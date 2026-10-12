<?php

use App\Services\PermissionCarryOverService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCarryOverService::class)->carryOver([
            'student_leave.view' => [
                'student_leave.show',
            ],
            'student_leave.add' => [
                'student_leave.show',
            ],
        ]);
    }

    public function down(): void
    {
        // Grants are additive; legacy permissions remain on roles, so nothing to revert.
    }
};
