<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Skill;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $paths = LearningPath::query()
            ->orderBy('path_name')
            ->get();

        return view('onboarding.path', [
            'paths' => $paths,
            'selectedPathId' => $request->session()->get('onboarding.path_id'),
        ]);
    }

    public function storePath(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path_id' => ['required', 'integer', 'exists:learning_paths,id'],
        ]);

        $pathId = (int) $validated['path_id'];
        $previousPathId = (int) $request->session()->get('onboarding.path_id', 0);

        if ($previousPathId !== 0 && $previousPathId !== $pathId) {
            $request->session()->forget(['onboarding.skill_ids', 'onboarding.starting_as']);
        }

        $request->session()->put('onboarding.path_id', $pathId);

        return redirect()->route('onboarding.skills');
    }

    public function storeStartingPoint(Request $request): RedirectResponse
    {
        if (! $this->selectedPath($request)) {
            return redirect()->route('onboarding.show');
        }

        $validated = $request->validate([
            'starting_as' => ['required', 'in:experienced,beginner'],
        ]);

        $request->session()->put('onboarding.starting_as', $validated['starting_as']);

        if ($validated['starting_as'] === 'beginner') {
            $request->session()->put('onboarding.skill_ids', []);

            return redirect()->route('onboarding.confirm');
        }

        return redirect()->route('onboarding.skills');
    }

    public function skills(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $path = $this->selectedPath($request);

        if (! $path) {
            return redirect()->route('onboarding.show');
        }

        $pathSkills = $path->skills()
            ->where('name', 'not like', 'achv-skill-%')
            ->orderBy('name')
            ->get();
        $selectedIds = $this->selectedSkillIds($request);
        $selectedSkills = Skill::query()
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get();

        return view('onboarding.skills', [
            'path' => $path,
            'pathSkills' => $pathSkills,
            'selectedIds' => $selectedIds,
            'selectedSkills' => $selectedSkills,
            'startingAs' => $this->startingAs($request),
        ]);
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        if (! $this->selectedPath($request)) {
            return redirect()->route('onboarding.show');
        }

        $request->session()->put('onboarding.starting_as', 'experienced');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $skill = Skill::findOrCreateByName($validated['name']);
        $ids = $this->selectedSkillIds($request);

        if (! in_array($skill->id, $ids, true)) {
            $ids[] = $skill->id;
            $request->session()->put('onboarding.skill_ids', $ids);
        }

        return redirect()->route('onboarding.skills');
    }

    public function toggleSkills(Request $request): RedirectResponse
    {
        if (! $this->selectedPath($request)) {
            return redirect()->route('onboarding.show');
        }

        if ($this->startingAs($request) === 'beginner') {
            $request->session()->put('onboarding.skill_ids', []);

            return redirect()->route('onboarding.confirm');
        }

        $validated = $request->validate([
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['skill_ids'] ?? [])));

        if ($ids === []) {
            $request->session()->put('onboarding.starting_as', 'experienced');

            return redirect()
                ->route('onboarding.skills')
                ->withErrors([
                    'skill_ids' => 'Select at least one skill, or choose “I\'m a total beginner” if you are starting from the first step.',
                ]);
        }

        $request->session()->put('onboarding.starting_as', 'experienced');
        $request->session()->put('onboarding.skill_ids', $ids);

        return redirect()->route('onboarding.confirm');
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $path = $this->selectedPath($request);

        if (! $path) {
            return redirect()->route('onboarding.show');
        }

        if ($this->startingAs($request) !== 'beginner' && $this->selectedSkillIds($request) === []) {
            return redirect()->route('onboarding.skills');
        }

        $skills = Skill::query()
            ->whereIn('id', $this->selectedSkillIds($request))
            ->orderBy('name')
            ->get();

        return view('onboarding.confirm', [
            'path' => $path,
            'skills' => $skills,
            'isBeginner' => $this->startingAs($request) === 'beginner',
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $path = $this->selectedPath($request);

        if (! $path) {
            return redirect()->route('onboarding.show');
        }

        $skillIds = $this->startingAs($request) === 'beginner'
            ? []
            : $this->selectedSkillIds($request);

        if ($this->startingAs($request) !== 'beginner' && $skillIds === []) {
            return redirect()->route('onboarding.skills');
        }

        $user->path_id = $path->id;
        $user->onboarding_completed = true;
        $user->save();

        $user->skills()->sync($skillIds);
        app(AchievementService::class)->checkAndUnlock($user);

        $request->session()->forget(['onboarding.path_id', 'onboarding.skill_ids', 'onboarding.starting_as']);

        return redirect()->route('dashboard');
    }

    private function selectedPath(Request $request): ?LearningPath
    {
        $pathId = $request->session()->get('onboarding.path_id');

        if (! $pathId) {
            return null;
        }

        return LearningPath::query()->find($pathId);
    }

    private function startingAs(Request $request): ?string
    {
        $value = $request->session()->get('onboarding.starting_as');

        return in_array($value, ['experienced', 'beginner'], true) ? $value : null;
    }

    /**
     * @return list<int>
     */
    private function selectedSkillIds(Request $request): array
    {
        $ids = $request->session()->get('onboarding.skill_ids', []);

        return array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
    }
}
