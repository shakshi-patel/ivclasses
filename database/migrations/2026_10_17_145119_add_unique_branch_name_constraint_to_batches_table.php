<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL treats every NULL in a unique index as distinct from every other NULL,
        // so a plain unique(branch_id, name, deleted_at) would never catch two ACTIVE
        // duplicates (deleted_at is NULL on both). A generated column collapses every
        // "active" row onto the same sentinel value so those genuinely collide, while
        // each soft-deleted row keeps its own real timestamp and stays distinct.
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE batches DROP INDEX batches_branch_name_unique');
        DB::statement('ALTER TABLE batches DROP COLUMN deleted_at_key');
    }
};
