<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OpportunityController as AdminOpportunityController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\RoadmapController as AdminRoadmapController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AiStudioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\Organization\DashboardController as OrganizationDashboardController;
use App\Http\Controllers\Organization\MemberController as OrganizationMemberController;
use App\Http\Controllers\Organization\OpportunityController as OrganizationOpportunityController;
use App\Http\Controllers\Organization\ProfileController as OrganizationProfileController;
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
    ->middleware(['auth', 'student', 'onboarded'])
    ->name('dashboard');

Route::middleware(['auth', 'student'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'show'])->name('show');
    Route::post('/path', [OnboardingController::class, 'storePath'])->name('path.store');
    Route::get('/skills', [OnboardingController::class, 'skills'])->name('skills');
    Route::post('/skills', [OnboardingController::class, 'storeSkill'])->name('skills.store');
    Route::post('/skills/starting', [OnboardingController::class, 'storeStartingPoint'])->name('skills.starting');
    Route::post('/skills/continue', [OnboardingController::class, 'toggleSkills'])->name('skills.continue');
    Route::get('/confirm', [OnboardingController::class, 'confirm'])->name('confirm');
    Route::post('/confirm', [OnboardingController::class, 'complete'])->name('complete');
});

Route::middleware(['auth', 'student', 'onboarded'])->group(function () {
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
    Route::get('/opportunities/saved', [OpportunityController::class, 'saved'])->name('opportunities.saved');
    Route::post('/opportunities/{opportunity}/save', [OpportunityController::class, 'save'])->name('opportunities.save');
    Route::delete('/opportunities/{opportunity}/save', [OpportunityController::class, 'unsave'])->name('opportunities.unsave');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
});

Route::middleware(['auth', 'organization'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/', [OrganizationDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [OrganizationProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [OrganizationProfileController::class, 'update'])->name('profile.update');

    Route::get('/members', [OrganizationMemberController::class, 'index'])->name('members.index');
    Route::post('/members', [OrganizationMemberController::class, 'store'])->name('members.store');
    Route::put('/members/{user}', [OrganizationMemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{user}', [OrganizationMemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/opportunities', [OrganizationOpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/create', [OrganizationOpportunityController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [OrganizationOpportunityController::class, 'store'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}', [OrganizationOpportunityController::class, 'show'])->name('opportunities.show');
    Route::get('/opportunities/{opportunity}/edit', [OrganizationOpportunityController::class, 'edit'])->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [OrganizationOpportunityController::class, 'update'])->name('opportunities.update');
    Route::post('/opportunities/{opportunity}/submit', [OrganizationOpportunityController::class, 'submit'])->name('opportunities.submit');
    Route::delete('/opportunities/{opportunity}', [OrganizationOpportunityController::class, 'destroy'])->name('opportunities.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/opportunities', [AdminOpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/create', [AdminOpportunityController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [AdminOpportunityController::class, 'store'])->name('opportunities.store');
    Route::post('/opportunities/fetch', [AdminOpportunityController::class, 'fetch'])->name('opportunities.fetch');
    Route::post('/opportunities/{opportunity}/approve', [AdminOpportunityController::class, 'approve'])->name('opportunities.approve');
    Route::post('/opportunities/{opportunity}/reject', [AdminOpportunityController::class, 'reject'])->name('opportunities.reject');
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

    Route::get('/organizations', [AdminOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [AdminOrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [AdminOrganizationController::class, 'store'])->name('organizations.store');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
});
