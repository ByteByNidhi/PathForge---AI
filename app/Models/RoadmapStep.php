<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoadmapStep extends Model
{
    protected $fillable = [
        'path_id',
        'step_no',
        'title',
        'xp_reward',
    ];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class, 'roadmap_step_id');
    }
}
