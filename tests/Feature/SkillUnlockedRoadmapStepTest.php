<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProgress;
use Tests\TestCase;

class SkillUnlockedRoadmapStepTest extends TestCase
{
    public function test_user_without_matching_skill_cannot_bypass_sequence(): void
    {
        [$user, $path, $first, $later, $unrelated] = $this->webDevFixture();

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $later]))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('roadmaps.show', $path))
                ->assertOk()
                ->assertSee('Locked');

            $this->assertFalse($user->fresh()->canCompleteRoadmapStep($later));
            $this->assertSame((int) $first->id, (int) $user->availableRoadmapStep($path)?->id);
            $this->assertFalse($user->fresh()->canCompleteRoadmapStep($unrelated));
            $this->assertSame(0, (int) $user->fresh()->xp);
        } finally {
            $this->cleanupUser($user);
        }
    }

    public function test_post_onboarding_skill_unlocks_matching_later_step_for_completion_and_xp_once(): void
    {
        [$user, $path, $first, $later, $unrelated] = $this->webDevFixture();
        $git = Skill::query()->where('name', 'Git')->first();
        $this->assertNotNull($git);

        try {
            $this->actingAs($user)
                ->post('/profile/skills', ['name' => 'Git'])
                ->assertRedirect();

            $this->assertSame(1, $user->fresh()->skills()->where('skills.id', $git->id)->count());
            $this->assertSame(0, $user->userProgress()->count());
            $this->assertSame(0, (int) $user->fresh()->xp);

            $user->refresh()->load('skills');
            $this->assertTrue($user->canCompleteRoadmapStep($later));
            $this->assertFalse($user->canCompleteRoadmapStep($unrelated));
            $this->assertSame((int) $first->id, (int) $user->availableRoadmapStep($path)?->id);

            $roadmap = $this->actingAs($user)->get(route('roadmaps.show', $path))->assertOk();
            $roadmap->assertSee($later->title);
            $html = $roadmap->getContent();
            $this->assertTrue(str_contains(
                $html,
                '/roadmaps/'.$path->id.'/steps/'.$later->id.'/complete'
            ));

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $unrelated]))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $later]))
                ->assertRedirect(route('roadmaps.show', $path));

            $user->refresh();
            $this->assertSame((int) $later->xp_reward, (int) $user->xp);
            $this->assertSame(1, UserProgress::query()
                ->where('user_id', $user->id)
                ->where('roadmap_step_id', $later->id)
                ->where('status', 'completed')
                ->count());

            $total = $path->publishedRoadmapSteps()->count();
            $expectedPercent = (int) round((1 / $total) * 100);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('1 / '.$total)
                ->assertSee($expectedPercent.'%');

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $later]))
                ->assertRedirect(route('roadmaps.show', $path));

            $this->assertSame((int) $later->xp_reward, (int) $user->fresh()->xp);
            $this->assertSame(1, UserProgress::query()
                ->where('user_id', $user->id)
                ->where('roadmap_step_id', $later->id)
                ->count());

            $this->actingAs($user)
                ->from('/profile')
                ->post('/profile/skills', ['name' => 'git'])
                ->assertRedirect('/profile')
                ->assertSessionHasErrors('name');

            $this->assertSame(1, $user->fresh()->skills()->where('skills.id', $git->id)->count());
        } finally {
            $this->cleanupUser($user);
        }
    }

    public function test_onboarding_skill_selection_still_starts_at_the_first_incomplete_step(): void
    {
        $user = User::factory()->needsOnboarding()->create([
            'xp' => 0,
            'level' => 1,
        ]);
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);
        $html = Skill::query()->where('name', 'HTML')->first();
        $this->assertNotNull($html);
        $first = $path->publishedRoadmapSteps()->orderBy('step_no')->orderBy('id')->first();
        $this->assertNotNull($first);

        try {
            $this->actingAs($user)->post('/onboarding/path', ['path_id' => $path->id]);
            $this->actingAs($user)->post('/onboarding/skills/starting', ['starting_as' => 'experienced']);
            $this->actingAs($user)
                ->post('/onboarding/skills/continue', ['skill_ids' => [$html->id]])
                ->assertRedirect(route('onboarding.confirm'));
            $this->actingAs($user)
                ->post('/onboarding/confirm')
                ->assertRedirect(route('dashboard'));

            $user->refresh()->load('skills');
            $this->assertTrue($user->hasCompletedOnboarding());
            $this->assertTrue($user->skills()->where('skills.id', $html->id)->exists());
            $this->assertSame(0, $user->userProgress()->count());
            $this->assertSame(0, (int) $user->xp);
            $this->assertSame((int) $first->id, (int) $user->availableRoadmapStep($path)?->id);
            $this->assertTrue($user->canCompleteRoadmapStep($first));
        } finally {
            $this->cleanupUser($user);
        }
    }

    /**
     * @return array{0: User, 1: LearningPath, 2: RoadmapStep, 3: RoadmapStep, 4: RoadmapStep}
     */
    private function webDevFixture(): array
    {
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        $steps = $path->publishedRoadmapSteps()->with('skills')->orderBy('step_no')->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(3, $steps->count());

        $first = $steps->first();
        $later = $steps->first(
            fn (RoadmapStep $step) => $step->skills->count() === 1 && $step->skills->first()?->name === 'Git'
        );
        $unrelated = $steps->first(
            fn (RoadmapStep $step) => $step->skills->count() === 1 && $step->skills->first()?->name === 'React'
        );

        $this->assertNotNull($later, 'Web Development must have a Git-only step.');
        $this->assertNotNull($unrelated, 'Web Development must have a React-only step.');
        $this->assertNotSame((int) $first->id, (int) $later->id);

        $user = User::factory()->create([
            'path_id' => $path->id,
            'xp' => 0,
            'level' => 1,
        ]);

        return [$user, $path, $first, $later, $unrelated];
    }

    private function cleanupUser(User $user): void
    {
        $user->userProgress()->delete();
        $user->userAchievements()->delete();
        $user->skills()->detach();
        $user->delete();
    }
}
