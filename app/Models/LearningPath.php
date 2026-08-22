<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    protected $fillable = [
        'path_name',
        'description',
        'icon',
    ];

    public function roadmapSteps(): HasMany
    {
        return $this->hasMany(RoadmapStep::class, 'path_id');
    }
}
