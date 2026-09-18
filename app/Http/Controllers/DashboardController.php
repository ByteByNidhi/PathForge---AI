<?php

namespace App\Http\Controllers;

use App\Models\CareerPathRequest;
use App\Models\Opportunity;
use App\Services\AchievementService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AchievementService $achievements): View
    {
        $user = auth()->user()->load(['skills', 'learningPath']);
        $path = $user->learningPath;
        $pendingCareerPathRequest = $user->careerPathRequests()
            ->where('status', CareerPathRequest::STATUS_PENDING)
            ->latest()
            ->first();

        $totalSteps = 0;
        $completedSteps = 0;
        $progressPercent = 0;
        $currentStep = null;
        $recentCompletions = collect();

        if ($path) {
            $steps = $path->publishedRoadmapSteps()->orderBy('step_no')->get();
            $totalSteps = $steps->count();
            $progressRecords = $user->userProgress()
                ->whereIn('roadmap_step_id', $steps->pluck('id'))
                ->get();
            $completedIds = $progressRecords
                ->where('status', 'completed')
                ->pluck('roadmap_step_id');
            $completedSteps = $completedIds->count();
            $progressPercent = $totalSteps > 0
                ? (int) round(($completedSteps / $totalSteps) * 100)
                : 0;
            $currentStep = $user->availableRoadmapStep($path);
            $recentCompletions = $progressRecords
                ->where('status', 'completed')
                ->sortByDesc(fn ($row) => $row->completed_at ?? $row->updated_at)
                ->take(5)
                ->values();
        }

        $skillNames = $user->skills->pluck('name')->all();
        $recommendedOpportunities = Opportunity::query()
            ->visibleToStudents()
            ->with('skills')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (Opportunity $opportunity) use ($skillNames) {
                $opportunity->setAttribute('skill_match', $opportunity->skillMatch($skillNames));

                return $opportunity;
            })
            ->sortBy(function (Opportunity $opportunity) {
                $percent = $opportunity->skill_match['percent'] ?? -1;

                return [
                    -$percent,
                    -($opportunity->created_at?->timestamp ?? 0),
                    $opportunity->deadline?->timestamp ?? PHP_INT_MAX,
                ];
            })
            ->take(3)
            ->values();

        return view('dashboard', [
            'user' => $user,
            'path' => $path,
            'totalSteps' => $totalSteps,
            'completedSteps' => $completedSteps,
            'progressPercent' => $progressPercent,
            'currentStep' => $currentStep,
            'skills' => $user->skills,
            'achievements' => $achievements->catalogFor($user)->take(4),
            'recommendedOpportunities' => $recommendedOpportunities,
            'recentCompletions' => $recentCompletions,
            'xpIntoLevel' => $user->xpIntoLevel(),
            'pendingCareerPathRequest' => $pendingCareerPathRequest,
        ]);
    }
}
