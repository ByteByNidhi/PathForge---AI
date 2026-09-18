<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RoadmapGenerationException;
use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Services\RoadmapGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoadmapController extends Controller
{
    public function index(): View
    {
        $paths = LearningPath::query()
            ->withCount([
                'roadmapSteps',
                'publishedRoadmapSteps',
                'draftRoadmapSteps',
            ])
            ->orderBy('path_name')
            ->get();

        return view('admin.roadmaps.index', [
            'paths' => $paths,
        ]);
    }

    public function create(): View
    {
        return view('admin.roadmaps.create', [
            'path' => new LearningPath,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPath($request);

        $path = LearningPath::query()->create([
            'path_name' => $validated['path_name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'roadmap_source' => LearningPath::SOURCE_CURATED,
            'is_published' => false,
        ]);

        return redirect()
            ->route('admin.roadmaps.show', $path)
            ->with('success', 'Draft career path created. Add steps manually or generate an AI draft, then publish before students can see it.');
    }

    public function show(LearningPath $learningPath): View
    {
        $steps = $learningPath->publishedRoadmapSteps()
            ->with('skills')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get();

        $draftSteps = $learningPath->draftRoadmapSteps()
            ->with('skills')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get();

        return view('admin.roadmaps.show', [
            'path' => $learningPath,
            'steps' => $steps,
            'draftSteps' => $draftSteps,
            'hasStudentProgress' => $learningPath->hasLiveStudentProgress(),
            'isBeginnerPath' => $learningPath->skills()->doesntExist(),
        ]);
    }

    public function generate(Request $request, LearningPath $learningPath, RoadmapGenerationService $generator): RedirectResponse
    {
        $beginner = $request->boolean('beginner') || $learningPath->skills()->doesntExist();

        try {
            $generator->generateDraft($learningPath, $beginner);
        } catch (RoadmapGenerationException $e) {
            return redirect()
                ->route('admin.roadmaps.show', $learningPath)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.roadmaps.preview', $learningPath)
            ->with('success', 'AI draft generated. Review it before publishing. Users cannot see this draft.');
    }

    public function preview(LearningPath $learningPath): View|RedirectResponse
    {
        $draftSteps = $learningPath->draftRoadmapSteps()
            ->with('skills')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get();

        if ($draftSteps->isEmpty()) {
            return redirect()
                ->route('admin.roadmaps.show', $learningPath)
                ->with('error', 'There is no AI draft to preview. Generate a roadmap first.');
        }

        return view('admin.roadmaps.preview', [
            'path' => $learningPath,
            'draftSteps' => $draftSteps,
            'hasStudentProgress' => $learningPath->hasLiveStudentProgress(),
        ]);
    }

    public function publish(LearningPath $learningPath, RoadmapGenerationService $generator): RedirectResponse
    {
        try {
            if ($learningPath->draftRoadmapSteps()->exists() && $this->hasAiDraftMetadata($learningPath)) {
                $generator->publishDraft($learningPath);
            } elseif ($learningPath->draftRoadmapSteps()->exists()) {
                $this->publishManualDrafts($learningPath);
            }

            $learningPath->refresh();

            if ($learningPath->publishedRoadmapSteps()->doesntExist()) {
                return redirect()
                    ->route('admin.roadmaps.show', $learningPath)
                    ->with('error', 'Add or generate roadmap steps before publishing this path.');
            }

            $learningPath->forceFill(['is_published' => true])->save();
        } catch (RoadmapGenerationException $e) {
            $fallback = $learningPath->hasAiDraft()
                ? route('admin.roadmaps.preview', $learningPath)
                : route('admin.roadmaps.show', $learningPath);

            return redirect()
                ->to($fallback)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', 'Career path published. Students can now see and select it.');
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
            'catalogSkills' => $this->catalogSkills(),
            'selectedSkillIds' => [],
        ]);
    }

    public function storeStep(Request $request, LearningPath $learningPath): RedirectResponse
    {
        $validated = $this->validatedStep($request);
        $skillIds = $this->uniqueSkillIds($validated['skill_ids'] ?? []);
        unset($validated['skill_ids']);

        $validated['path_id'] = $learningPath->id;
        $validated['is_published'] = $learningPath->isAvailableToStudents();

        $step = RoadmapStep::query()->create($validated);
        $step->skills()->sync($skillIds);

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', $validated['is_published']
                ? 'Roadmap step added.'
                : 'Draft roadmap step added. Publish the path when it is ready for students.');
    }

    public function editStep(LearningPath $learningPath, RoadmapStep $roadmapStep): View
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        return view('admin.roadmaps.step-form', [
            'path' => $learningPath,
            'step' => $roadmapStep->load('skills'),
            'catalogSkills' => $this->catalogSkills(),
            'selectedSkillIds' => $roadmapStep->skills->pluck('id')->all(),
        ]);
    }

    public function updateStep(Request $request, LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        $validated = $this->validatedStep($request);
        $skillIds = $this->uniqueSkillIds($validated['skill_ids'] ?? []);
        unset($validated['skill_ids']);

        $roadmapStep->update($validated);
        $roadmapStep->skills()->sync($skillIds);

        $destination = $roadmapStep->is_published
            ? route('admin.roadmaps.show', $learningPath)
            : ($learningPath->hasAiDraft()
                ? route('admin.roadmaps.preview', $learningPath)
                : route('admin.roadmaps.show', $learningPath));

        return redirect()
            ->to($destination)
            ->with('success', 'Roadmap step updated.');
    }

    public function destroyStep(LearningPath $learningPath, RoadmapStep $roadmapStep): RedirectResponse
    {
        $this->assertStepBelongsToPath($learningPath, $roadmapStep);

        $roadmapStep->skills()->detach();
        $roadmapStep->delete();

        return redirect()
            ->route('admin.roadmaps.show', $learningPath)
            ->with('success', 'Roadmap step deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPath(Request $request): array
    {
        $name = trim((string) $request->input('path_name', ''));
        $request->merge(['path_name' => $name]);

        $validated = $request->validate([
            'path_name' => [
                'required',
                'string',
                'min:2',
                'max:120',
                Rule::unique('learning_paths', 'path_name'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['path_name'] = trim($validated['path_name']);
        $validated['description'] = isset($validated['description'])
            ? (trim($validated['description']) !== '' ? trim($validated['description']) : null)
            : null;
        $validated['icon'] = isset($validated['icon'])
            ? (trim($validated['icon']) !== '' ? trim($validated['icon']) : null)
            : null;

        $duplicate = LearningPath::query()
            ->whereRaw('LOWER(path_name) = ?', [mb_strtolower($validated['path_name'])])
            ->exists();

        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'path_name' => 'A career path with this name already exists.',
            ]);
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedStep(Request $request): array
    {
        return $request->validate([
            'step_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);
    }

    /**
     * @param  list<mixed>  $skillIds
     * @return list<int>
     */
    private function uniqueSkillIds(array $skillIds): array
    {
        return array_values(array_unique(array_map('intval', $skillIds)));
    }

    private function catalogSkills()
    {
        return Skill::query()
            ->where('name', 'not like', 'achv-skill-%')
            ->orderBy('name')
            ->get();
    }

    private function hasAiDraftMetadata(LearningPath $path): bool
    {
        return filled($path->roadmap_draft_title) || $path->roadmap_generated_at !== null;
    }

    private function publishManualDrafts(LearningPath $path): void
    {
        DB::transaction(function () use ($path) {
            foreach ($path->draftRoadmapSteps()->orderBy('step_no')->orderBy('id')->get() as $step) {
                $step->is_published = true;
                $step->save();
            }
        });
    }

    private function assertStepBelongsToPath(LearningPath $learningPath, RoadmapStep $roadmapStep): void
    {
        if ((int) $roadmapStep->path_id !== (int) $learningPath->id) {
            abort(404);
        }
    }
}
