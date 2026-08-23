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

        $request->session()->put('onboarding.path_id', (int) $validated['path_id']);

        return redirect()->route('onboarding.skills');
    }

    public function skills(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        if (! $this->selectedPath($request)) {
            return redirect()->route('onboarding.show');
        }

        $catalog = Skill::query()
            ->where('name', 'not like', 'achv-skill-%')
            ->orderBy('name')
            ->get();
        $selectedIds = $this->selectedSkillIds($request);
        $selectedSkills = Skill::query()
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get();

        return view('onboarding.skills', [
            'catalog' => $catalog,
            'selectedIds' => $selectedIds,
            'selectedSkills' => $selectedSkills,
        ]);
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        if (! $this->selectedPath($request)) {
            return redirect()->route('onboarding.show');
        }

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

        $validated = $request->validate([
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['skill_ids'] ?? [])));
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

        $skills = Skill::query()
            ->whereIn('id', $this->selectedSkillIds($request))
            ->orderBy('name')
            ->get();

        return view('onboarding.confirm', [
            'path' => $path,
            'skills' => $skills,
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

        $skillIds = $this->selectedSkillIds($request);

        $user->path_id = $path->id;
        $user->onboarding_completed = true;
        $user->save();

        $user->skills()->sync($skillIds);
        app(AchievementService::class)->checkAndUnlock($user);

        $request->session()->forget(['onboarding.path_id', 'onboarding.skill_ids']);

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

    /**
     * @return list<int>
     */
    private function selectedSkillIds(Request $request): array
    {
        $ids = $request->session()->get('onboarding.skill_ids', []);

        return array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
    }
}
