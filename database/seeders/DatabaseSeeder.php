<?php

namespace Database\Seeders;

use App\Enums\InstituteStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserType;
use App\Models\Institute;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PermissionSyncService;
use App\Services\StaffService;
use App\Services\TeacherService;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        app(PermissionSyncService::class)->sync();
        $this->seedDeveloper();
        $this->seedDemoInstitute();
        $this->call(EmailTemplateSeeder::class);

        // Optional: fill institute_id=5 with ~2 months CRM demo data
        // $this->call(Institute5CrmDemoSeeder::class);
    }

    protected function seedPlans(): void
    {
        $plans = [
            [
                'code' => 'trial',
                'name' => 'Free Trial',
                'description' => 'Time-limited trial with core CRM modules.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'max_branches' => 1,
                'max_students' => 50,
                'max_staff' => 5,
                'modules_json' => ['inquiry', 'follow_up', 'fees', 'attendance'],
                'benefits_json' => [
                    '1 branch included',
                    'Up to 50 students',
                    'Up to 5 staff logins',
                    '14-day free trial',
                    'Web CRM for institute admin',
                    'Branch manager portal access',
                ],
                'is_public' => true,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Single branch coaching CRM.',
                'price_monthly' => 1999,
                'price_yearly' => 19990,
                'trial_days' => 14,
                'max_branches' => 1,
                'max_students' => 200,
                'max_staff' => 10,
                'modules_json' => ['inquiry', 'follow_up', 'fees', 'attendance', 'homework', 'notice', 'holiday', 'gst_invoice'],
                'benefits_json' => [
                    '1 branch included',
                    'Up to 200 students',
                    'Up to 10 staff logins',
                    '14-day free trial',
                    'Web CRM for institute admin',
                    'Branch manager portal access',
                ],
                'is_public' => true,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'code' => 'growth',
                'name' => 'Growth',
                'description' => 'Multi-branch with exams, GST and leave.',
                'price_monthly' => 4999,
                'price_yearly' => 49990,
                'trial_days' => 14,
                'max_branches' => 5,
                'max_students' => 1000,
                'max_staff' => 50,
                'modules_json' => ['*'],
                'benefits_json' => [
                    'Up to 5 branches',
                    'Up to 1,000 students',
                    'Up to 50 staff logins',
                    '14-day free trial',
                    'All CRM modules included',
                    'Multi-branch operations',
                ],
                'is_public' => true,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Unlimited branches, students and modules.',
                'price_monthly' => 9999,
                'price_yearly' => 99990,
                'trial_days' => 14,
                'max_branches' => null,
                'max_students' => null,
                'max_staff' => null,
                'modules_json' => ['*'],
                'benefits_json' => [
                    'Unlimited branches',
                    'Unlimited students',
                    'Unlimited staff logins',
                    '14-day free trial',
                    'All CRM modules included',
                    'Priority onboarding support',
                ],
                'is_public' => true,
                'sort_order' => 4,
                'status' => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan);
        }
    }

    protected function seedDeveloper(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(config('tenancy.platform_team_id', 0));

        $user = User::query()->updateOrCreate(
            ['email' => 'developer@ivclasses.com'],
            [
                'name' => 'IV Developer',
                'password' => Hash::make('password'),
                'user_type' => UserType::PlatformAdmin,
                'institute_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );

        $role = Role::query()
            ->where('name', 'developer')
            ->where('guard_name', 'web')
            ->whereNull('team_id')
            ->first();

        if ($role && ! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    protected function seedDemoInstitute(): void
    {
        $plan = Plan::query()->where('code', 'trial')->first();
        if (! $plan) {
            return;
        }

        $institute = Institute::query()->updateOrCreate(
            ['slug' => 'demo-institute'],
            [
                'name' => 'Demo Institute',
                'email' => 'admin@demo-institute.test',
                'phone' => '9876543210',
                'city' => 'Mehsana',
                'state' => 'Gujarat',
                'status' => InstituteStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
                'timezone' => 'Asia/Kolkata',
            ]
        );

        Subscription::query()->updateOrCreate(
            [
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
            ],
            [
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
                'status' => SubscriptionStatus::Trial,
                'amount' => 0,
            ]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($institute->id);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@demo-institute.test'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'user_type' => UserType::InstituteAdmin,
                'institute_id' => $institute->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::query()->firstOrCreate(
            [
                'name' => 'institute_admin',
                'guard_name' => 'web',
                'team_id' => $institute->id,
            ],
            [
                'is_system' => true,
                'status' => 'active',
            ]
        );

        $adminRole->syncPermissions(Tenancy::institutePermissions());

        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }

        $branch = \App\Models\Branch::query()->updateOrCreate(
            [
                'institute_id' => $institute->id,
                'name' => 'Mehsana Main',
            ],
            [
                'code' => 'MEH-01',
                'contact_person' => 'Demo Manager',
                'email' => 'branch@demo-institute.test',
                'phone' => '9876543210',
                'city' => 'Mehsana',
                'state' => 'Gujarat',
                'address' => 'Station Road, Mehsana',
                'status' => 'active',
            ]
        );

        if (! User::query()->where('email', 'teacher@demo-institute.test')->exists()) {
            app(TeacherService::class)->create([
                'institute_id' => $institute->id,
                'name' => 'Priya Mehta',
                'email' => 'teacher@demo-institute.test',
                'phone' => '9876510001',
                'password' => 'password',
                'password_confirmation' => 'password',
                'specialization' => 'Maths',
                'address' => 'Near Bus Stand, Mehsana',
                'expected_login_time' => '08:00',
                'grace_minutes' => 10,
                'status' => 'active',
                'primary_branch_id' => $branch->id,
            ]);
        }

        $staffService = app(StaffService::class);
        $staffService->ensureDefaultRoles($institute->id);

        $principalRole = Role::query()
            ->where('name', 'principal')
            ->where('team_id', $institute->id)
            ->first();

        if ($principalRole && ! User::query()->where('email', 'principal@demo-institute.test')->exists()) {
            $staffService->create([
                'institute_id' => $institute->id,
                'name' => 'Ravi Patel',
                'email' => 'principal@demo-institute.test',
                'phone' => '9876510002',
                'password' => 'password',
                'password_confirmation' => 'password',
                'designation' => 'Branch Principal',
                'address' => 'Station Road, Mehsana',
                'city' => 'Mehsana',
                'state' => 'Gujarat',
                'pincode' => '384001',
                'expected_login_time' => '09:00',
                'grace_minutes' => 10,
                'status' => 'active',
                'primary_branch_id' => $branch->id,
                'role_id' => $principalRole->id,
                'set_as_branch_manager' => true,
            ]);
        }

        $standards = [
            ['name' => '5th', 'sort_order' => 5],
            ['name' => '8th', 'sort_order' => 8],
            ['name' => '10th', 'sort_order' => 10],
            ['name' => '12th', 'sort_order' => 12],
        ];
        foreach ($standards as $std) {
            \App\Models\Standard::query()->updateOrCreate(
                [
                    'institute_id' => $institute->id,
                    'name' => $std['name'],
                ],
                [
                    'sort_order' => $std['sort_order'],
                    'status' => 'active',
                ]
            );
        }

        $subjects = [
            ['name' => 'Maths', 'code' => 'MTH'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Gujarati', 'code' => 'GUJ'],
        ];
        foreach ($subjects as $sub) {
            \App\Models\Subject::query()->updateOrCreate(
                [
                    'institute_id' => $institute->id,
                    'name' => $sub['name'],
                ],
                [
                    'code' => $sub['code'],
                    'status' => 'active',
                ]
            );
        }
    }
}
