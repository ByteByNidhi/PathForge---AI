<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description');
            $table->string('icon');
            $table->string('rarity');
            $table->string('condition_type');
            $table->unsignedInteger('condition_value');
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('achievement_id')
                ->constrained('achievements')
                ->cascadeOnDelete();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });

        $now = now();

        foreach ($this->catalog() as $row) {
            DB::table('achievements')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }

    /**
     * @return list<array{slug: string, name: string, description: string, icon: string, rarity: string, condition_type: string, condition_value: int}>
     */
    private function catalog(): array
    {
        return [
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
    }
};
