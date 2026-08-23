<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AchievementService
{
    /**
     * Evaluate every achievement and unlock those whose milestones are met.
     * Multiple badges may unlock from a single check. Existing unlocks are never duplicated.
     *
     * @return list<Achievement>
     */
    public function checkAndUnlock(User $user): array
    {
        $unlocked = [];

        foreach (Achievement::query()->orderBy('id')->get() as $achievement) {
            if ($this->currentValue($user, $achievement) < (int) $achievement->condition_value) {
                continue;
            }

            if ($this->unlock($user, $achievement)) {
                $unlocked[] = $achievement;
            }
        }

        return $unlocked;
    }

    /**
     * @return Collection<int, array{
     *     achievement: Achievement,
     *     unlocked: bool,
     *     unlocked_at: \Illuminate\Support\Carbon|null,
     *     current: int,
     *     target: int,
     *     progress_percent: int
     * }>
     */
    public function catalogFor(User $user): Collection
    {
        $unlocks = UserAchievement::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('achievement_id');

        return Achievement::query()
            ->orderBy('id')
            ->get()
            ->map(function (Achievement $achievement) use ($user, $unlocks) {
                $current = $this->currentValue($user, $achievement);
                $target = (int) $achievement->condition_value;
                $pivot = $unlocks->get($achievement->id);

                return [
                    'achievement' => $achievement,
                    'unlocked' => $pivot !== null,
                    'unlocked_at' => $pivot?->unlocked_at,
                    'current' => $current,
                    'target' => $target,
                    'progress_percent' => $target > 0
                        ? (int) min(100, round(($current / $target) * 100))
                        : 0,
                ];
            });
    }

    public function unlock(User $user, Achievement $achievement): bool
    {
        try {
            return DB::transaction(function () use ($user, $achievement) {
                $existing = UserAchievement::query()
                    ->where('user_id', $user->id)
                    ->where('achievement_id', $achievement->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return false;
                }

                UserAchievement::query()->create([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now(),
                ]);

                return true;
            });
        } catch (Throwable) {
            return false;
        }
    }

    private function currentValue(User $user, Achievement $achievement): int
    {
        return match ($achievement->condition_type) {
            'completed_steps' => $this->completedStepCount($user),
            'roadmap_percent' => $this->selectedRoadmapPercent($user),
            'skills_count' => $user->skills()->count(),
            'xp' => (int) ($user->xp ?? 0),
            'level' => (int) ($user->level ?? 1),
            default => 0,
        };
    }

    private function completedStepCount(User $user): int
    {
        return $user->userProgress()
            ->where('status', 'completed')
            ->count();
    }

    private function selectedRoadmapPercent(User $user): int
    {
        $path = $user->learningPath;

        if (! $path) {
            return 0;
        }

        $stepIds = $path->roadmapSteps()->pluck('id');
        $total = $stepIds->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $user->userProgress()
            ->whereIn('roadmap_step_id', $stepIds)
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}
