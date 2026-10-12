<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class TeacherService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Teacher
    {
        return DB::transaction(function () use ($data) {
            $instituteId = (int) $data['institute_id'];

            $user = User::query()->create([
                'institute_id' => $instituteId,
                'user_type' => UserType::Teacher,
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
                    'avatar_path' => $data['avatar']->store('avatars/teachers', 'public'),
                ]);
            }

            $teacher = Teacher::query()->create([
                'user_id' => $user->id,
                'institute_id' => $instituteId,
                'employee_code' => $this->nextEmployeeCode($instituteId),
                'specialization' => $data['specialization'] ?? null,
                'address' => $data['address'] ?? null,
                'expected_login_time' => $data['expected_login_time'] ?? null,
                'grace_minutes' => (int) ($data['grace_minutes'] ?? 10),
                'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'active',
            ]);

            $this->syncBranches($user, $data['branch_ids'] ?? [], (int) ($data['primary_branch_id'] ?? 0));
            $this->syncBatches($user, $data['batch_ids'] ?? []);
            $this->assignTeacherRole($user, $instituteId);

            return $teacher->load(['user', 'branches', 'assignedBatches']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        return DB::transaction(function () use ($teacher, $data) {
            $user = $teacher->user;
            $instituteId = (int) $teacher->institute_id;

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
                $user->avatar_path = $data['avatar']->store('avatars/teachers', 'public');
            }

            $user->save();

            $teacher->update([
                'specialization' => $data['specialization'] ?? null,
                'address' => $data['address'] ?? null,
                'expected_login_time' => $data['expected_login_time'] ?? null,
                'grace_minutes' => (int) ($data['grace_minutes'] ?? 10),
                'joining_date' => $data['joining_date'] ?? $teacher->joining_date,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->syncBranches($user, $data['branch_ids'] ?? [], (int) ($data['primary_branch_id'] ?? 0));
            $this->syncBatches($user, $data['batch_ids'] ?? []);

            return $teacher->fresh(['user', 'branches', 'assignedBatches']);
        });
    }

    public function delete(Teacher $teacher): void
    {
        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            if ($user) {
                $user->assignedBatches()->detach();
                DB::table('branch_user')->where('user_id', $user->id)->delete();
                $teacher->delete();
                $user->delete();
            } else {
                $teacher->delete();
            }
        });
    }

    public function toggleStatus(Teacher $teacher): Teacher
    {
        $active = $teacher->status !== 'active';
        $teacher->update(['status' => $active ? 'active' : 'inactive']);
        $teacher->user?->update(['is_active' => $active]);

        return $teacher->fresh(['user', 'branches']);
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
            $sync[$branchId] = ['is_primary' => $branchId === $primaryBranchId || ($primaryBranchId === 0 && $branchId === $branchIds[0])];
        }

        if ($primaryBranchId === 0 && count($branchIds) === 1) {
            $sync[$branchIds[0]] = ['is_primary' => true];
        }

        $user->branches()->sync($sync);
    }

    /**
     * @param  array<int|string>  $batchIds
     */
    protected function syncBatches(User $user, array $batchIds): void
    {
        $batchIds = array_values(array_unique(array_filter(array_map('intval', $batchIds))));

        if ($batchIds === []) {
            $user->assignedBatches()->detach();

            return;
        }

        $sync = [];
        foreach ($batchIds as $index => $batchId) {
            $sync[$batchId] = ['is_primary' => $index === 0];
        }

        $user->assignedBatches()->sync($sync);
    }

    protected function assignTeacherRole(User $user, int $instituteId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $role = Role::query()->firstOrCreate(
            [
                'name' => 'teacher',
                'guard_name' => 'web',
                'team_id' => $instituteId,
            ],
            [
                'is_system' => true,
                'status' => 'active',
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    protected function nextEmployeeCode(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $n = Teacher::withTrashed()->where('institute_id', $instituteId)->count() + 1;

            return 'TCH-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        });
    }

    public function staffLimitError(int $instituteId): ?string
    {
        $institute = Institute::query()->with('currentSubscription.plan')->find($instituteId);

        return app(\App\Services\Billing\PlanLimitService::class)->staffLimitError($institute);
    }
}
