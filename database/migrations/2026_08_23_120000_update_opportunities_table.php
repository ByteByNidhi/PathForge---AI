<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('opportunities')) {
            Schema::create('opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('organization');
                $table->string('type');
                $table->text('description')->nullable();
                $table->text('required_skills')->nullable();
                $table->text('eligibility')->nullable();
                $table->date('deadline')->nullable();
                $table->text('application_url')->nullable();
                $table->string('location')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'company') && ! Schema::hasColumn('opportunities', 'organization')) {
                $table->renameColumn('company', 'organization');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'category') && ! Schema::hasColumn('opportunities', 'type')) {
                $table->renameColumn('category', 'type');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'apply_link') && ! Schema::hasColumn('opportunities', 'application_url')) {
                $table->renameColumn('apply_link', 'application_url');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'required_skills')) {
                $table->text('required_skills')->nullable()->after('description');
            }

            if (! Schema::hasColumn('opportunities', 'eligibility')) {
                $table->text('eligibility')->nullable()->after('required_skills');
            }

            if (! Schema::hasColumn('opportunities', 'location')) {
                $table->string('location')->nullable()->after('application_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('opportunities')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'organization') && ! Schema::hasColumn('opportunities', 'company')) {
                $table->renameColumn('organization', 'company');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'type') && ! Schema::hasColumn('opportunities', 'category')) {
                $table->renameColumn('type', 'category');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'application_url') && ! Schema::hasColumn('opportunities', 'apply_link')) {
                $table->renameColumn('application_url', 'apply_link');
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('opportunities', 'required_skills')) {
                $drop[] = 'required_skills';
            }

            if (Schema::hasColumn('opportunities', 'eligibility')) {
                $drop[] = 'eligibility';
            }

            if (Schema::hasColumn('opportunities', 'location')) {
                $drop[] = 'location';
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
