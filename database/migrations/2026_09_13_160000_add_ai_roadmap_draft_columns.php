<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roadmap_steps', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('xp_reward');
        });

        Schema::table('learning_paths', function (Blueprint $table) {
            $table->string('roadmap_draft_title')->nullable()->after('roadmap_generated_at');
            $table->text('roadmap_draft_description')->nullable()->after('roadmap_draft_title');
        });
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn(['roadmap_draft_title', 'roadmap_draft_description']);
        });

        Schema::table('roadmap_steps', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
