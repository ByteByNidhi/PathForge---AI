<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('learningPath')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['learningPath', 'skills']);

        $completedSteps = 0;
        $totalSteps = 0;

        if ($user->learningPath) {
            $stepIds = $user->learningPath->roadmapSteps()->pluck('id');
            $totalSteps = $stepIds->count();
            $completedSteps = $user->userProgress()
                ->whereIn('roadmap_step_id', $stepIds)
                ->where('status', 'completed')
                ->count();
        }

        return view('admin.users.show', [
            'user' => $user,
            'completedSteps' => $completedSteps,
            'totalSteps' => $totalSteps,
        ]);
    }
}
