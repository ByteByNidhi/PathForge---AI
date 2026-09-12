<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class StudentProgressionLoopTest extends TestCase
{
    public function test_career_paths_are_linked_to_catalog_skills(): void
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path, 'Web Development path must exist.');

        $skillNames = $path->skills()->pluck('name');
        $this->assertTrue($skillNames->contains('HTML'));
        $this->assertTrue($skillNames->contains('JavaScript'));
        $this->assertFalse($skillNames->contains('Cybersecurity'));
        $this->assertTrue(
            Skill::query()->where('name', 'HTML')->exists(),
            'Global skill catalog must still contain HTML.'
        );
        $this->assertTrue(
            Skill::query()->where('name', 'Cybersecurity')->exists(),
            'Global skill catalog must still contain Cybersecurity.'
        );
    }

    public function test_onboarding_shows_only_skills_for_the_selected_path(): void
    {
        $user = User::factory()->needsOnboarding()->create();
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $pathSkills = $path->skills()->orderBy('name')->pluck('name');
        $this->assertNotEmpty($pathSkills);
        $this->assertTrue($pathSkills->contains('HTML'));
        $this->assertFalse($pathSkills->contains('Cybersecurity'));

        try {
            $this->actingAs($user)
                ->post('/onboarding/path', ['path_id' => $path->id])
                ->assertRedirect(route('onboarding.skills'));

            $this->actingAs($user)
                ->post('/onboarding/skills/starting', ['starting_as' => 'experienced'])
                ->assertRedirect(route('onboarding.skills'));

            $response = $this->actingAs($user)
                ->get('/onboarding/skills')
                ->assertOk()
                ->assertSee('Skills for '.$path->path_name)
                ->assertSee('Add skill')
                ->assertDontSee('All skills')
                ->assertDontSee('Cybersecurity');

            foreach ($pathSkills as $name) {
                $response->assertSee($name);
            }
        } finally {
            $user->delete();
        }
    }

    public function test_custom_skill_can_still_be_added_during_onboarding(): void
    {
        $user = User::factory()->needsOnboarding()->create();
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);
        $customName = 'Custom Skill '.uniqid();

        try {
            $this->actingAs($user)->post('/onboarding/path', ['path_id' => $path->id]);
            $this->actingAs($user)->post('/onboarding/skills/starting', ['starting_as' => 'experienced']);
            $this->actingAs($user)
                ->post('/onboarding/skills', ['name' => $customName])
                ->assertRedirect(route('onboarding.skills'));

            $skill = Skill::query()->where('name', $customName)->first();
            $this->assertNotNull($skill);

            $this->actingAs($user)
                ->get('/onboarding/skills')
                ->assertOk()
                ->assertSee($customName);

            $this->actingAs($user)
                ->post('/onboarding/skills/continue', ['skill_ids' => [$skill->id]])
                ->assertRedirect(route('onboarding.confirm'));

            $this->actingAs($user)
                ->post('/onboarding/confirm')
                ->assertRedirect(route('dashboard'));

            $this->assertTrue($user->fresh()->skills()->where('name', $customName)->exists());
        } finally {
            $user->skills()->detach();
            $user->delete();
            Skill::query()->where('name', $customName)->whereDoesntHave('users')->delete();
        }
    }

    public function test_beginner_onboarding_saves_path_with_zero_skills_and_starts_at_step_one(): void
    {
        $user = User::factory()->needsOnboarding()->create([
            'xp' => 0,
            'level' => 1,
        ]);
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);
        $firstStep = $path->roadmapSteps()->orderBy('step_no')->orderBy('id')->first();
        $secondStep = $path->roadmapSteps()->orderBy('step_no')->orderBy('id')->skip(1)->first();
        $this->assertNotNull($firstStep);
        $this->assertNotNull($secondStep);

        try {
            $this->actingAs($user)->post('/onboarding/path', ['path_id' => $path->id]);

            $this->actingAs($user)
                ->get('/onboarding/skills')
                ->assertOk()
                ->assertSee("I'm a total beginner", false);

            $this->actingAs($user)
                ->post('/onboarding/skills/starting', ['starting_as' => 'beginner'])
                ->assertRedirect(route('onboarding.confirm'));

            $this->actingAs($user)
                ->get('/onboarding/confirm')
                ->assertOk()
                ->assertSee($path->path_name)
                ->assertSee('Starting from the beginning');

            $this->actingAs($user)
                ->post('/onboarding/confirm')
                ->assertRedirect(route('dashboard'));

            $user->refresh();
            $this->assertTrue($user->hasCompletedOnboarding());
            $this->assertSame((int) $path->id, (int) $user->path_id);
            $this->assertSame(0, $user->skills()->count());
            $this->assertSame(0, (int) $user->xp);
            $this->assertSame(1, (int) $user->level);
            $this->assertSame(0, $user->userProgress()->count());

            $dashboard = $this->actingAs($user)->get('/dashboard')->assertOk();
            $dashboard->assertSee($path->path_name)
                ->assertSee($firstStep->title)
                ->assertSee('0 / '.$path->roadmapSteps()->count())
                ->assertSee('0%');

            $roadmap = $this->actingAs($user)
                ->get('/roadmaps/'.$path->id)
                ->assertOk()
                ->assertSee($firstStep->title)
                ->assertSee($secondStep->title)
                ->assertSee('Locked');

            $this->assertSame((int) $firstStep->id, (int) $user->availableRoadmapStep($path)?->id);
        } finally {
            $user->userProgress()->delete();
            $user->userAchievements()->delete();
            $user->skills()->detach();
            $user->delete();
        }
    }

    public function test_experienced_users_cannot_continue_with_zero_skills_unless_beginner(): void
    {
        $user = User::factory()->needsOnboarding()->create();
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        try {
            $this->actingAs($user)->post('/onboarding/path', ['path_id' => $path->id]);
            $this->actingAs($user)->post('/onboarding/skills/starting', ['starting_as' => 'experienced']);
            $this->actingAs($user)
                ->post('/onboarding/skills/continue', ['skill_ids' => []])
                ->assertRedirect(route('onboarding.skills'))
                ->assertSessionHasErrors('skill_ids');
        } finally {
            $user->delete();
        }
    }

    public function test_roadmap_steps_are_linked_to_skills(): void
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $step = $path->roadmapSteps()->where('step_no', 1)->first();
        $this->assertNotNull($step);
        $this->assertTrue($step->skills()->pluck('name')->contains('HTML'));

        $user = User::factory()->create(['path_id' => $path->id]);

        try {
            $this->actingAs($user)
                ->get('/roadmaps/'.$path->id)
                ->assertOk()
                ->assertSee($step->title)
                ->assertSee('HTML');
        } finally {
            $user->delete();
        }
    }

    public function test_roadmap_unlocks_sequentially_and_rejects_out_of_order_completion(): void
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $steps = $path->roadmapSteps()->orderBy('step_no')->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(3, $steps->count());
        $first = $steps[0];
        $second = $steps[1];
        $third = $steps[2];

        $user = User::factory()->create([
            'path_id' => $path->id,
            'xp' => 0,
            'level' => 1,
        ]);

        try {
            $roadmap = $this->actingAs($user)->get('/roadmaps/'.$path->id)->assertOk();
            $roadmap->assertSee('Mark complete')->assertSee('Locked');

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $third]))
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $second]))
                ->assertForbidden();

            $this->assertSame(0, $user->userProgress()->count());
            $this->assertSame(0, (int) $user->fresh()->xp);

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $first]))
                ->assertRedirect(route('roadmaps.show', $path));

            $user->refresh();
            $this->assertSame((int) $first->xp_reward, (int) $user->xp);
            $this->assertSame((int) $second->id, (int) $user->availableRoadmapStep($path)?->id);

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $third]))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $second]))
                ->assertRedirect(route('roadmaps.show', $path));

            $user->refresh();
            $this->assertSame((int) ($first->xp_reward + $second->xp_reward), (int) $user->xp);
            $this->assertSame((int) $third->id, (int) $user->availableRoadmapStep($path)?->id);

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $third]))
                ->assertRedirect(route('roadmaps.show', $path));
        } finally {
            $user->userProgress()->delete();
            $user->userAchievements()->delete();
            $user->delete();
        }
    }

    public function test_completing_a_step_from_another_path_is_rejected(): void
    {
        $selected = LearningPath::query()->where('path_name', 'Web Development')->first();
        $other = LearningPath::query()->where('path_name', 'Cybersecurity')->first();
        $this->assertNotNull($selected);
        $this->assertNotNull($other);

        $foreignStep = $other->roadmapSteps()->orderBy('step_no')->first();
        $this->assertNotNull($foreignStep);

        $user = User::factory()->create([
            'path_id' => $selected->id,
            'xp' => 0,
            'level' => 1,
        ]);

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$other, $foreignStep]))
                ->assertForbidden();

            $this->assertFalse(
                UserProgress::query()
                    ->where('user_id', $user->id)
                    ->where('roadmap_step_id', $foreignStep->id)
                    ->exists()
            );

            $user->refresh();
            $this->assertSame(0, (int) $user->xp);
            $this->assertSame(1, (int) $user->level);
        } finally {
            $user->userProgress()->delete();
            $user->delete();
        }
    }

    public function test_duplicate_progress_is_prevented_and_xp_is_awarded_once(): void
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $step = $path->roadmapSteps()->orderBy('step_no')->first();
        $this->assertNotNull($step);
        $this->assertGreaterThan(0, (int) $step->xp_reward);

        $user = User::factory()->create([
            'path_id' => $path->id,
            'xp' => 0,
            'level' => 1,
        ]);

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect(route('roadmaps.show', $path));

            $user->refresh();
            $this->assertSame((int) $step->xp_reward, (int) $user->xp);
            $this->assertSame(1, UserProgress::query()
                ->where('user_id', $user->id)
                ->where('roadmap_step_id', $step->id)
                ->count());

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect(route('roadmaps.show', $path));

            $user->refresh();
            $this->assertSame((int) $step->xp_reward, (int) $user->xp);
            $this->assertSame(1, (int) $user->level);
            $this->assertSame(1, UserProgress::query()
                ->where('user_id', $user->id)
                ->where('roadmap_step_id', $step->id)
                ->count());

            $this->expectException(QueryException::class);
            UserProgress::query()->create([
                'user_id' => $user->id,
                'roadmap_step_id' => $step->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } finally {
            $user->userProgress()->delete();
            $user->userAchievements()->delete();
            $user->delete();
        }
    }

    public function test_roadmap_percent_is_step_based_and_independent_of_xp(): void
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $total = $path->roadmapSteps()->count();
        $this->assertSame(20, $total);

        $step = $path->roadmapSteps()->orderBy('step_no')->first();
        $this->assertNotNull($step);

        $user = User::factory()->create([
            'path_id' => $path->id,
            'xp' => 0,
            'level' => 1,
        ]);

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect();

            $user->refresh();
            $this->assertSame(50, (int) $step->xp_reward);
            $this->assertSame(50, (int) $user->xp);
            $this->assertSame(1, (int) $user->level);
            $this->assertSame(50, $user->xpIntoLevel());

            $expectedPercent = (int) round((1 / $total) * 100);
            $this->assertSame(5, $expectedPercent);
            $this->assertNotSame($expectedPercent, $user->xpIntoLevel());

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('1 / 20')
                ->assertSee('5%')
                ->assertSee('50 / 100 XP in this level')
                ->assertSee('Level')
                ->assertSee('1');
        } finally {
            $user->userProgress()->delete();
            $user->userAchievements()->delete();
            $user->delete();
        }
    }
}
