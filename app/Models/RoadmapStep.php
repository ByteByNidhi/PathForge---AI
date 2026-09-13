<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoadmapStep extends Model
{
    protected $fillable = [
        'path_id',
        'step_no',
        'title',
        'description',
        'xp_reward',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class, 'roadmap_step_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'roadmap_step_skill')
            ->withTimestamps();
    }
}
