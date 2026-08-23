<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'slug' => 'path-ignited',
                'name' => 'PATH IGNITED',
                'description' => 'Complete 1 roadmap step',
                'icon' => 'flame',
                'rarity' => 'common',
                'condition_type' => 'completed_steps',
                'condition_value' => 1,
            ],
            [
                'slug' => 'trailblazer',
                'name' => 'TRAILBLAZER',
                'description' => 'Complete 10 roadmap steps',
                'icon' => 'compass',
                'rarity' => 'rare',
                'condition_type' => 'completed_steps',
                'condition_value' => 10,
            ],
            [
                'slug' => 'summit-seeker',
                'name' => 'SUMMIT SEEKER',
                'description' => 'Reach 50% roadmap completion',
                'icon' => 'mountain',
                'rarity' => 'epic',
                'condition_type' => 'roadmap_percent',
                'condition_value' => 50,
            ],
            [
                'slug' => 'skillforged',
                'name' => 'SKILLFORGED',
                'description' => 'Add 5 skills',
                'icon' => 'anvil',
                'rarity' => 'rare',
                'condition_type' => 'skills_count',
                'condition_value' => 5,
            ],
            [
                'slug' => 'xp-overdrive',
                'name' => 'XP OVERDRIVE',
                'description' => 'Reach 500 XP',
                'icon' => 'lightning',
                'rarity' => 'epic',
                'condition_type' => 'xp',
                'condition_value' => 500,
            ],
            [
                'slug' => 'ascendant',
                'name' => 'ASCENDANT',
                'description' => 'Reach Level 5',
                'icon' => 'star',
                'rarity' => 'legendary',
                'condition_type' => 'level',
                'condition_value' => 5,
            ],
        ];

        foreach ($rows as $row) {
            Achievement::query()->updateOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }
    }
}
