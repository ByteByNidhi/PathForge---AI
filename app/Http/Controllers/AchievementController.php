<?php

namespace App\Http\Controllers;

use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function index(Request $request, AchievementService $achievements): View
    {
        $catalog = $achievements->catalogFor($request->user());

        return view('achievements.index', [
            'unlocked' => $catalog->where('unlocked', true)->values(),
            'locked' => $catalog->where('unlocked', false)->values(),
        ]);
    }
}
