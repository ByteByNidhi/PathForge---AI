<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function learningPaths(): BelongsToMany
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_skill')
            ->withTimestamps();
    }

    public function roadmapSteps(): BelongsToMany
    {
        return $this->belongsToMany(RoadmapStep::class, 'roadmap_step_skill')
            ->withTimestamps();
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'opportunity_skills')
            ->withTimestamps();
    }

    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        $existing = static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create(['name' => $name]);
    }
}
