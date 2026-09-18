<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\UserProgress;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoadmapController extends Controller
{
    public function index(): View
    {
        $paths = LearningPath::query()
            ->availableToStudents()
            ->orderBy('path_name')
            ->get();

        return view('roadmaps.index', [
            'paths' => $paths,
            'selectedPathId' => auth()->user()->path_id,
        ]);
    }

    public function select(LearningPath $learningPath): RedirectResponse
    {
        if (! $learningPath->isAvailableToStudents()) {
            abort(404);
        }

        $user = auth()->user();
        $user->path_id = $learningPath->id;
        $user->save();

        return redirect()
            ->route('roadmaps.show', $learningPath)
            ->with('success', 'Roadmap selected.');
    }

    public function show(LearningPath $learningPath): View
    {
        if (! $learningPath->isAvailableToStudents()) {
            abort(404);
        }

        $steps = $learningPath->publishedRoadmapSteps()
            ->with('skills')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get();

        $user = auth()->user()->loadMissing('skills');
        $isSelected = (int) $user->path_id === (int) $learningPath->id;

        $progressByStepId = $user
            ->userProgress()
            ->whereIn('roadmap_step_id', $steps->pluck('id'))
            ->get()
            ->keyBy('roadmap_step_id');

        $completableStepIds = $isSelected
            ? $steps
                ->filter(fn (RoadmapStep $step) => $user->canCompleteRoadmapStep($step))
                ->pluck('id')
            : collect();

        return view('roadmaps.show', [
            'path' => $learningPath,
            'steps' => $steps,
            'progressByStepId' => $progressByStepId,
            'isSelected' => $isSelected,
            'completableStepIds' => $completableStepIds,
        ]);
    }

    public function complete(Request $request, LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        if ((int) $roadmapStep->path_id !== (int) $learningPath->id || ! $roadmapStep->is_published) {
            abort(404);
        }

        if (! $learningPath->isAvailableToStudents()) {
            abort(404);
        }

        $user = $request->user();

        if ((int) $user->path_id !== (int) $learningPath->id) {
            abort(403, 'You can only complete steps on your selected career path.');
        }

        $progress = UserProgress::firstOrNew([
            'user_id' => $user->id,
            'roadmap_step_id' => $roadmapStep->id,
        ]);

        $alreadyCompleted = $progress->exists && $progress->status === 'completed';

        if (! $alreadyCompleted && ! $user->canCompleteRoadmapStep($roadmapStep)) {
            abort(403, 'Complete your current roadmap step first.');
        }

        $progress->status = 'completed';
        $progress->completed_at = $progress->completed_at ?? now();
        $progress->save();

        if (! $alreadyCompleted) {
            $user->addXp((int) $roadmapStep->xp_reward);
            app(AchievementService::class)->checkAndUnlock($user);
        }

        return redirect()
            ->route('roadmaps.show', $learningPath)
            ->with('success', $alreadyCompleted ? 'Step already completed.' : 'Step marked complete.');
    }
}
