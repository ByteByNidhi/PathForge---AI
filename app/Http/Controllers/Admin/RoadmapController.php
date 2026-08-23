<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\RoadmapStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoadmapController extends Controller
{
    public function index(): View
    {
        $paths = LearningPath::query()
            ->withCount('roadmapSteps')
            ->orderBy('path_name')
            ->get();

        return view('admin.roadmaps.index', [
            'paths' => $paths,
        ]);
    }

    public function show(LearningPath $learningPath): View
    {
        $steps = $learningPath->roadmapSteps()
            ->orderBy('step_no')
            ->orderBy('id')
            ->get();

        return view('admin.roadmaps.show', [
            'path' => $learningPath,
            'steps' => $steps,
        ]);
    }

    public function createStep(LearningPath $learningPath): View
    {
        $nextStepNo = ((int) $learningPath->roadmapSteps()->max('step_no')) + 1;

        return view('admin.roadmaps.step-form', [
            'path' => $learningPath,
            'step' => new RoadmapStep([
                'step_no' => $nextStepNo,
                'xp_reward' => 10,
            ]),
        ]);
    }

    public function storeStep(Request $request, LearningPath $learningPath): RedirectResponse
    {
        $validated = $this->validatedStep($request);
        $validated['path_id'] = $learningPath->id;

        RoadmapStep::query()->create($validated);

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', 'Roadmap step added.');
    }

    public function editStep(LearningPath $learningPath, RoadmapStep $roadmapStep): View
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        return view('admin.roadmaps.step-form', [
            'path' => $learningPath,
            'step' => $roadmapStep,
        ]);
    }

    public function updateStep(Request $request, LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        $roadmapStep->update($this->validatedStep($request));

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', 'Roadmap step updated.');
    }

    public function destroyStep(LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        $roadmapStep->delete();

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', 'Roadmap step deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedStep(Request $request): array
    {
        return $request->validate([
            'step_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'xp_reward' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function assertStepBelongsToPath(LearningPath $learningPath, RoadmapStep $roadmapStep): void
    {
        if ((int) $roadmapStep->path_id !== (int) $learningPath->id) {
            abort(404);
        }
    }
}
