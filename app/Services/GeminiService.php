<?php

namespace App\Services;

use App\Exceptions\GeminiServiceException;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiService
{
    public const MAX_HISTORY = 8;

    public function generateReply(User $user, string $message, array $history = []): string
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-3.6-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $timeout = (int) config('services.gemini.timeout', 45);

        if ($apiKey === '') {
            throw new GeminiServiceException(
                'The career assistant is not available right now. Please try again later.'
            );
        }

        $url = $baseUrl.'/models/'.$model.':generateContent';

        $contents = $this->buildContents($history, $message);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $this->systemPrompt($user)],
                        ],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ]);
        } catch (RequestException $e) {
            report($e);

            throw new GeminiServiceException(
                'The career assistant could not be reached. Please try again in a moment.'
            );
        } catch (Throwable $e) {
            report($e);

            throw new GeminiServiceException(
                'The career assistant could not be reached. Please try again in a moment.'
            );
        }

        if ($response->failed()) {
            throw new GeminiServiceException(
                'The career assistant is busy or unavailable. Please try again shortly.'
            );
        }

        $text = $this->extractText($response->json());

        if ($text === '') {
            throw new GeminiServiceException(
                'The career assistant returned an empty reply. Please try asking in a different way.'
            );
        }

        return $text;
    }

    /**
     * @param  list<array{role: string, text: string}>  $history
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function buildContents(array $history, string $message): array
    {
        $contents = [];

        foreach (array_slice($history, -self::MAX_HISTORY) as $turn) {
            $role = ($turn['role'] ?? '') === 'model' ? 'model' : 'user';
            $text = trim((string) ($turn['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        return $contents;
    }

    private function systemPrompt(User $user): string
    {
        $user->loadMissing(['learningPath.roadmapSteps', 'skills', 'userProgress']);

        $path = $user->learningPath;
        $pathName = $path?->path_name ?? 'None selected yet';
        $pathDescription = $path?->description ? trim((string) $path->description) : 'No description available.';

        $skills = $user->skills
            ->map(function ($skill) {
                $level = $skill->pivot->level ?? null;

                return $level ? $skill->name.' (level '.$level.')' : $skill->name;
            })
            ->filter()
            ->values();

        $skillList = $skills->isEmpty()
            ? 'No skills recorded yet'
            : $skills->implode(', ');

        $completed = [];
        $upcoming = [];

        if ($path) {
            $completedIds = $user->userProgress
                ->where('status', 'completed')
                ->pluck('roadmap_step_id')
                ->all();

            foreach ($path->roadmapSteps->sortBy('step_no') as $step) {
                $label = 'Step '.$step->step_no.': '.$step->title;
                if (in_array($step->id, $completedIds, true)) {
                    $completed[] = $label;
                } else {
                    $upcoming[] = $label;
                }
            }
        }

        $completedText = $completed === [] ? 'None yet' : implode('; ', $completed);
        $upcomingText = $upcoming === [] ? 'None remaining or no roadmap selected' : implode('; ', array_slice($upcoming, 0, 8));

        return <<<PROMPT
You are PathForge AI Studio, a practical career assistant for students using the PathForge learning platform.

Give concise, encouraging, career-focused advice. Prefer concrete next steps, project ideas, and skill-building plans over generic motivation. If information is missing, say so and suggest what the student can add in PathForge (skills, roadmap, or completed steps). Do not invent internships, employers, or credentials the student has not mentioned. Do not ask for or reveal API keys or passwords.

Student context from PathForge (trusted server data):
- Name: {$user->name}
- Career path: {$pathName}
- Path notes: {$pathDescription}
- Level: {$user->level}
- XP: {$user->xp}
- Skills: {$skillList}
- Completed roadmap steps: {$completedText}
- Upcoming roadmap steps: {$upcomingText}
PROMPT;
    }

    private function extractText(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        $parts = data_get($payload, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        $chunks = [];

        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $chunks[] = $part['text'];
            }
        }

        return trim(implode("\n", $chunks));
    }
}
