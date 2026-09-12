<?php

namespace App\Services;

use App\Models\LearningPath;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Log;

class OpportunityImportService
{
    public function __construct(
        private HimalayasJobService $himalayas,
        private OpportunitySkillMatcher $matcher,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{fetched: int, imported: int, duplicates: int, query: string}
     */
    public function importFromSearch(string $query, array $filters = []): array
    {
        $jobs = $this->himalayas->search($query, $filters);
        $imported = 0;
        $duplicates = 0;

        foreach ($jobs as $job) {
            if ($this->findExisting($job) !== null) {
                $duplicates++;
                continue;
            }

            try {
                $this->storeJob($job);
                $imported++;
            } catch (\Throwable $exception) {
                Log::warning('Failed to import Himalayas job.', [
                    'external_id' => $job['external_id'] ?? null,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'fetched' => count($jobs),
            'imported' => $imported,
            'duplicates' => $duplicates,
            'query' => $query,
        ];
    }

    public function searchQueryForPath(LearningPath $path): string
    {
        $path->loadMissing('skills');

        $name = trim((string) $path->path_name);
        $primary = preg_replace('/\s+development$/i', ' developer', $name) ?? $name;
        $primary = trim((string) $primary);

        $skillTerms = $path->skills
            ->pluck('name')
            ->filter()
            ->unique()
            ->take(4)
            ->implode(' ');

        return trim($primary.' '.$skillTerms);
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function storeJob(array $job): Opportunity
    {
        $matchedSkills = $this->matcher->match((string) ($job['match_text'] ?? ''));
        $skillNames = array_map(static fn ($skill) => $skill->name, $matchedSkills);

        $opportunity = Opportunity::query()->create([
            'title' => $job['title'],
            'organization' => $job['organization'],
            'type' => $job['type'],
            'description' => $job['description'],
            'required_skills' => $skillNames !== [] ? implode(', ', $skillNames) : null,
            'eligibility' => $job['eligibility'] !== '' ? $job['eligibility'] : null,
            'deadline' => $job['deadline'],
            'application_url' => $job['application_url'],
            'location' => $job['location'],
            'source' => HimalayasJobService::SOURCE,
            'external_id' => $job['external_id'],
            'source_url' => $job['source_url'],
            'approval_status' => Opportunity::APPROVAL_PENDING,
        ]);

        if ($matchedSkills !== []) {
            $opportunity->skills()->sync(array_map(static fn ($skill) => $skill->id, $matchedSkills));
        }

        return $opportunity;
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function findExisting(array $job): ?Opportunity
    {
        $source = (string) ($job['source'] ?? HimalayasJobService::SOURCE);
        $externalId = (string) ($job['external_id'] ?? '');

        if ($externalId !== '') {
            $existing = Opportunity::query()
                ->where('source', $source)
                ->where('external_id', $externalId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $applicationUrl = (string) ($job['application_url'] ?? '');

        if ($applicationUrl === '') {
            return null;
        }

        return Opportunity::query()
            ->where('application_url', $applicationUrl)
            ->first();
    }
}
