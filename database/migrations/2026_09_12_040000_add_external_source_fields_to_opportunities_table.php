<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('opportunities')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'source')) {
                $table->string('source')->nullable()->after('location');
            }

            if (! Schema::hasColumn('opportunities', 'external_id')) {
                $table->string('external_id', 512)->nullable()->after('source');
            }

            if (! Schema::hasColumn('opportunities', 'source_url')) {
                $table->text('source_url')->nullable()->after('external_id');
            }

            if (! Schema::hasColumn('opportunities', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('source_url');
            }
        });

        $indexNames = collect(DB::select('SHOW INDEX FROM opportunities'))
            ->pluck('Key_name')
            ->all();

        Schema::table('opportunities', function (Blueprint $table) use ($indexNames) {
            if (! in_array('opportunities_source_external_id_unique', $indexNames, true)) {
                $table->unique(['source', 'external_id']);
            }

            if (! in_array('opportunities_approval_status_index', $indexNames, true)) {
                $table->index('approval_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('opportunities')) {
            return;
        }

        $indexNames = collect(DB::select('SHOW INDEX FROM opportunities'))
            ->pluck('Key_name')
            ->all();

        Schema::table('opportunities', function (Blueprint $table) use ($indexNames) {
            if (in_array('opportunities_source_external_id_unique', $indexNames, true)) {
                $table->dropUnique(['source', 'external_id']);
            }

            if (in_array('opportunities_approval_status_index', $indexNames, true)) {
                $table->dropIndex(['approval_status']);
            }

            $drop = [];

            foreach (['source', 'external_id', 'source_url', 'approval_status'] as $column) {
                if (Schema::hasColumn('opportunities', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
