<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    public const SOURCE_CURATED = 'curated';

    public const SOURCE_AI = 'ai';

    protected $fillable = [
        'path_name',
        'description',
        'icon',
        'roadmap_source',
        'roadmap_generated_at',
        'roadmap_draft_title',
        'roadmap_draft_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'roadmap_generated_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function scopeAvailableToStudents($query)
    {
        return $query->where('is_published', true);
    }

    public function isAvailableToStudents(): bool
    {
        return (bool) $this->is_published;
    }

    public function roadmapSteps(): HasMany
    {
        return $this->hasMany(RoadmapStep::class, 'path_id');
    }

    public function publishedRoadmapSteps(): HasMany
    {
        return $this->roadmapSteps()->where('is_published', true);
    }

    public function draftRoadmapSteps(): HasMany
    {
        return $this->roadmapSteps()->where('is_published', false);
    }

    public function isAiGenerated(): bool
    {
        return $this->roadmap_source === self::SOURCE_AI;
    }

    public function hasAiDraft(): bool
    {
        return $this->draftRoadmapSteps()->exists();
    }

    public function hasLiveStudentProgress(): bool
    {
        $stepIds = $this->publishedRoadmapSteps()->pluck('id');

        if ($stepIds->isEmpty()) {
            return false;
        }

        return UserProgress::query()
            ->whereIn('roadmap_step_id', $stepIds)
            ->exists();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'learning_path_skill')
            ->withTimestamps();
    }
}
