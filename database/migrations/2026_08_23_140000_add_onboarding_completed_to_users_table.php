<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'onboarding_completed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('onboarding_completed')->default(false);
            });
        }

        DB::table('users')->update(['onboarding_completed' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'onboarding_completed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed');
            });
        }
    }
};
