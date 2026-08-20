<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Learning Paths
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->string('path_name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // 2. Roadmap Steps
        Schema::create('roadmap_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')
                ->constrained('learning_paths')
                ->cascadeOnDelete();
            $table->integer('step_no');
            $table->string('title');
            $table->integer('xp_reward')->default(0);
            $table->timestamps();
        });

        // 3. Skills
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->timestamps();
        });

        // 4. User Skills
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'skill_id']);
        });

        // 5. User Progress
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('roadmap_step_id')
                ->constrained('roadmap_steps')
                ->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 6. Opportunities
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('company');
            $table->string('category');
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->text('apply_link')->nullable();
            $table->timestamps();
        });

        // 7. Opportunity Skills
        Schema::create('opportunity_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')
                ->constrained('opportunities')
                ->cascadeOnDelete();
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['opportunity_id', 'skill_id']);
        });

        // 8. Saved Opportunities
        Schema::create('saved_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('opportunity_id')
                ->constrained('opportunities')
                ->cascadeOnDelete();
            $table->timestamp('saved_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'opportunity_id']);
        });

        // 9. Connections
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('partner_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'partner_id']);
        });

        // 10. Admins
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Add PathForge fields to Laravel's existing users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('path_id')
                ->nullable()
                ->constrained('learning_paths')
                ->nullOnDelete();

            $table->integer('xp')->default(0);
            $table->integer('level')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['path_id']);
            $table->dropColumn(['path_id', 'xp', 'level']);
        });

        Schema::dropIfExists('connections');
        Schema::dropIfExists('saved_opportunities');
        Schema::dropIfExists('opportunity_skills');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('user_progress');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('roadmap_steps');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('admins');
    }
};
