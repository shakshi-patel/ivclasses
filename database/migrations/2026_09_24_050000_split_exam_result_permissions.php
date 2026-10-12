<?php

use App\Services\PermissionCarryOverService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCarryOverService::class)->carryOver([
            'exam.view' => [
                'exam.show',
                'exam.download',
            ],
            'exam.result' => [
                'result_announcement.view',
                'result_announcement.add',
                'result_announcement.edit',
            ],
            'result_announcement.view' => [
                'result_announcement.show',
                'result_announcement.export',
            ],
        ]);
    }

    public function down(): void
    {
        // Grants are additive; legacy permissions remain on roles, so nothing to revert.
    }
};
