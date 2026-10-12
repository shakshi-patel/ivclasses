<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sync permission matrix, platform role templates, and propagate to institutes.';

    public function handle(PermissionSyncService $sync): int
    {
        $result = $sync->sync();

        $this->info('Permission matrix synced.');
        $this->table(
            ['Created', 'Updated', 'Deactivated', 'Assigned to auto roles'],
            [[$result['created'], $result['updated'], $result['deactivated'], $result['assigned']]]
        );

        return self::SUCCESS;
    }
}
