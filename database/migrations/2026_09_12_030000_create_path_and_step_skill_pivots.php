<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_path_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')
                ->constrained('learning_paths')
                ->cascadeOnDelete();
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learning_path_id', 'skill_id']);
        });

        Schema::create('roadmap_step_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roadmap_step_id')
                ->constrained('roadmap_steps')
                ->cascadeOnDelete();
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['roadmap_step_id', 'skill_id']);
        });

        $this->deduplicateUserProgress();

        Schema::table('user_progress', function (Blueprint $table) {
            $table->unique(['user_id', 'roadmap_step_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'roadmap_step_id']);
        });

        Schema::dropIfExists('roadmap_step_skill');
        Schema::dropIfExists('learning_path_skill');
    }

    private function deduplicateUserProgress(): void
    {
        $pairs = DB::table('user_progress')
            ->select('user_id', 'roadmap_step_id')
            ->groupBy('user_id', 'roadmap_step_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($pairs as $pair) {
            $rows = DB::table('user_progress')
                ->where('user_id', $pair->user_id)
                ->where('roadmap_step_id', $pair->roadmap_step_id)
                ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            $keepId = $rows->first()?->id;

            if ($keepId === null) {
                continue;
            }

            DB::table('user_progress')
                ->where('user_id', $pair->user_id)
                ->where('roadmap_step_id', $pair->roadmap_step_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }
};
