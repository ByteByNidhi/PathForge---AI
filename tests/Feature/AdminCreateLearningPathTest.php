<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Organization;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCreateLearningPathTest extends TestCase
{
    public function test_admin_can_create_manual_path_edit_steps_publish_and_students_see_it(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $html = Skill::query()->where('name', 'HTML')->first();
        $this->assertNotNull($html);

        $name = 'PF New Path '.uniqid();
        $path = null;

        try {
            $this->actingAs($admin)
                ->get(route('admin.roadmaps.index'))
                ->assertOk()
                ->assertSee('Create New Path');

            $this->actingAs($admin)
                ->get(route('admin.roadmaps.create'))
                ->assertOk()
                ->assertSee('Create New Path');

            $this->actingAs($admin)
                ->from(route('admin.roadmaps.create'))
                ->post(route('admin.roadmaps.store'), [
                    'path_name' => '  '.$name.'  ',
                    'description' => 'A newly curated career path for tests.',
                    'icon' => 'path',
                ])
                ->assertRedirect();

            $path = LearningPath::query()->where('path_name', $name)->first();
            $this->assertNotNull($path);
            $this->assertFalse($path->is_published);

            $this->actingAs($student)
                ->get(route('roadmaps.index'))
                ->assertOk()
                ->assertDontSee($name);

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertNotFound();

            $this->actingAs($student)
                ->get('/onboarding')
                ->assertDontSee($name);

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.steps.store', $path), [
                    'step_no' => 1,
                    'title' => 'Learn HTML foundations',
                    'description' => 'Build a first page.',
                    'xp_reward' => 12,
                    'skill_ids' => [$html->id],
                ])
                ->assertRedirect(route('admin.roadmaps.show', $path));

            $step = $path->roadmapSteps()->where('title', 'Learn HTML foundations')->first();
            $this->assertNotNull($step);
            $this->assertFalse($step->is_published);
            $this->assertTrue($step->skills()->where('skills.id', $html->id)->exists());

            $this->actingAs($admin)
                ->put(route('admin.roadmaps.steps.update', [$path, $step]), [
                    'step_no' => 1,
                    'title' => 'Learn HTML foundations edited',
                    'description' => 'Build a first page, then review.',
                    'xp_reward' => 15,
                    'skill_ids' => [$html->id],
                ])
                ->assertRedirect();

            $this->assertSame('Learn HTML foundations edited', $step->fresh()->title);
            $this->assertSame(15, (int) $step->fresh()->xp_reward);

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertNotFound();

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.publish', $path))
                ->assertRedirect(route('admin.roadmaps.show', $path));

            $path->refresh();
            $this->assertTrue($path->is_published);
            $this->assertTrue($step->fresh()->is_published);

            $this->actingAs($student)
                ->get(route('roadmaps.index'))
                ->assertOk()
                ->assertSee($name);

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertOk()
                ->assertSee('Learn HTML foundations edited');

            $this->actingAs($student)
                ->post(route('roadmaps.select', $path))
                ->assertRedirect(route('roadmaps.show', $path));

            $this->assertSame((int) $path->id, (int) $student->fresh()->path_id);
        } finally {
            $this->cleanupPath($path ?? null);
            $student->delete();
            $admin->delete();
        }
    }

    public function test_duplicate_path_names_are_rejected_and_unpublished_paths_are_hidden(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($existing);

        try {
            $this->actingAs($admin)
                ->from(route('admin.roadmaps.create'))
                ->post(route('admin.roadmaps.store'), [
                    'path_name' => 'web development',
                    'description' => 'Should not clone the existing path.',
                ])
                ->assertRedirect(route('admin.roadmaps.create'))
                ->assertSessionHasErrors('path_name');

            $this->actingAs($admin)
                ->from(route('admin.roadmaps.create'))
                ->post(route('admin.roadmaps.store'), [
                    'path_name' => '',
                ])
                ->assertRedirect(route('admin.roadmaps.create'))
                ->assertSessionHasErrors('path_name');
        } finally {
            $admin->delete();
        }
    }

    public function test_ai_generated_draft_for_new_path_stays_unpublished_until_admin_publishes(): void
    {
        $this->fakeGeminiJson();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $name = 'PF AI New Path '.uniqid();
        $path = null;

        try {
            $this->actingAs($admin)
                ->post(route('admin.roadmaps.store'), [
                    'path_name' => $name,
                    'description' => 'AI draft career path.',
                ]);

            $path = LearningPath::query()->where('path_name', $name)->first();
            $this->assertNotNull($path);
            $this->assertFalse($path->is_published);

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.generate', $path), ['beginner' => 1])
                ->assertRedirect(route('admin.roadmaps.preview', $path));

            $this->assertGreaterThanOrEqual(8, $path->draftRoadmapSteps()->count());
            $this->assertSame(0, $path->publishedRoadmapSteps()->count());

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertNotFound();

            $draft = $path->draftRoadmapSteps()->orderBy('step_no')->first();
            $this->assertNotNull($draft);

            $this->actingAs($admin)
                ->put(route('admin.roadmaps.steps.update', [$path, $draft]), [
                    'step_no' => $draft->step_no,
                    'title' => 'Edited AI step title',
                    'description' => $draft->description,
                    'xp_reward' => 20,
                    'skill_ids' => $draft->skills()->pluck('skills.id')->all(),
                ])
                ->assertRedirect();

            $this->assertSame('Edited AI step title', $draft->fresh()->title);
            $this->assertFalse($draft->fresh()->is_published);

            $this->actingAs($admin)
                ->post(route('admin.roadmaps.publish', $path))
                ->assertRedirect(route('admin.roadmaps.show', $path));

            $path->refresh();
            $this->assertTrue($path->is_published);
            $this->assertSame(0, $path->draftRoadmapSteps()->count());
            $this->assertSame('Edited AI step title', $path->publishedRoadmapSteps()->orderBy('step_no')->first()->title);

            $this->actingAs($student)
                ->get(route('roadmaps.show', $path))
                ->assertOk()
                ->assertSee('Edited AI step title');
        } finally {
            $this->cleanupPath($path ?? null);
            $student->delete();
            $admin->delete();
        }
    }

    public function test_students_and_organization_users_cannot_access_admin_path_management(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create([
            'name' => 'PF Path Auth Org '.uniqid(),
        ]);
        $owner = User::factory()->create([
            'is_admin' => false,
            'onboarding_completed' => true,
        ]);
        $organization->users()->attach($owner->id, ['role' => Organization::ROLE_OWNER]);

        $path = LearningPath::query()->orderBy('path_name')->first();
        $this->assertNotNull($path);

        try {
            Http::fake();

            foreach ([$student, $owner] as $actor) {
                $this->actingAs($actor)
                    ->get(route('admin.roadmaps.create'))
                    ->assertForbidden();
                $this->actingAs($actor)
                    ->post(route('admin.roadmaps.store'), [
                        'path_name' => 'PF Forbidden Path '.uniqid(),
                    ])
                    ->assertForbidden();
                $this->actingAs($actor)
                    ->post(route('admin.roadmaps.generate', $path))
                    ->assertForbidden();
                $this->actingAs($actor)
                    ->post(route('admin.roadmaps.publish', $path))
                    ->assertForbidden();
            }

            Http::assertNothingSent();
            $this->assertFalse(LearningPath::query()->where('path_name', 'like', 'PF Forbidden Path %')->exists());
        } finally {
            $organization->users()->detach();
            $organization->delete();
            $student->delete();
            $owner->delete();
            $admin->delete();
        }
    }

    private function cleanupPath(?LearningPath $path): void
    {
        if ($path === null) {
            return;
        }

        $path->roadmapSteps()->each(function (RoadmapStep $step) {
            $step->skills()->detach();
            $step->userProgress()->delete();
            $step->delete();
        });
        $path->skills()->detach();
        $path->delete();
    }

    private function fakeGeminiJson(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key-not-for-clients',
            'services.gemini.model' => 'gemini-3.6-flash',
            'services.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);

        $html = Skill::query()->where('name', 'HTML')->value('name') ?? 'HTML';
        $git = Skill::query()->where('name', 'Git')->value('name') ?? $html;
        $steps = [];
        for ($i = 1; $i <= 8; $i++) {
            $steps[] = [
                'title' => $i === 1 ? 'Set up HTML basics' : 'Foundation step '.$i,
                'description' => 'Complete a practical beginner task for step '.$i.' of this career path.',
                'xp_reward' => 10,
                'skills' => [$i % 2 === 0 ? $git : $html],
            ];
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'title' => 'AI Web Foundations',
                                    'description' => 'A complete foundation roadmap generated for tests.',
                                    'steps' => $steps,
                                ])],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }
}
