<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_helps')
            ->where('module_key', 'attendance')
            ->whereNull('deleted_at')
            ->update([
                'intro' => 'Mark daily student attendance by date, branch, and batch. Tap each student to set Present, Absent, or Late, then submit. You can update the same day anytime.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: previous intro was incorrect copy-paste from teacher attendance.
    }
};
