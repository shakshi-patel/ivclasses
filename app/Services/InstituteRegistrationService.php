<?php

namespace App\Services;

use App\Enums\DemoStatus;
use App\Enums\InstituteStatus;
use App\Enums\ProjectInquiryStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserType;
use App\Models\Institute;
use App\Models\Plan;
use App\Models\ProjectInquiry;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class InstituteRegistrationService
{
    /** Same ceiling as the public registration branch-count field. */
    public const BRANCH_FORM_CAP = 5;

    public function __construct(
        protected BranchService $branchService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{institute:Institute,user:User,subscription:Subscription,branch:\App\Models\Branch,branches:list<\App\Models\Branch>,otp:string}
     */
    public function register(array $data): array
    {
        $plan = $this->resolveRegistrationPlan($data['plan_id'] ?? null);
        $trialDays = (int) ($plan->trial_days ?: 14);
        $otp = $this->generateOtp();

        return DB::transaction(function () use ($data, $plan, $trialDays, $otp) {
            $slugBase = Str::slug($data['name']);
            $slug = $slugBase;
            $i = 1;
            while (Institute::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$i;
                $i++;
            }

            $institute = Institute::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => InstituteStatus::Trial,
                'trial_ends_at' => now()->addDays($trialDays),
                'timezone' => 'Asia/Kolkata',
            ]);

            $subscription = Subscription::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addDays($trialDays),
                'status' => SubscriptionStatus::Trial,
                // Payment bypass: start trial on chosen plan; live billing will collect later.
                'amount' => 0,
            ]);

            $user = User::query()->create([
                'institute_id' => $institute->id,
                'user_type' => UserType::InstituteAdmin,
                'name' => $data['admin_name'] ?? $data['name'].' Admin',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => ! empty($data['activate_immediately']),
                'email_verified_at' => ! empty($data['activate_immediately']) ? now() : null,
                'must_change_password' => (bool) ($data['must_change_password'] ?? false),
                'registration_otp' => ! empty($data['activate_immediately']) ? null : $otp,
                'registration_otp_expires_at' => ! empty($data['activate_immediately']) ? null : now()->addMinutes(15),
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($institute->id);

            app(RoleTemplateService::class)->ensureInstituteRolesFromTemplates($institute->id);

            $role = Role::query()
                ->where('name', 'institute_admin')
                ->where('guard_name', 'web')
                ->where('team_id', $institute->id)
                ->firstOrFail();

            $user->assignRole($role);

            $branchRows = $this->normalizeBranchesPayload($data, $user);
            $createdBranches = [];

            foreach ($branchRows as $row) {
                if (! empty($row['managed_by_admin'])) {
                    $createdBranches[] = $this->createDefaultMainBranch($institute, $user, $row);
                    continue;
                }

                $createdBranches[] = $this->branchService->create([
                    'institute_id' => $institute->id,
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? ($data['phone'] ?? null),
                    'address' => $row['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'manager_name' => $row['manager_name'],
                    'manager_email' => $row['manager_email'],
                    'manager_password' => $row['manager_password'],
                    'manager_must_change_password' => (bool) ($data['must_change_password'] ?? false),
                    'status' => 'active',
                ]);
            }

            $branch = $createdBranches[0];

            return [
                'institute' => $institute,
                'user' => $user,
                'subscription' => $subscription,
                'branch' => $branch,
                'branches' => $createdBranches,
                'otp' => $otp,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    protected function normalizeBranchesPayload(array $data, User $admin): array
    {
        $hasBranches = ($data['has_branches'] ?? 'yes') === 'yes';

        if (! $hasBranches) {
            return [[
                'managed_by_admin' => true,
                'name' => trim((string) $data['name']).' — Main',
                'phone' => $data['phone'] ?? null,
                'address' => null,
            ]];
        }

        $rows = [];
        foreach ($data['branches'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $rows[] = [
                'name' => $name,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'manager_name' => $row['manager_name'] ?? null,
                'manager_email' => $row['manager_email'] ?? null,
                'manager_password' => $row['manager_password'] ?? null,
            ];
        }

        if ($rows !== []) {
            return $rows;
        }

            // Legacy flat fields (demo convert / older forms)
        return [[
            'name' => $data['branch_name'] ?? 'Main Branch',
            'phone' => $data['branch_phone'] ?? ($data['phone'] ?? null),
            'address' => $data['branch_address'] ?? null,
            'manager_name' => $data['manager_name'] ?? $admin->name,
            'manager_email' => $data['manager_email'],
            'manager_password' => $data['manager_password'],
        ]];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function createDefaultMainBranch(Institute $institute, User $admin, array $row): \App\Models\Branch
    {
        $branch = \App\Models\Branch::query()->create([
            'institute_id' => $institute->id,
            'name' => $row['name'] ?? ($institute->name.' — Main'),
            'contact_person' => $admin->name,
            'email' => $admin->email,
            'phone' => $row['phone'] ?? $institute->phone,
            'address' => $row['address'] ?? null,
            'city' => $institute->city,
            'state' => $institute->state,
            'manager_user_id' => $admin->id,
            'status' => 'active',
        ]);

        $admin->branches()->syncWithoutDetaching([
            $branch->id => ['is_primary' => true],
        ]);

        return $branch->load('manager');
    }

    /**
     * Platform admin converts a demo lead into a live trial institute.
     * Creates accounts immediately and returns plain-text temp passwords for email.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     institute: Institute,
     *     user: User,
     *     subscription: Subscription,
     *     branch: \App\Models\Branch,
     *     admin_password: string,
     *     manager_password: string,
     *     credentials: array<string, string>
     * }
     */
    public function convertFromProjectInquiry(ProjectInquiry $inquiry, array $overrides = []): array
    {
        if ($inquiry->isConverted()) {
            throw new \InvalidArgumentException('This project inquiry is already converted to an institute.');
        }

        $hasBranches = ($overrides['has_branches'] ?? 'no') === 'yes';
        $adminEmail = strtolower(trim((string) ($overrides['admin_email'] ?? $inquiry->email)));

        if (User::query()->where('email', $adminEmail)->exists()) {
            throw new \InvalidArgumentException('Institute admin email is already registered: '.$adminEmail);
        }

        $adminPassword = $this->generateTempPassword();
        $branchRows = [];
        $branchLogins = [];

        if ($hasBranches) {
            foreach ($overrides['branches'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $managerEmail = strtolower(trim((string) ($row['manager_email'] ?? '')));
                $branchName = trim((string) ($row['name'] ?? ''));
                if ($branchName === '' || $managerEmail === '') {
                    continue;
                }

                if ($managerEmail === $adminEmail) {
                    throw new \InvalidArgumentException('Branch manager email must be different from institute admin email.');
                }

                if (User::query()->where('email', $managerEmail)->exists()) {
                    throw new \InvalidArgumentException('Branch manager email is already registered: '.$managerEmail);
                }

                $managerPassword = $this->generateTempPassword();
                $branchRows[] = [
                    'name' => $branchName,
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'manager_name' => $row['manager_name'] ?? null,
                    'manager_email' => $managerEmail,
                    'manager_password' => $managerPassword,
                ];
                $branchLogins[] = [
                    'branch_name' => $branchName,
                    'manager_name' => (string) ($row['manager_name'] ?? ''),
                    'manager_email' => $managerEmail,
                    'manager_password' => $managerPassword,
                ];
            }

            if ($branchRows === []) {
                throw new \InvalidArgumentException('Add at least one branch with a manager email.');
            }
        }

        $firstLogin = $branchLogins[0] ?? null;

        $payload = [
            'plan_id' => $overrides['plan_id'] ?? null,
            'name' => $overrides['name'] ?? $inquiry->institute_name,
            'admin_name' => $overrides['admin_name'] ?? ($inquiry->contact_person_name ?: ($inquiry->institute_name.' Admin')),
            'email' => $adminEmail,
            'phone' => $overrides['phone'] ?? $inquiry->contact_no,
            'city' => $overrides['city'] ?? $inquiry->city,
            'state' => $overrides['state'] ?? $inquiry->state,
            'password' => $adminPassword,
            'has_branches' => $hasBranches ? 'yes' : 'no',
            'branches' => $branchRows,
            'activate_immediately' => true,
            'must_change_password' => true,
        ];

        $result = $this->register($payload);

        $inquiry->update([
            'converted_at' => now(),
            'converted_institute_id' => $result['institute']->id,
            'status' => ProjectInquiryStatus::Converted,
            'demo_status' => $inquiry->demo_status === DemoStatus::Requested
                ? DemoStatus::Completed
                : $inquiry->demo_status,
            'demo_completed_at' => $inquiry->demo_completed_at ?? now(),
        ]);

        $credentials = [
            'institute_name' => $result['institute']->name,
            'branch_name' => $firstLogin['branch_name'] ?? $result['branch']->name,
            'admin_name' => $result['user']->name,
            'admin_email' => $result['user']->email,
            'admin_password' => $adminPassword,
            'manager_name' => $firstLogin['manager_name'] ?? $result['user']->name,
            'manager_email' => $firstLogin['manager_email'] ?? '',
            'manager_password' => $firstLogin['manager_password'] ?? '',
            'branches' => $branchLogins,
            'has_separate_branch_login' => $branchLogins !== [],
            'institute_login_url' => route('login.institute'),
            'branch_login_url' => route('login.branch'),
            'forgot_password_url' => route('password.request'),
            'is_temporary' => true,
        ];

        return [
            'institute' => $result['institute'],
            'user' => $result['user'],
            'subscription' => $result['subscription'],
            'branch' => $result['branch'],
            'admin_password' => $adminPassword,
            'manager_password' => $firstLogin['manager_password'] ?? '',
            'credentials' => $credentials,
        ];
    }

    public function suggestManagerEmail(string $adminEmail): string
    {
        $adminEmail = strtolower(trim($adminEmail));
        if (! str_contains($adminEmail, '@')) {
            return 'manager@example.com';
        }

        [$local, $domain] = explode('@', $adminEmail, 2);
        $candidate = $local.'.manager@'.$domain;

        if (! User::query()->where('email', $candidate)->exists()) {
            return $candidate;
        }

        return $local.'+manager@'.$domain;
    }

    public function generateTempPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    /**
     * @return array{user: User, otp: string}
     */
    public function regenerateOtp(User $user): array
    {
        if ($user->email_verified_at && $user->is_active && ! $user->registration_otp) {
            throw new \InvalidArgumentException('Account is already verified.');
        }

        $otp = $this->generateOtp();

        $user->update([
            'registration_otp' => $otp,
            'registration_otp_expires_at' => now()->addMinutes(15),
            'is_active' => false,
        ]);

        return [
            'user' => $user->fresh(),
            'otp' => $otp,
        ];
    }

    public function verifyOtp(User $user, string $otp): User
    {
        if ($user->email_verified_at && $user->is_active && ! $user->registration_otp) {
            return $user;
        }

        if (! $user->registration_otp || ! $user->registration_otp_expires_at) {
            throw new \InvalidArgumentException('No OTP pending for this account.');
        }

        if ($user->registration_otp_expires_at->isPast()) {
            throw new \InvalidArgumentException('OTP has expired. Please resend a new code.');
        }

        if (! hash_equals((string) $user->registration_otp, trim($otp))) {
            throw new \InvalidArgumentException('Invalid OTP. Please try again.');
        }

        $user->update([
            'is_active' => true,
            'email_verified_at' => now(),
            'registration_otp' => null,
            'registration_otp_expires_at' => null,
        ]);

        return $user->fresh();
    }

    protected function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Public active plan for signup (modules come from plan.modules_json via subscription).
     */
    protected function resolveRegistrationPlan(mixed $planId): Plan
    {
        if ($planId) {
            $plan = Plan::query()
                ->whereKey((int) $planId)
                ->where('is_public', true)
                ->where('status', 'active')
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        return Plan::query()
            ->where('code', 'trial')
            ->where('status', 'active')
            ->firstOrFail();
    }
}
