<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OpportunityController as AdminOpportunityController;
use App\Http\Controllers\Admin\RoadmapController as AdminRoadmapController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AiStudioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RoadmapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'onboarded'])
    ->name('dashboard');

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'show'])->name('show');
    Route::post('/path', [OnboardingController::class, 'storePath'])->name('path.store');
    Route::get('/skills', [OnboardingController::class, 'skills'])->name('skills');
    Route::post('/skills', [OnboardingController::class, 'storeSkill'])->name('skills.store');
    Route::post('/skills/continue', [OnboardingController::class, 'toggleSkills'])->name('skills.continue');
    Route::get('/confirm', [OnboardingController::class, 'confirm'])->name('confirm');
    Route::post('/confirm', [OnboardingController::class, 'complete'])->name('complete');
});

Route::middleware(['auth', 'onboarded'])->group(function () {
    Route::get('/ai-studio', [AiStudioController::class, 'show'])->name('ai-studio');
    Route::post('/ai-studio/chat', [AiStudioController::class, 'chat'])->name('ai-studio.chat');

    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/skills', [ProfileController::class, 'storeSkill'])->name('profile.skills.store');
    Route::post('/profile/skills/{skill}/remove', [ProfileController::class, 'destroySkill'])->name('profile.skills.destroy');

    Route::get('/roadmaps', [RoadmapController::class, 'index'])->name('roadmaps.index');
    Route::post('/roadmaps/{learningPath}/select', [RoadmapController::class, 'select'])->name('roadmaps.select');
    Route::get('/roadmaps/{learningPath}', [RoadmapController::class, 'show'])->name('roadmaps.show');
    Route::post('/roadmaps/{learningPath}/steps/{roadmapStep}/complete', [RoadmapController::class, 'complete'])->name('roadmaps.complete');

    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/opportunities', [AdminOpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/create', [AdminOpportunityController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [AdminOpportunityController::class, 'store'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}/edit', [AdminOpportunityController::class, 'edit'])->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [AdminOpportunityController::class, 'update'])->name('opportunities.update');
    Route::delete('/opportunities/{opportunity}', [AdminOpportunityController::class, 'destroy'])->name('opportunities.destroy');

    Route::get('/roadmaps', [AdminRoadmapController::class, 'index'])->name('roadmaps.index');
    Route::get('/roadmaps/{learningPath}', [AdminRoadmapController::class, 'show'])->name('roadmaps.show');
    Route::get('/roadmaps/{learningPath}/steps/create', [AdminRoadmapController::class, 'createStep'])->name('roadmaps.steps.create');
    Route::post('/roadmaps/{learningPath}/steps', [AdminRoadmapController::class, 'storeStep'])->name('roadmaps.steps.store');
    Route::get('/roadmaps/{learningPath}/steps/{roadmapStep}/edit', [AdminRoadmapController::class, 'editStep'])->name('roadmaps.steps.edit');
    Route::put('/roadmaps/{learningPath}/steps/{roadmapStep}', [AdminRoadmapController::class, 'updateStep'])->name('roadmaps.steps.update');
    Route::delete('/roadmaps/{learningPath}/steps/{roadmapStep}', [AdminRoadmapController::class, 'destroyStep'])->name('roadmaps.steps.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
});
