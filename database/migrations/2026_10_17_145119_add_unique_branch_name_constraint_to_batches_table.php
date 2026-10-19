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
        // MySQL/MariaDB treat every NULL in a unique index as distinct, so a plain
        // unique(branch_id, name, deleted_at) never catches two ACTIVE duplicates.
        // A generated column collapses every "active" row onto one sentinel value.
        // Use CASE (not IFNULL) — MariaDB rejects IFNULL in GENERATED ALWAYS AS (#1901).
        DB::statement("
            ALTER TABLE batches
            ADD COLUMN deleted_at_key DATETIME
                GENERATED ALWAYS AS (
                    CASE
                        WHEN `deleted_at` IS NULL THEN CAST('1970-01-01 00:00:00' AS DATETIME)
                        ELSE `deleted_at`
                    END
                ) STORED
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
