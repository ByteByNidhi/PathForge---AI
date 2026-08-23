<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\Opportunity;
use App\Models\RoadmapStep;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::query()->count();
        $totalOpportunities = Opportunity::query()->count();
        $totalCareerPaths = LearningPath::query()->count();
        $totalRoadmapSteps = RoadmapStep::query()->count();

        $usersWithRoadmap = User::query()->whereNotNull('path_id')->count();
        $adminCount = User::query()->where('is_admin', true)->count();
        $completedSteps = UserProgress::query()->where('status', 'completed')->count();

        $today = now()->startOfDay();
        $openOpportunities = Opportunity::query()
            ->where(function ($query) use ($today) {
                $query->whereNull('deadline')
                    ->orWhereDate('deadline', '>=', $today->toDateString());
            })
            ->count();
        $closedOpportunities = Opportunity::query()
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today->toDateString())
            ->count();

        $recentUsers = User::query()
            ->with('learningPath')
            ->latest()
            ->limit(5)
            ->get();

        $recentOpportunities = Opportunity::query()
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalOpportunities' => $totalOpportunities,
            'totalCareerPaths' => $totalCareerPaths,
            'totalRoadmapSteps' => $totalRoadmapSteps,
            'usersWithRoadmap' => $usersWithRoadmap,
            'adminCount' => $adminCount,
            'completedSteps' => $completedSteps,
            'openOpportunities' => $openOpportunities,
            'closedOpportunities' => $closedOpportunities,
            'recentUsers' => $recentUsers,
            'recentOpportunities' => $recentOpportunities,
        ]);
    }
}
