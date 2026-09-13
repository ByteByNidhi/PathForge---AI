<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DynamicAiRoadmapTest extends TestCase
{
    public function test_successful_gemini_roadmap_generation_persists_unpublished_steps(): void
    {
        $this->fakeGeminiJson($this->validPayload(['HTML', 'Git']));

        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML', 'Git']);

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path), ['beginner' => 0])
                ->assertRedirect(route('admin.roadmaps.preview', $path));

            $path->refresh();
            $drafts = $path->draftRoadmapSteps()->orderBy('step_no')->get();

            $this->assertCount(8, $drafts);
            $this->assertSame('AI Web Foundations', $path->roadmap_draft_title);
            $this->assertNotNull($path->roadmap_generated_at);
            $this->assertSame(LearningPath::SOURCE_CURATED, $path->roadmap_source ?? LearningPath::SOURCE_CURATED);
            $this->assertTrue($drafts->every(fn (RoadmapStep $step) => $step->is_published === false));
            $this->assertSame('Set up HTML basics', $drafts->first()->title);
            $this->assertNotNull($drafts->first()->description);
            $this->assertEqualsCanonicalizing(
                ['HTML'],
                $drafts->first()->skills()->pluck('name')->all()
            );

            Http::assertSent(function ($request) use ($path) {
                $body = $request->data();
                $system = (string) data_get($body, 'systemInstruction.parts.0.text');
                $userText = (string) data_get($body, 'contents.0.parts.0.text');
                $mime = data_get($body, 'generationConfig.responseMimeType');

                return $mime === 'application/json'
                    && str_contains($userText, $path->path_name)
                    && str_contains($userText, 'HTML')
                    && str_contains($system, 'STRICT JSON');
            });
        } finally {
            $cleanup();
        }
    }

    public function test_malformed_gemini_response_is_rejected(): void
    {
        $this->configureGemini();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Here is a roadmap but not JSON.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML']);

        try {
            $this->actingAs($admin)
                ->from(route('admin.roadmaps.show', $path))
                ->post(route('admin.roadmaps.generate', $path))
                ->assertRedirect(route('admin.roadmaps.show', $path))
                ->assertSessionHas('error');

            $this->assertSame(0, $path->draftRoadmapSteps()->count());
            $this->assertDatabaseMissing('roadmap_steps', [
                'path_id' => $path->id,
                'title' => 'Here is a roadmap but not JSON.',
            ]);
        } finally {
            $cleanup();
        }
    }

    public function test_unknown_skill_from_gemini_is_rejected(): void
    {
        $payload = $this->validPayload(['HTML']);
        $payload['steps'][0]['skills'] = ['Quantum Baking 9000'];
        $this->fakeGeminiJson($payload);

        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML']);

        try {
            $this->actingAs($admin)
                ->from(route('admin.roadmaps.show', $path))
                ->post(route('admin.roadmaps.generate', $path))
                ->assertRedirect(route('admin.roadmaps.show', $path))
                ->assertSessionHas('error');

            $this->assertSame(0, $path->draftRoadmapSteps()->count());
            $this->assertFalse(Skill::query()->where('name', 'Quantum Baking 9000')->exists());
        } finally {
            $cleanup();
        }
    }

    public function test_publish_workflow_writes_live_steps_and_metadata(): void
    {
        $this->fakeGeminiJson($this->validPayload(['HTML', 'Git']));
        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML', 'Git']);

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path))
                ->assertRedirect(route('admin.roadmaps.preview', $path));

            $this->actingAs($admin)
                ->get(route('admin.roadmaps.preview', $path))
                ->assertOk()
                ->assertSee('Review AI draft')
                ->assertSee('Set up HTML basics')
                ->assertSee('Publish');

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.publish', $path))
                ->assertRedirect(route('admin.roadmaps.show', $path));

            $path->refresh();
            $this->assertSame(LearningPath::SOURCE_AI, $path->roadmap_source);
            $this->assertNotNull($path->roadmap_generated_at);
            $this->assertNull($path->roadmap_draft_title);
            $this->assertSame(0, $path->draftRoadmapSteps()->count());
            $this->assertSame(8, $path->publishedRoadmapSteps()->count());
            $this->assertSame('Set up HTML basics', $path->publishedRoadmapSteps()->orderBy('step_no')->first()->title);
            $this->assertTrue($path->publishedRoadmapSteps()->orderBy('step_no')->first()->is_published);
        } finally {
            $cleanup();
        }
    }

    public function test_unpublished_ai_draft_is_hidden_from_students(): void
    {
        $this->fakeGeminiJson($this->validPayload(['HTML', 'Git']));
        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML', 'Git']);
        $student = User::factory()->create();

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path));

            Http::fake();

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertOk()
                ->assertDontSee('Set up HTML basics')
                ->assertDontSee('Generate with AI')
                ->assertDontSee('Review AI draft')
                ->assertDontSee('Publish')
                ->assertSee('This roadmap has no steps yet.');

            $this->actingAs($student)
                ->get(route('dashboard'))
                ->assertDontSee('Generate with AI');

            Http::assertNothingSent();

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.publish', $path));

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertOk()
                ->assertSee('Set up HTML basics')
                ->assertDontSee('Generate with AI');
        } finally {
            $student->delete();
            $cleanup();
        }
    }

    public function test_existing_user_progress_blocks_destructive_publish(): void
    {
        $this->fakeGeminiJson($this->validPayload(['HTML', 'Git']));
        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML', 'Git']);
        $student = User::factory()->create(['path_id' => null]);

        $live = RoadmapStep::query()->create([
            'path_id' => $path->id,
            'step_no' => 1,
            'title' => 'Keep this live step',
            'description' => 'Students already started this.',
            'xp_reward' => 10,
            'is_published' => true,
        ]);

        $student->path_id = $path->id;
        $student->save();

        UserProgress::query()->create([
            'user_id' => $student->id,
            'roadmap_step_id' => $live->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path))
                ->assertRedirect(route('admin.roadmaps.preview', $path));

            $this->assertSame(8, $path->draftRoadmapSteps()->count());
            $this->assertTrue(RoadmapStep::query()->whereKey($live->id)->exists());

            $this->actingAs($admin)
                ->from(route('admin.roadmaps.preview', $path))
                ->post(route('admin.roadmaps.publish', $path))
                ->assertRedirect(route('admin.roadmaps.preview', $path))
                ->assertSessionHas('error');

            $this->assertTrue(RoadmapStep::query()->whereKey($live->id)->exists());
            $this->assertSame('Keep this live step', $live->fresh()->title);
            $this->assertTrue($live->fresh()->is_published);
            $this->assertSame(1, UserProgress::query()->where('user_id', $student->id)->where('roadmap_step_id', $live->id)->count());
            $this->assertSame(LearningPath::SOURCE_CURATED, $path->fresh()->roadmap_source ?? LearningPath::SOURCE_CURATED);
            $this->assertSame(8, $path->draftRoadmapSteps()->count());
        } finally {
            UserProgress::query()->where('user_id', $student->id)->delete();
            $student->delete();
            $cleanup();
        }
    }

    public function test_beginner_path_without_skills_requests_a_foundation_roadmap(): void
    {
        $this->fakeGeminiJson($this->validPayload(['HTML', 'Git']));
        [$admin, $path, $cleanup] = $this->makePathWithSkills([]);

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path))
                ->assertRedirect(route('admin.roadmaps.preview', $path));

            $this->assertGreaterThanOrEqual(8, $path->draftRoadmapSteps()->count());

            Http::assertSent(function ($request) {
                $system = (string) data_get($request->data(), 'systemInstruction.parts.0.text');
                $userText = (string) data_get($request->data(), 'contents.0.parts.0.text');

                return str_contains($system, 'beginner')
                    && str_contains($userText, 'BEGINNER / NO-SKILLS CASE')
                    && str_contains($userText, 'complete foundation');
            });
        } finally {
            $cleanup();
        }
    }

    public function test_students_cannot_trigger_generation(): void
    {
        [$admin, $path, $cleanup] = $this->makePathWithSkills(['HTML']);
        $student = User::factory()->create();

        try {
            Http::fake();

            $this->actingAs($student)
                ->post(route('admin.roadmaps.generate', $path))
                ->assertForbidden();

            Http::assertNothingSent();
            $this->assertSame(0, $path->draftRoadmapSteps()->count());
        } finally {
            $student->delete();
            $cleanup();
        }
    }

    /**
     * @param  list<string>  $skillNames
     * @return array{0: User, 1: LearningPath, 2: callable(): void}
     */
    private function makePathWithSkills(array $skillNames): array
    {
        $admin = User::factory()->admin()->create();
        $path = LearningPath::query()->create([
            'path_name' => 'PF AI Roadmap Test '.uniqid(),
            'description' => 'Isolated path for AI roadmap tests.',
            'roadmap_source' => LearningPath::SOURCE_CURATED,
        ]);

        $skillIds = [];
        foreach ($skillNames as $name) {
            $skill = Skill::query()->where('name', $name)->first();
            $this->assertNotNull($skill, $name.' must exist in the skill catalog.');
            $skillIds[] = $skill->id;
        }

        if ($skillIds !== []) {
            $path->skills()->sync($skillIds);
        }

        $cleanup = function () use ($admin, $path): void {
            $path->roadmapSteps()->each(function (RoadmapStep $step) {
                $step->skills()->detach();
                $step->userProgress()->delete();
                $step->delete();
            });
            $path->skills()->detach();
            $path->delete();
            $admin->delete();
        };

        return [$admin, $path, $cleanup];
    }

    /**
     * @param  list<string>  $skills
     * @return array<string, mixed>
     */
    private function validPayload(array $skills): array
    {
        $primary = $skills[0] ?? 'HTML';
        $secondary = $skills[1] ?? $primary;
        $steps = [];

        for ($i = 1; $i <= 8; $i++) {
            $steps[] = [
                'title' => $i === 1 ? 'Set up HTML basics' : 'Foundation step '.$i,
                'description' => 'Complete a practical beginner task for step '.$i.' of this career path.',
                'xp_reward' => 10,
                'skills' => [$i % 2 === 0 ? $secondary : $primary],
            ];
        }

        return [
            'title' => 'AI Web Foundations',
            'description' => 'A complete foundation roadmap generated for tests.',
            'steps' => $steps,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeGeminiJson(array $payload): void
    {
        $this->configureGemini();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($payload)],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function configureGemini(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key-not-for-clients',
            'services.gemini.model' => 'gemini-3.6-flash',
            'services.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);
    }
}
