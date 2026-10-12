<?php

use App\Services\PermissionCarryOverService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCarryOverService::class)->carryOver([
            'attendance.view' => [
                'attendance.show',
                'teacher_attendance.view',
                'teacher_attendance.show',
            ],
            'attendance.mark' => [
                'attendance.add',
                'attendance.edit',
                'teacher_attendance.add',
                'teacher_attendance.edit',
            ],
        ]);
    }

    public function down(): void
    {
        // Grants are additive; legacy permissions remain on roles, so nothing to revert.
    }
};
