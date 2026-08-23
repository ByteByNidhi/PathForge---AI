<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Skill;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    public function test_guests_cannot_open_achievements(): void
    {
        $this->get('/achievements')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_locked_catalog_and_progress(): void
    {
        $this->assertCatalogSeeded();

        $user = User::factory()->create();

        try {
            $this->actingAs($user)
                ->get('/achievements')
                ->assertOk()
                ->assertSee('Achievements')
                ->assertSee('You have not unlocked any badges yet')
                ->assertSee('PATH IGNITED')
                ->assertSee('TRAILBLAZER')
                ->assertSee('SUMMIT SEEKER')
                ->assertSee('SKILLFORGED')
                ->assertSee('XP OVERDRIVE')
                ->assertSee('ASCENDANT')
                ->assertSee('Rarity: Common')
                ->assertSee('Rarity: Rare')
                ->assertSee('Rarity: Epic')
                ->assertSee('Rarity: Legendary')
                ->assertSee('Complete 1 roadmap step')
                ->assertSee('Progress: 0 / 1')
                ->assertSee('Progress: 0 / 10')
                ->assertSee('Progress: 0 / 5')
                ->assertSee('Progress: 0 / 500');
        } finally {
            $this->cleanupUser($user);
        }
    }

    public function test_completing_one_step_unlocks_path_ignited_once(): void
    {
        $this->assertCatalogSeeded();

        $path = $this->makePathWithSteps(10, 0);
        $user = User::factory()->create(['path_id' => $path->id]);
        $step = $path->roadmapSteps()->orderBy('step_no')->first();

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect(route('roadmaps.show', $path));

            $this->assertUnlocked($user, 'path-ignited');
            $this->assertNotUnlocked($user, 'trailblazer');
            $this->assertNotUnlocked($user, 'summit-seeker');

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect();

            $this->assertSame(1, $this->unlockCount($user, 'path-ignited'));

            $unlock = $this->unlock($user, 'path-ignited');
            $this->assertNotNull($unlock->unlocked_at);

            $this->actingAs($user)
                ->get('/achievements')
                ->assertOk()
                ->assertSee('PATH IGNITED')
                ->assertSee('Unlocked')
                ->assertDontSee('You have not unlocked any badges yet');
        } finally {
            $this->cleanupUser($user, $path);
        }
    }

    public function test_completing_ten_steps_unlocks_trailblazer(): void
    {
        $this->assertCatalogSeeded();

        $path = $this->makePathWithSteps(20, 0);
        $user = User::factory()->create(['path_id' => $path->id]);
        $steps = $path->roadmapSteps()->orderBy('step_no')->get();

        try {
            foreach ($steps->take(9) as $step) {
                $this->actingAs($user)
                    ->post(route('roadmaps.complete', [$path, $step]));
            }

            $this->assertUnlocked($user, 'path-ignited');
            $this->assertNotUnlocked($user, 'trailblazer');

            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $steps[9]]))
                ->assertRedirect();

            $this->assertUnlocked($user, 'trailblazer');
            $this->assertUnlocked($user, 'summit-seeker');
        } finally {
            $this->cleanupUser($user, $path);
        }
    }

    public function test_fifty_percent_roadmap_completion_unlocks_summit_seeker(): void
    {
        $this->assertCatalogSeeded();

        $path = $this->makePathWithSteps(2, 0);
        $user = User::factory()->create(['path_id' => $path->id]);
        $step = $path->roadmapSteps()->orderBy('step_no')->first();

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect();

            $this->assertUnlocked($user, 'path-ignited');
            $this->assertUnlocked($user, 'summit-seeker');
            $this->assertSame(1, $this->unlockCount($user, 'summit-seeker'));
        } finally {
            $this->cleanupUser($user, $path);
        }
    }

    public function test_adding_five_skills_unlocks_skillforged(): void
    {
        $this->assertCatalogSeeded();

        $user = User::factory()->create();
        $prefix = 'achv-skill-'.uniqid();

        try {
            for ($i = 1; $i <= 4; $i++) {
                $this->actingAs($user)
                    ->post('/profile/skills', ['name' => $prefix.'-'.$i])
                    ->assertRedirect();
            }

            $this->assertNotUnlocked($user, 'skillforged');

            $this->actingAs($user)
                ->get('/achievements')
                ->assertOk()
                ->assertSee('Progress: 4 / 5');

            $this->actingAs($user)
                ->post('/profile/skills', ['name' => $prefix.'-5'])
                ->assertRedirect();

            $this->assertUnlocked($user, 'skillforged');
            $this->assertSame(1, $this->unlockCount($user, 'skillforged'));
        } finally {
            $this->cleanupUser($user);
        }
    }

    public function test_xp_and_level_milestones_can_unlock_together(): void
    {
        $this->assertCatalogSeeded();

        $path = $this->makePathWithSteps(10, 0);
        $user = User::factory()->create([
            'path_id' => $path->id,
            'xp' => 0,
            'level' => 1,
        ]);
        $step = $path->roadmapSteps()->orderBy('step_no')->first();
        $step->update(['xp_reward' => 500]);

        try {
            $this->actingAs($user)
                ->post(route('roadmaps.complete', [$path, $step]))
                ->assertRedirect();

            $user->refresh();
            $this->assertSame(500, (int) $user->xp);
            $this->assertSame(6, (int) $user->level);

            $this->assertUnlocked($user, 'path-ignited');
            $this->assertUnlocked($user, 'xp-overdrive');
            $this->assertUnlocked($user, 'ascendant');
            $this->assertNotUnlocked($user, 'trailblazer');
            $this->assertNotUnlocked($user, 'summit-seeker');
        } finally {
            $this->cleanupUser($user, $path);
        }
    }

    public function test_reaching_level_five_unlocks_ascendant_without_xp_overdrive(): void
    {
        $this->assertCatalogSeeded();

        $user = User::factory()->create([
            'xp' => 0,
            'level' => 1,
        ]);

        try {
            $user->addXp(400);
            $user->refresh();

            $this->assertSame(5, (int) $user->level);
            $this->assertUnlocked($user, 'ascendant');
            $this->assertNotUnlocked($user, 'xp-overdrive');
        } finally {
            $this->cleanupUser($user);
        }
    }

    private function assertCatalogSeeded(): void
    {
        $this->assertSame(
            6,
            Achievement::query()->count(),
            'Achievement catalog is missing. Run migrations so the six badges exist.'
        );
    }

    private function makePathWithSteps(int $count, int $xpReward): LearningPath
    {
        $path = LearningPath::query()->create([
            'path_name' => 'Achievement Fixture '.uniqid(),
            'description' => 'Temporary path for achievement tests',
        ]);

        for ($i = 1; $i <= $count; $i++) {
            RoadmapStep::query()->create([
                'path_id' => $path->id,
                'step_no' => $i,
                'title' => 'Achievement step '.$i,
                'xp_reward' => $xpReward,
            ]);
        }

        return $path->fresh();
    }

    private function unlock(User $user, string $slug): ?UserAchievement
    {
        $achievement = Achievement::query()->where('slug', $slug)->first();
        $this->assertNotNull($achievement);

        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();
    }

    private function unlockCount(User $user, string $slug): int
    {
        $achievement = Achievement::query()->where('slug', $slug)->first();
        $this->assertNotNull($achievement);

        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->count();
    }

    private function assertUnlocked(User $user, string $slug): void
    {
        $this->assertNotNull($this->unlock($user, $slug), "Expected {$slug} to be unlocked.");
    }

    private function assertNotUnlocked(User $user, string $slug): void
    {
        $this->assertNull($this->unlock($user, $slug), "Did not expect {$slug} to be unlocked.");
    }

    private function cleanupUser(User $user, ?LearningPath $path = null): void
    {
        UserAchievement::query()->where('user_id', $user->id)->delete();
        $user->userProgress()->delete();
        $user->skills()->detach();
        Skill::query()
            ->where('name', 'like', 'achv-skill-%')
            ->whereDoesntHave('users')
            ->delete();
        $user->delete();

        if ($path) {
            $path->delete();
        }
    }
}
