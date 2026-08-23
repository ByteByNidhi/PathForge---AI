<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['skills', 'learningPath']);

        return view('profile', [
            'user' => $user,
            'skills' => $user->skills()->orderBy('name')->get(),
        ]);
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $skill = Skill::findOrCreateByName($validated['name']);
        $user = $request->user();

        if ($user->skills()->where('skills.id', $skill->id)->exists()) {
            return back()
                ->withErrors(['name' => 'You already have this skill.'])
                ->withInput();
        }

        $user->skills()->attach($skill->id);
        app(AchievementService::class)->checkAndUnlock($user);

        return back()->with('success', 'Skill added.');
    }

    public function destroySkill(Request $request, Skill $skill): RedirectResponse
    {
        $request->user()->skills()->detach($skill->id);

        return back()->with('success', 'Skill removed.');
    }
}
