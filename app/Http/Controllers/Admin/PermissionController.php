<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use App\Services\PermissionSyncService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PermissionController extends AdminController
{
    public function index(): View
    {
        $this->ensureCan('permissions.view');

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.master.permissions.permissions', compact('permissions'));
    }

    public function sync(PermissionSyncService $service): JsonResponse
    {
        $this->ensureCan('permissions.sync');

        $result = $service->sync();

        return Ajax::success(
            'Permission matrix synced.',
            $result
        );
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
