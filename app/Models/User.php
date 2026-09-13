<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\AchievementService;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'path_id',
        'xp',
        'level',
        'onboarding_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentOrganization(): ?Organization
    {
        if ($this->relationLoaded('organizations')) {
            return $this->organizations->first();
        }

        return $this->organizations()->orderBy('organization_users.id')->first();
    }

    public function isOrganizationUser(): bool
    {
        if ($this->relationLoaded('organizations')) {
            return $this->organizations->isNotEmpty();
        }

        return $this->organizations()->exists();
    }

    public function organizationRole(?Organization $organization = null): ?string
    {
        $organization ??= $this->currentOrganization();

        if ($organization === null) {
            return null;
        }

        return $organization->roleFor($this);
    }

    public function isOrganizationOwner(?Organization $organization = null): bool
    {
        return $this->organizationRole($organization) === Organization::ROLE_OWNER;
    }

    public function hasCompletedOnboarding(): bool
    {
        return (bool) $this->onboarding_completed;
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class, 'user_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function careerPathRequests(): HasMany
    {
        return $this->hasMany(CareerPathRequest::class);
    }

    public function savedOpportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'saved_opportunities')
            ->withPivot('saved_at')
            ->withTimestamps();
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function addXp(int $amount): void
    {
        $this->xp = (int) ($this->xp ?? 0) + $amount;
        $this->level = intdiv($this->xp, 100) + 1;
        $this->save();

        app(AchievementService::class)->checkAndUnlock($this);
    }

    public function xpIntoLevel(): int
    {
        return ((int) ($this->xp ?? 0)) % 100;
    }

    public function availableRoadmapStep(?LearningPath $path = null): ?RoadmapStep
    {
        $path ??= $this->learningPath;

        if (! $path) {
            return null;
        }

        $completedIds = $this->completedRoadmapStepIds($path);

        return $path->roadmapSteps()
            ->orderBy('step_no')
            ->orderBy('id')
            ->get()
            ->first(fn (RoadmapStep $step) => ! $completedIds->contains($step->id));
    }

    public function canCompleteRoadmapStep(RoadmapStep $step): bool
    {
        if ((int) $this->path_id !== (int) $step->path_id) {
            return false;
        }

        $available = $this->availableRoadmapStep($step->learningPath);

        return $available !== null && (int) $available->id === (int) $step->id;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function completedRoadmapStepIds(?LearningPath $path = null)
    {
        $path ??= $this->learningPath;

        if (! $path) {
            return collect();
        }

        $stepIds = $path->roadmapSteps()->pluck('id');

        return $this->userProgress()
            ->whereIn('roadmap_step_id', $stepIds)
            ->where('status', 'completed')
            ->pluck('roadmap_step_id');
    }
}
