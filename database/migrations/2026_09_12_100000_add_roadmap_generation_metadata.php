<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->string('roadmap_source', 32)->default('curated');
            $table->timestamp('roadmap_generated_at')->nullable();
        });

        Schema::table('roadmap_steps', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn(['roadmap_source', 'roadmap_generated_at']);
        });

        Schema::table('roadmap_steps', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
