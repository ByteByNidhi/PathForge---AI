<?php

namespace App\Services;

use App\Exceptions\HimalayasServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HimalayasJobService
{
    public const SOURCE = 'himalayas';

    public const ATTRIBUTION_URL = 'https://himalayas.app';

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function search(string $query, array $filters = []): array
    {
        $url = (string) config('services.himalayas.base_url');
        $timeout = (int) config('services.himalayas.timeout', 15);
        $limit = $this->limitFromFilters($filters);

        $queryParams = array_filter([
            'q' => trim($query) !== '' ? trim($query) : null,
            'country' => $this->stringFilter($filters, 'country'),
            'worldwide' => $this->boolFilter($filters, 'worldwide'),
            'exclude_worldwide' => $this->boolFilter($filters, 'exclude_worldwide'),
            'seniority' => $this->stringFilter($filters, 'seniority'),
            'employment_type' => $this->stringFilter($filters, 'employment_type'),
            'company' => $this->stringFilter($filters, 'company'),
            'timezone' => $this->stringFilter($filters, 'timezone'),
            'sort' => $this->stringFilter($filters, 'sort'),
            'page' => isset($filters['page']) ? (int) $filters['page'] : 1,
            'limit' => $limit,
        ], static fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, $queryParams);
        } catch (ConnectionException $exception) {
            Log::warning('Himalayas API connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            throw new HimalayasServiceException(
                'Himalayas could not be reached. Please try again in a moment.'
            );
        } catch (Throwable $exception) {
            Log::warning('Himalayas API request failed.', [
                'message' => $exception->getMessage(),
            ]);

            throw new HimalayasServiceException(
                'Himalayas could not be reached. Please try again in a moment.'
            );
        }

        if ($response->status() === 429) {
            Log::warning('Himalayas API rate limited.', [
                'status' => 429,
            ]);

            throw new HimalayasServiceException(
                'Himalayas is rate-limiting requests right now. Please wait a minute and try again.'
            );
        }

        if ($response->serverError()) {
            Log::warning('Himalayas API server error.', [
                'status' => $response->status(),
            ]);

            throw new HimalayasServiceException(
                'Himalayas is unavailable right now. Please try again shortly.'
            );
        }

        if ($response->failed()) {
            Log::warning('Himalayas API HTTP failure.', [
                'status' => $response->status(),
            ]);

            throw new HimalayasServiceException(
                'Himalayas could not complete the search. Please try again shortly.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('jobs', $payload) || ! is_array($payload['jobs'])) {
            Log::warning('Himalayas API returned an invalid payload.');

            throw new HimalayasServiceException(
                'Himalayas returned an unexpected response. No jobs were imported.'
            );
        }

        $jobs = [];

        foreach ($payload['jobs'] as $job) {
            if (! is_array($job)) {
                continue;
            }

            $normalized = $this->normalizeJob($job);

            if ($normalized === null) {
                continue;
            }

            $jobs[] = $normalized;

            if (count($jobs) >= $limit) {
                break;
            }
        }

        return $jobs;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>|null
     */
    public function normalizeJob(array $job): ?array
    {
        $title = trim((string) ($job['title'] ?? ''));
        $applicationUrl = trim((string) ($job['applicationLink'] ?? ''));
        $guid = trim((string) ($job['guid'] ?? ''));
        $externalId = $guid !== '' ? $guid : $this->fallbackExternalId($applicationUrl);

        if ($title === '' || $externalId === '') {
            return null;
        }

        $excerpt = trim(strip_tags((string) ($job['excerpt'] ?? '')));
        $description = trim(strip_tags((string) ($job['description'] ?? '')));
        $storedDescription = $description !== '' ? $description : $excerpt;

        $categories = $this->stringList($job['categories'] ?? []);
        $parentCategories = $this->stringList($job['parentCategories'] ?? []);

        return [
            'external_id' => mb_substr($externalId, 0, 512),
            'source' => self::SOURCE,
            'source_url' => $applicationUrl !== '' ? $applicationUrl : self::ATTRIBUTION_URL,
            'title' => mb_substr($title, 0, 255),
            'organization' => mb_substr(trim((string) ($job['companyName'] ?? 'Unknown company')), 0, 255),
            'description' => $storedDescription !== '' ? $storedDescription : $title,
            'excerpt' => $excerpt,
            'type' => $this->mapEmploymentType($job['employmentType'] ?? null),
            'location' => $this->formatLocation($job['locationRestrictions'] ?? []),
            'application_url' => $applicationUrl !== '' ? $applicationUrl : self::ATTRIBUTION_URL,
            'deadline' => $this->timestampToDate($job['expiryDate'] ?? null),
            'published_at' => $this->timestampToDate($job['pubDate'] ?? null),
            'eligibility' => $this->formatEligibility($job),
            'categories' => $categories,
            'parent_categories' => $parentCategories,
            'match_text' => $this->matchText($title, $excerpt, $storedDescription, $categories, $parentCategories),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function limitFromFilters(array $filters): int
    {
        $configured = (int) config('services.himalayas.max_results', 10);
        $requested = isset($filters['limit']) ? (int) $filters['limit'] : $configured;

        return max(1, min(20, $requested));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function stringFilter(array $filters, string $key): ?string
    {
        if (! isset($filters[$key])) {
            return null;
        }

        $value = trim((string) $filters[$key]);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function boolFilter(array $filters, string $key): ?string
    {
        if (! array_key_exists($key, $filters) || $filters[$key] === null || $filters[$key] === '') {
            return null;
        }

        $value = $filters[$key];

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    private function fallbackExternalId(string $applicationUrl): string
    {
        $normalized = $this->normalizeUrl($applicationUrl);

        return $normalized !== '' ? $normalized : '';
    }

    public function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return mb_strtolower(rtrim($url, '/'));
        }

        $host = mb_strtolower((string) $parts['host']);
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $host.$path.$query;
    }

    private function mapEmploymentType(mixed $employmentType): string
    {
        $value = mb_strtolower(trim((string) $employmentType));

        if ($value === '') {
            return 'Internship';
        }

        if (str_contains($value, 'intern')) {
            return 'Internship';
        }

        if (str_contains($value, 'scholarship')) {
            return 'Scholarship';
        }

        if (str_contains($value, 'research')) {
            return 'Research';
        }

        if (str_contains($value, 'hackathon')) {
            return 'Hackathon';
        }

        return mb_substr(trim((string) $employmentType), 0, 255);
    }

    private function formatLocation(mixed $restrictions): string
    {
        $places = $this->stringList($restrictions);

        if ($places === []) {
            return 'Remote (worldwide)';
        }

        if (count($places) <= 3) {
            return 'Remote · '.implode(', ', $places);
        }

        return 'Remote · '.count($places).' locations';
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function formatEligibility(array $job): string
    {
        $parts = [];

        $salary = $this->formatSalary($job);
        if ($salary !== null) {
            $parts[] = $salary;
        }

        $seniority = $this->stringList($job['seniority'] ?? []);
        if ($seniority !== []) {
            $parts[] = 'Seniority: '.implode(', ', $seniority);
        }

        $employment = trim((string) ($job['employmentType'] ?? ''));
        if ($employment !== '') {
            $parts[] = 'Employment: '.$employment;
        }

        return $parts !== [] ? implode('. ', $parts).'.' : '';
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function formatSalary(array $job): ?string
    {
        $min = $job['minSalary'] ?? null;
        $max = $job['maxSalary'] ?? null;
        $currency = trim((string) ($job['currency'] ?? ''));
        $period = trim((string) ($job['salaryPeriod'] ?? ''));

        if ($min === null && $max === null) {
            return null;
        }

        $range = $min !== null && $max !== null
            ? $min.'–'.$max
            : (string) ($min ?? $max);

        $label = 'Salary: '.trim($currency.' '.$range);
        if ($period !== '') {
            $label .= ' '.$period;
        }

        return $label;
    }

    private function timestampToDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;

            if ($timestamp <= 0) {
                return null;
            }

            return date('Y-m-d', $timestamp);
        }

        $parsed = strtotime((string) $value);

        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    /**
     * @param  list<string>  $categories
     * @param  list<string>  $parentCategories
     */
    private function matchText(
        string $title,
        string $excerpt,
        string $description,
        array $categories,
        array $parentCategories
    ): string {
        return trim(implode(' ', [
            $title,
            $excerpt,
            $description,
            implode(' ', $categories),
            implode(' ', $parentCategories),
        ]));
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item) && ! is_numeric($item)) {
                continue;
            }

            $text = trim((string) $item);
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return array_values($items);
    }
}
