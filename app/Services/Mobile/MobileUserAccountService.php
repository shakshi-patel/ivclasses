<?php

namespace App\Services\Mobile;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Writes mobile profile/password via query builder and verifies the row in DB.
 * Avoids "success response but nothing persisted" from stale Eloquent instances.
 */
final class MobileUserAccountService
{
    /**
     * @param  array{name?: string, phone?: string|null, avatar_path?: string|null}  $fields
     * @return array{user: User, synced: array{branches: list<array<string, mixed>>, parents: int}}
     */
    public function updateProfile(User $user, array $fields): array
    {
        $payload = ['updated_at' => now()];

        if (array_key_exists('name', $fields)) {
            $payload['name'] = $fields['name'];
        }
        if (array_key_exists('phone', $fields)) {
            $payload['phone'] = $fields['phone'];
        }
        if (array_key_exists('avatar_path', $fields)) {
            $payload['avatar_path'] = $fields['avatar_path'];
        }

        if (count($payload) === 1) {
            throw new RuntimeException('No profile fields to update.');
        }

        return DB::transaction(function () use ($user, $payload) {
            DB::table('users')
                ->where('id', $user->id)
                ->whereNull('deleted_at')
                ->update($payload);

            $row = DB::table('users')->where('id', $user->id)->whereNull('deleted_at')->first();
            if (! $row) {
                throw new RuntimeException('User row not found while updating profile.');
            }

            $this->assertUserFieldsMatch($row, $payload);

            $synced = $this->syncRelatedContactRows((int) $user->id, $payload, $row);

            return [
                'user' => User::query()->findOrFail($user->id),
                'synced' => $synced,
            ];
        });
    }

    public function updatePassword(User $user, string $plainPassword): User
    {
        $hash = Hash::make($plainPassword);

        DB::table('users')
            ->where('id', $user->id)
            ->whereNull('deleted_at')
            ->update([
                'password' => $hash,
                'must_change_password' => false,
                'updated_at' => now(),
            ]);

        $stored = DB::table('users')->where('id', $user->id)->value('password');

        if (! is_string($stored) || ! Hash::check($plainPassword, $stored)) {
            throw new RuntimeException('Password update did not persist to the database.');
        }

        $this->revokeMobileTokens($user);

        return User::query()->findOrFail($user->id);
    }

    public function revokeMobileTokens(User $user, ?string $plainToken = null): void
    {
        $user->tokens()->delete();

        if (is_string($plainToken) && $plainToken !== '') {
            DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $plainToken))
                ->delete();
        }
    }

    public function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }

    /**
     * Branch manager contact is duplicated on `branches` (contact_person / phone / email).
     * Parent login contact is duplicated on `parents`. Keep those in lockstep with `users`.
     * Never rewrite `branches.name` — that is the campus name, not the person.
     *
     * @param  array<string, mixed>  $userPayload
     * @param  object{name: mixed, phone: mixed, email: mixed}  $userRow
     * @return array{branches: list<array<string, mixed>>, parents: int}
     */
    private function syncRelatedContactRows(int $userId, array $userPayload, object $userRow): array
    {
        $hasContactChange = array_key_exists('name', $userPayload)
            || array_key_exists('phone', $userPayload);

        if (! $hasContactChange) {
            return ['branches' => [], 'parents' => 0];
        }

        $branchFields = ['updated_at' => now()];
        if (array_key_exists('name', $userPayload)) {
            $branchFields['contact_person'] = $userRow->name;
        }
        if (array_key_exists('phone', $userPayload)) {
            $branchFields['phone'] = $userRow->phone;
        }
        $branchFields['email'] = $userRow->email;

        $branchIds = DB::table('branches')
            ->where('manager_user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $syncedBranches = [];
        if ($branchIds->isNotEmpty()) {
            DB::table('branches')->whereIn('id', $branchIds)->update($branchFields);

            $syncedBranches = DB::table('branches')
                ->whereIn('id', $branchIds)
                ->get(['id', 'name', 'contact_person', 'phone', 'email'])
                ->map(function ($branch) use ($branchFields) {
                    if (array_key_exists('contact_person', $branchFields)
                        && (string) $branch->contact_person !== (string) $branchFields['contact_person']) {
                        throw new RuntimeException('Branch contact_person did not persist.');
                    }
                    if (array_key_exists('phone', $branchFields)
                        && (string) $branch->phone !== (string) $branchFields['phone']) {
                        throw new RuntimeException('Branch phone did not persist.');
                    }
                    if ((string) $branch->email !== (string) $branchFields['email']) {
                        throw new RuntimeException('Branch email did not persist.');
                    }

                    return [
                        'id' => (int) $branch->id,
                        'branch_name' => $branch->name,
                        'contact_person' => $branch->contact_person,
                        'phone' => $branch->phone,
                        'email' => $branch->email,
                    ];
                })
                ->values()
                ->all();
        }

        $parentFields = ['updated_at' => now()];
        if (array_key_exists('name', $userPayload)) {
            $parentFields['father_name'] = $userRow->name;
        }
        if (array_key_exists('phone', $userPayload)) {
            $parentFields['phone'] = $userRow->phone;
        }
        $parentFields['email'] = $userRow->email;

        $parentIds = DB::table('parents')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($parentIds->isNotEmpty()) {
            DB::table('parents')->whereIn('id', $parentIds)->update($parentFields);

            foreach (DB::table('parents')->whereIn('id', $parentIds)->get() as $parent) {
                if (array_key_exists('father_name', $parentFields)
                    && (string) $parent->father_name !== (string) $parentFields['father_name']) {
                    throw new RuntimeException('Parent name did not persist.');
                }
                if (array_key_exists('phone', $parentFields)
                    && (string) $parent->phone !== (string) $parentFields['phone']) {
                    throw new RuntimeException('Parent phone did not persist.');
                }
                if ((string) $parent->email !== (string) $parentFields['email']) {
                    throw new RuntimeException('Parent email did not persist.');
                }
            }
        }

        return [
            'branches' => $syncedBranches,
            'parents' => $parentIds->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertUserFieldsMatch(object $row, array $payload): void
    {
        foreach (['name', 'phone', 'avatar_path'] as $key) {
            if (array_key_exists($key, $payload) && (string) $row->{$key} !== (string) $payload[$key]) {
                throw new RuntimeException('Profile update did not persist to the database.');
            }
        }
    }
}
