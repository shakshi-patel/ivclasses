<?php

use App\Services\PermissionCarryOverService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCarryOverService::class)->carryOver([
            'homework.view' => [
                'homework.show',
                'homework.download',
            ],
            'study_material.view' => [
                'study_material.show',
            ],
            'notice.view' => [
                'notice.show',
            ],
            'notice.publish' => [
                'notice.status',
            ],
            'holiday.view' => [
                'holiday.show',
            ],
        ]);
    }

    public function down(): void
    {
        // Grants are additive; legacy permissions remain on roles, so nothing to revert.
    }
};
