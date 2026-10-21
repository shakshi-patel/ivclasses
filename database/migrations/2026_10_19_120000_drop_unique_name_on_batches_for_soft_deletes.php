<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The STORED generated column added in 2026_10_17_145119 (IFNULL on the
     * TIMESTAMP `deleted_at` column) hits MySQL error 1901 on servers that treat
     * TIMESTAMP-derived expressions as non-deterministic for generated columns.
     * Drop it and fall back to the same pattern already used for
     * standards/subjects: a plain index, with active-row uniqueness enforced
     * in app validation (BatchController::validateBatch() Rule::unique(...)
     * ->where('deleted_at', null)).
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropUnique('batches_branch_name_unique');
        });

        DB::statement('ALTER TABLE batches DROP COLUMN deleted_at_key');

        Schema::table('batches', function (Blueprint $table) {
            $table->index(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'name']);
        });

        DB::statement("
            ALTER TABLE batches
            ADD COLUMN deleted_at_key DATETIME
                GENERATED ALWAYS AS (IFNULL(deleted_at, '1970-01-01 00:00:00')) STORED
        ");
        DB::statement('
            ALTER TABLE batches
            ADD UNIQUE INDEX batches_branch_name_unique (branch_id, name, deleted_at_key)
        ');
    }
};
