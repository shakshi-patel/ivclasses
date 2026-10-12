<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class BranchService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $managerUserId = $this->upsertManagerUser(null, $data, requirePassword: true);

            $branch = Branch::query()->create([
                'institute_id' => (int) $data['institute_id'],
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'contact_person' => $data['manager_name'] ?? ($data['contact_person'] ?? null),
                'email' => $data['manager_email'] ?? ($data['email'] ?? null),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'manager_user_id' => $managerUserId,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->attachManagerToBranch(User::query()->findOrFail($managerUserId), $branch);

            return $branch->load('manager');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $managerUserId = $this->upsertManagerUser($branch, $data, requirePassword: false);

            $branch->update([
                'institute_id' => (int) ($data['institute_id'] ?? $branch->institute_id),
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'contact_person' => $data['manager_name'] ?? ($data['contact_person'] ?? $branch->contact_person),
                'email' => $data['manager_email'] ?? ($data['email'] ?? $branch->email),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'manager_user_id' => $managerUserId,
                'status' => $data['status'] ?? $branch->status,
            ]);

            $this->attachManagerToBranch(User::query()->findOrFail($managerUserId), $branch->fresh());

            return $branch->fresh('manager');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function upsertManagerUser(?Branch $branch, array $data, bool $requirePassword): int
    {
        $instituteId = (int) $data['institute_id'];
        $name = trim((string) ($data['manager_name'] ?? ''));
        $email = strtolower(trim((string) ($data['manager_email'] ?? '')));
        $password = $data['manager_password'] ?? null;

        if ($name === '' || $email === '') {
            throw ValidationException::withMessages([
                'manager_name' => 'Branch manager name and email are required for Branch portal login.',
            ]);
        }

        if ($requirePassword && blank($password)) {
            throw ValidationException::withMessages([
                'manager_password' => 'Password is required so the branch manager can sign in.',
            ]);
        }

        $existingByEmail = User::query()->where('email', $email)->first();
        $currentManager = $branch?->manager_user_id
            ? User::query()->find($branch->manager_user_id)
            : null;

        if ($existingByEmail && $currentManager && (int) $existingByEmail->id !== (int) $currentManager->id) {
            throw ValidationException::withMessages([
                'manager_email' => 'This email is already used by another account.',
            ]);
        }

        if ($existingByEmail && ! $currentManager) {
            if ($existingByEmail->isPrincipal()) {
                throw ValidationException::withMessages([
                    'manager_email' => 'Principal cannot be Branch Manager. Use a different email.',
                ]);
            }
            if ((int) $existingByEmail->institute_id !== $instituteId) {
                throw ValidationException::withMessages([
                    'manager_email' => 'This email belongs to another institute.',
                ]);
            }
            $user = $existingByEmail;
            $user->fill([
                'user_type' => UserType::BranchManager,
                'name' => $name,
                'phone' => $data['phone'] ?? $user->phone,
                'is_active' => ($data['status'] ?? 'active') === 'active',
            ]);
            if (filled($password)) {
                $user->password = Hash::make($password);
            }
            $user->save();
        } elseif ($currentManager) {
            $user = $currentManager;
            if ($user->isPrincipal()) {
                throw ValidationException::withMessages([
                    'manager_email' => 'Principal cannot be Branch Manager.',
                ]);
            }
            $user->fill([
                'user_type' => UserType::BranchManager,
                'name' => $name,
                'email' => $email,
                'phone' => $data['phone'] ?? $user->phone,
                'is_active' => ($data['status'] ?? 'active') === 'active',
            ]);
            if (filled($password)) {
                $user->password = Hash::make($password);
                $user->must_change_password = (bool) ($data['manager_must_change_password'] ?? true);
            }
            $user->save();
        } else {
            $user = User::query()->create([
                'institute_id' => $instituteId,
                'user_type' => UserType::BranchManager,
                'name' => $name,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make((string) $password),
                'is_active' => ($data['status'] ?? 'active') === 'active',
                'email_verified_at' => now(),
                'must_change_password' => (bool) ($data['manager_must_change_password'] ?? true),
            ]);

            Staff::query()->create([
                'user_id' => $user->id,
                'institute_id' => $instituteId,
                'employee_code' => $this->nextEmployeeCode($instituteId),
                'designation' => 'Branch Manager',
                'joining_date' => now()->toDateString(),
                'status' => $data['status'] ?? 'active',
            ]);
        }

        $this->ensureBranchManagerRole($user, $instituteId);

        return (int) $user->id;
    }

    protected function attachManagerToBranch(User $user, Branch $branch): void
    {
        // Keep other branch links; ensure this branch is attached as primary for manager scope.
        $sync = $user->branches()->pluck('branches.id')
            ->mapWithKeys(fn ($id) => [(int) $id => ['is_primary' => false]])
            ->all();
        $sync[(int) $branch->id] = ['is_primary' => true];
        $user->branches()->sync($sync);
    }

    protected function ensureBranchManagerRole(User $user, int $instituteId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $role = Role::query()->firstOrCreate(
            [
                'name' => 'branch_manager',
                'guard_name' => 'web',
                'team_id' => $instituteId,
            ],
            [
                'is_system' => true,
                'status' => 'active',
            ]
        );

        if ($user->hasRole('principal')) {
            throw ValidationException::withMessages([
                'manager_email' => 'Principal and Branch Manager must be separate logins.',
            ]);
        }

        $user->syncRoles([$role]);
    }

    protected function nextEmployeeCode(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $n = Staff::withTrashed()->where('institute_id', $instituteId)->count() + 1;

            return 'STF-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        });
    }
}
