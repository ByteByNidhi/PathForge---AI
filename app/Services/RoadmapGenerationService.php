<?php

namespace App\Services;

use App\Exceptions\GeminiServiceException;
use App\Exceptions\RoadmapGenerationException;
use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RoadmapGenerationService
{
    public const MIN_STEPS = 8;

    public const MAX_STEPS = 20;

    public function __construct(private GeminiService $gemini)
    {
    }

    public function generateDraft(LearningPath $path, bool $beginner = false): LearningPath
    {
        $path->loadMissing('skills');

        $catalog = Skill::query()->orderBy('name')->get();

        if ($catalog->isEmpty()) {
            throw new RoadmapGenerationException(
                'Roadmap generation needs skills in the catalog before Gemini can run.'
            );
        }

        $isBeginner = $beginner || $path->skills->isEmpty();
        $promptContext = $this->promptContext($path, $catalog, $isBeginner);

        try {
            $raw = $this->gemini->generateJson(
                $this->systemPrompt($catalog, $isBeginner),
                $this->userPrompt($path, $promptContext, $isBeginner),
                [
                    'responseSchema' => $this->responseSchema(),
                ]
            );
        } catch (GeminiServiceException $e) {
            throw new RoadmapGenerationException($e->getMessage(), 0, $e);
        }

        $payload = $this->decodeJson($raw);
        $validated = $this->validatePayload($payload, $catalog);

        DB::transaction(function () use ($path, $validated) {
            $path->draftRoadmapSteps()->each(function (RoadmapStep $step) {
                $step->skills()->detach();
                $step->delete();
            });

            foreach ($validated['steps'] as $index => $stepData) {
                $step = RoadmapStep::query()->create([
                    'path_id' => $path->id,
                    'step_no' => $index + 1,
                    'title' => $stepData['title'],
                    'description' => $stepData['description'],
                    'xp_reward' => $stepData['xp_reward'],
                    'is_published' => false,
                ]);

                $step->skills()->sync($stepData['skill_ids']);
            }

            $path->forceFill([
                'roadmap_draft_title' => $validated['title'],
                'roadmap_draft_description' => $validated['description'],
                'roadmap_generated_at' => now(),
            ])->save();
        });

        return $path->refresh();
    }

    public function publishDraft(LearningPath $path): LearningPath
    {
        $path->loadMissing(['draftRoadmapSteps.skills', 'publishedRoadmapSteps']);

        if ($path->draftRoadmapSteps->isEmpty()) {
            throw new RoadmapGenerationException(
                'There is no AI draft to publish. Generate a roadmap first.'
            );
        }

        if ($path->hasLiveStudentProgress()) {
            throw new RoadmapGenerationException(
                'This career path already has user progress. Publishing would replace the live roadmap and is blocked. Existing progress has not been changed.'
            );
        }

        DB::transaction(function () use ($path) {
            $path->publishedRoadmapSteps()->each(function (RoadmapStep $step) {
                $step->skills()->detach();
                $step->delete();
            });

            foreach ($path->draftRoadmapSteps()->orderBy('step_no')->orderBy('id')->get() as $step) {
                $step->is_published = true;
                $step->save();
            }

            $description = trim((string) $path->roadmap_draft_description);

            $path->forceFill([
                'description' => $description !== '' ? $description : $path->description,
                'roadmap_source' => LearningPath::SOURCE_AI,
                'roadmap_generated_at' => now(),
                'roadmap_draft_title' => null,
                'roadmap_draft_description' => null,
            ])->save();
        });

        return $path->refresh();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Skill>  $catalog
     * @return array<string, mixed>
     */
    private function promptContext(LearningPath $path, $catalog, bool $isBeginner): array
    {
        $pathSkills = $path->skills->pluck('name')->filter()->values()->all();

        $students = User::query()
            ->where('path_id', $path->id)
            ->where('is_admin', false);

        $studentCount = (int) (clone $students)->count();
        $averageLevel = $studentCount > 0
            ? (float) (clone $students)->avg('level')
            : null;

        return [
            'path_skills' => $pathSkills,
            'catalog_skills' => $catalog->pluck('name')->all(),
            'student_count' => $studentCount,
            'average_level' => $averageLevel,
            'is_beginner' => $isBeginner,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Skill>  $catalog
     */
    private function systemPrompt($catalog, bool $isBeginner): string
    {
        $skillList = $catalog->pluck('name')->implode(', ');
        $foundation = $isBeginner
            ? 'The audience is a beginner with little or no prior skill. Generate a complete foundational roadmap from first principles. Do not return an empty or very short roadmap. Cover orientation, core tools, practice projects, and a first portfolio milestone.'
            : 'Match step difficulty to the path skills and typical student level. Still produce a complete, sequenced roadmap rather than a short outline.';

        return <<<PROMPT
You generate learning roadmaps for PathForge career paths.

Return STRICT JSON only that matches the required schema. Do not include markdown, commentary, or extra keys.

Rules:
- Use only skill names from this catalog, copied exactly: {$skillList}
- Never invent skills, employers, courses, or tools outside that catalog.
- Every step must include at least one catalog skill.
- Produce between 8 and 20 steps.
- Each step needs a clear title, a practical description, an integer XP reward between 5 and 50, and skills.
- {$foundation}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function userPrompt(LearningPath $path, array $context, bool $isBeginner): string
    {
        $pathSkills = $context['path_skills'] === []
            ? 'None recorded for this path (treat as beginner / no skills).'
            : implode(', ', $context['path_skills']);

        $levelLine = $context['average_level'] === null
            ? 'No enrolled students yet; assume an introductory starting level.'
            : 'Enrolled students: '.$context['student_count'].'; average PathForge level: '.round((float) $context['average_level'], 1).'.';

        $beginnerLine = $isBeginner
            ? 'BEGINNER / NO-SKILLS CASE: Generate a complete foundation roadmap with at least 10 practical steps. Do not skip fundamentals.'
            : 'Use the path skills as the relevant skill set. Do not assume expertise the catalog does not support.';

        $description = trim((string) $path->description);
        $description = $description !== '' ? $description : 'No description provided.';

        return <<<PROMPT
Create a learning roadmap for this PathForge career path.

Career path: {$path->path_name}
Path description: {$description}
Relevant path skills: {$pathSkills}
Level context: {$levelLine}
{$beginnerLine}

JSON shape:
{
  "title": "short roadmap title",
  "description": "1-3 sentence overview",
  "steps": [
    {
      "title": "step title",
      "description": "what the student should do",
      "xp_reward": 10,
      "skills": ["Exact Catalog Skill"]
    }
  ]
}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'description' => ['type' => 'STRING'],
                'steps' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'description' => ['type' => 'STRING'],
                            'xp_reward' => ['type' => 'INTEGER'],
                            'skills' => [
                                'type' => 'ARRAY',
                                'items' => ['type' => 'STRING'],
                            ],
                        ],
                        'required' => ['title', 'description', 'xp_reward', 'skills'],
                    ],
                ],
            ],
            'required' => ['title', 'description', 'steps'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $text = trim($raw);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
            $text = trim($text);
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RoadmapGenerationException(
                'Gemini did not return valid JSON for this roadmap. Nothing was saved.'
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<int, Skill>  $catalog
     * @return array{title: string, description: string, steps: list<array{title: string, description: string, xp_reward: int, skill_ids: list<int>}>}
     */
    private function validatePayload(array $payload, $catalog): array
    {
        $validator = Validator::make($payload, [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'steps' => ['required', 'array', 'min:'.self::MIN_STEPS, 'max:'.self::MAX_STEPS],
            'steps.*.title' => ['required', 'string', 'min:3', 'max:255'],
            'steps.*.description' => ['required', 'string', 'min:10', 'max:2000'],
            'steps.*.xp_reward' => ['required', 'integer', 'min:5', 'max:50'],
            'steps.*.skills' => ['required', 'array', 'min:1', 'max:8'],
            'steps.*.skills.*' => ['required', 'string', 'max:120'],
        ]);

        try {
            $validated = $validator->validate();
        } catch (ValidationException $e) {
            throw new RoadmapGenerationException(
                'Gemini returned a roadmap that failed validation. Nothing was saved. '.$e->validator->errors()->first()
            );
        }

        $skillsByName = $catalog->mapWithKeys(
            fn (Skill $skill) => [mb_strtolower(trim($skill->name)) => $skill]
        );

        $steps = [];

        foreach ($validated['steps'] as $step) {
            $skillIds = [];

            foreach ($step['skills'] as $name) {
                $key = mb_strtolower(trim((string) $name));
                $skill = $skillsByName->get($key);

                if (! $skill instanceof Skill) {
                    throw new RoadmapGenerationException(
                        'Gemini returned a skill that is not in the PathForge catalog: '.$name.'. Fake or unknown skills are not saved.'
                    );
                }

                $skillIds[$skill->id] = $skill->id;
            }

            $steps[] = [
                'title' => trim($step['title']),
                'description' => trim($step['description']),
                'xp_reward' => (int) $step['xp_reward'],
                'skill_ids' => array_values($skillIds),
            ];
        }

        return [
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'steps' => $steps,
        ];
    }
}
