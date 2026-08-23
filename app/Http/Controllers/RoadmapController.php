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
            ->orderBy('path_name')
            ->get();

        return view('roadmaps.index', [
            'paths' => $paths,
            'selectedPathId' => auth()->user()->path_id,
        ]);
    }

    public function select(LearningPath $learningPath): RedirectResponse
    {
        $user = auth()->user();
        $user->path_id = $learningPath->id;
        $user->save();

        return redirect()
            ->route('roadmaps.show', $learningPath)
            ->with('success', 'Roadmap selected.');
    }

    public function show(LearningPath $learningPath): View
    {
        $steps = $learningPath->roadmapSteps()
            ->orderBy('step_no')
            ->get();

        $progressByStepId = auth()->user()
            ->userProgress()
            ->whereIn('roadmap_step_id', $steps->pluck('id'))
            ->get()
            ->keyBy('roadmap_step_id');

        return view('roadmaps.show', [
            'path' => $learningPath,
            'steps' => $steps,
            'progressByStepId' => $progressByStepId,
            'isSelected' => (int) auth()->user()->path_id === (int) $learningPath->id,
        ]);
    }

    public function complete(Request $request, LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        if ((int) $roadmapStep->path_id !== (int) $learningPath->id) {
            abort(404);
        }

        $user = $request->user();

        $progress = UserProgress::firstOrNew([
            'user_id' => $user->id,
            'roadmap_step_id' => $roadmapStep->id,
        ]);

        $alreadyCompleted = $progress->exists && $progress->status === 'completed';

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
