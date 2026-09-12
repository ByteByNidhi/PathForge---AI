<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->text('description')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('organization_users')) {
            Schema::create('organization_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->string('role')->default('member');
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('opportunities')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'organization_id')) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('location')
                    ->constrained('organizations')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('opportunities', 'submitted_by_user_id')) {
                $table->foreignId('submitted_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('opportunities', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });

        $indexNames = collect(DB::select('SHOW INDEX FROM opportunities'))
            ->pluck('Key_name')
            ->all();

        Schema::table('opportunities', function (Blueprint $table) use ($indexNames) {
            if (! in_array('opportunities_organization_id_index', $indexNames, true)) {
                $table->index('organization_id');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('opportunities')) {
            $indexNames = collect(DB::select('SHOW INDEX FROM opportunities'))
                ->pluck('Key_name')
                ->all();

            Schema::table('opportunities', function (Blueprint $table) use ($indexNames) {
                if (in_array('opportunities_organization_id_index', $indexNames, true)) {
                    $table->dropIndex(['organization_id']);
                }

                if (Schema::hasColumn('opportunities', 'organization_id')) {
                    $table->dropConstrainedForeignId('organization_id');
                }

                if (Schema::hasColumn('opportunities', 'submitted_by_user_id')) {
                    $table->dropConstrainedForeignId('submitted_by_user_id');
                }

                if (Schema::hasColumn('opportunities', 'rejection_reason')) {
                    $table->dropColumn('rejection_reason');
                }
            });
        }

        Schema::dropIfExists('organization_users');
        Schema::dropIfExists('organizations');
    }
};
