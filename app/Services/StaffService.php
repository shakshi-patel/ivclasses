<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class StaffService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {
            $instituteId = (int) $data['institute_id'];

            $user = User::query()->create([
                'institute_id' => $instituteId,
                'user_type' => UserType::InstituteStaff,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => ($data['status'] ?? 'active') === 'active',
                'email_verified_at' => now(),
                'must_change_password' => true,
            ]);

            if (! empty($data['avatar']) && $data['avatar']->isValid()) {
                $user->update([
                    'avatar_path' => $data['avatar']->store('avatars/staff', 'public'),
                ]);
            }

            $staff = Staff::query()->create([
                'user_id' => $user->id,
                'institute_id' => $instituteId,
                'employee_code' => $this->nextEmployeeCode($instituteId),
                'designation' => $data['designation'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'expected_login_time' => $data['expected_login_time'] ?? null,
                'grace_minutes' => (int) ($data['grace_minutes'] ?? 10),
                'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'active',
            ]);

            $this->syncBranches($user, $data['branch_ids'] ?? [], (int) ($data['primary_branch_id'] ?? 0));
            $this->assignRole($user, $instituteId, (int) ($data['role_id'] ?? 0));

            return $staff->load(['user', 'branches', 'user.roles']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {
            $user = $staff->user;
            $instituteId = (int) $staff->institute_id;

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => ($data['status'] ?? 'active') === 'active',
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            if (! empty($data['avatar']) && $data['avatar']->isValid()) {
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $user->avatar_path = $data['avatar']->store('avatars/staff', 'public');
            }

            $user->save();

            $staff->update([
                'designation' => $data['designation'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'expected_login_time' => $data['expected_login_time'] ?? null,
                'grace_minutes' => (int) ($data['grace_minutes'] ?? 10),
                'joining_date' => $data['joining_date'] ?? $staff->joining_date,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->syncBranches($user, $data['branch_ids'] ?? [], (int) ($data['primary_branch_id'] ?? 0));
            $this->assignRole($user, $instituteId, (int) ($data['role_id'] ?? 0));

            return $staff->fresh(['user', 'branches', 'user.roles']);
        });
    }

    public function delete(Staff $staff): void
    {
        DB::transaction(function () use ($staff) {
            $user = $staff->user;
            Branch::query()->where('manager_user_id', $user->id)->update(['manager_user_id' => null]);
            DB::table('branch_user')->where('user_id', $user->id)->delete();
            $staff->delete();
            $user->delete();
        });
    }

    public function toggleStatus(Staff $staff): Staff
    {
        $active = $staff->status !== 'active';
        $staff->update(['status' => $active ? 'active' : 'inactive']);
        $staff->user?->update(['is_active' => $active]);

        return $staff->fresh(['user', 'branches']);
    }

    /**
     * Resolve Spatie role for staff using institute team_id (works for platform admin too).
     *
     * @return array{id: int|null, name: string|null}
     */
    public function resolveRole(Staff $staff): array
    {
        if (! $staff->user_id) {
            return ['id' => null, 'name' => null];
        }

        $row = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->where('mhr.model_id', $staff->user_id)
            ->where('mhr.team_id', $staff->institute_id)
            ->where('r.team_id', $staff->institute_id)
            ->select(['r.id', 'r.name'])
            ->first();

        return [
            'id' => $row?->id ? (int) $row->id : null,
            'name' => $row?->name,
        ];
    }

    /**
     * Which "Users" list tab this staff member belongs to: branch_manager, principal,
     * receptionist, accountant, or 'other' (no dedicated tab).
     */
    public function roleTabFor(Staff $staff): string
    {
        $roleName = strtolower((string) $this->resolveRole($staff)['name']);

        if (in_array($roleName, ['branch_manager', 'principal', 'receptionist', 'accountant'], true)) {
            return $roleName;
        }

        if ($staff->user?->user_type === UserType::BranchManager
            || strcasecmp(trim((string) $staff->designation), 'Branch Manager') === 0) {
            return 'branch_manager';
        }

        return 'other';
    }

    /**
     * @param  Collection<int, Staff>  $staff
     * @return array<string, string>  key: "{user_id}:{institute_id}" => role name
     */
    public function roleNamesForStaff(Collection $staff): array
    {
        $userIds = $staff->pluck('user_id')->filter()->unique()->values()->all();

        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->whereIn('mhr.model_id', $userIds)
            ->whereColumn('mhr.team_id', 'r.team_id')
            ->select(['mhr.model_id as user_id', 'mhr.team_id as institute_id', 'r.name'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map["{$row->user_id}:{$row->institute_id}"] = $row->name;
        }

        return $map;
    }

    protected function syncBranches(User $user, array $branchIds, int $primaryBranchId): void
    {
        $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));

        if ($primaryBranchId && ! in_array($primaryBranchId, $branchIds, true)) {
            $branchIds[] = $primaryBranchId;
        }

        if ($branchIds === []) {
            DB::table('branch_user')->where('user_id', $user->id)->delete();

            return;
        }

        $sync = [];
        foreach ($branchIds as $branchId) {
            $sync[$branchId] = [
                'is_primary' => $branchId === $primaryBranchId
                    || ($primaryBranchId === 0 && $branchId === $branchIds[0]),
            ];
        }

        if ($primaryBranchId === 0 && count($branchIds) === 1) {
            $sync[$branchIds[0]] = ['is_primary' => true];
        }

        $user->branches()->sync($sync);
    }

    protected function assignRole(User $user, int $instituteId, int $roleId): void
    {
        if ($roleId <= 0) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $role = Role::query()
            ->where('id', $roleId)
            ->where('team_id', $instituteId)
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['developer', 'institute_admin', 'teacher', 'branch_manager'])
            ->first();

        if (! $role) {
            return;
        }

        $user->syncRoles([$role]);

        // Principal cannot also be a branch manager login.
        if (strtolower((string) $role->name) === 'principal') {
            Branch::query()->where('manager_user_id', $user->id)->update(['manager_user_id' => null]);
        }
    }

    protected function nextEmployeeCode(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $n = Staff::withTrashed()->where('institute_id', $instituteId)->count() + 1;

            return 'STF-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        });
    }

    public function staffLimitError(int $instituteId): ?string
    {
        $institute = Institute::query()->with('currentSubscription.plan')->find($instituteId);

        return app(\App\Services\Billing\PlanLimitService::class)->staffLimitError($institute);
    }

    /**
     * Ensure common institute staff roles exist from platform templates.
     */
    public function ensureDefaultRoles(int $instituteId): void
    {
        app(RoleTemplateService::class)->ensureInstituteRolesFromTemplates($instituteId);
    }
}
