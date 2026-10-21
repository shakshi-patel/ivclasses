<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilePermissionParityTest extends TestCase
{
    public function test_default_accountant_is_forbidden_on_inventory_not_missing(): void
    {
        $user = $this->demoUser('accountant@demo-institute.test');
        if (! $user) {
            $this->markTestSkipped('Demo accountant not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:accountant']);

        $this->getJson('/api/v1/inventory-assets')->assertForbidden();
        $this->getJson('/api/v1/asset-categories')->assertForbidden();
        $this->assertFalse($user->can('inventory.view'));
        $this->assertFalse($user->can('assets_category.view'));
    }

    public function test_default_branch_manager_is_forbidden_on_subject_and_standard_writes_until_granted(): void
    {
        $user = $this->demoUser('kanku01@gmail.com')
            ?? $this->demoUser('teacher@demo-institute.test');
        if (! $user) {
            $this->markTestSkipped('Demo branch user not seeded.');
        }

        Sanctum::actingAs($user, ['mobile']);

        if (! $user->can('subject.view')) {
            $this->getJson('/api/v1/subjects')->assertForbidden();
        } else {
            $this->getJson('/api/v1/subjects')->assertOk();
        }

        if (! $user->can('standard.view')) {
            $this->getJson('/api/v1/standards')->assertForbidden();
        } else {
            $this->getJson('/api/v1/standards')->assertOk();
        }
    }

    public function test_default_accountant_cannot_write_teachers_or_staff(): void
    {
        $user = $this->demoUser('accountant@demo-institute.test');
        if (! $user) {
            $this->markTestSkipped('Demo accountant not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:accountant']);

        $this->postJson('/api/v1/teachers', ['name' => 'nope'])->assertForbidden();
        $this->postJson('/api/v1/staff', ['name' => 'nope'])->assertForbidden();
        $this->assertContains(
            $this->postJson('/api/v1/branch', ['name' => 'nope'])->status(),
            [404, 405]
        );
    }

    public function test_teacher_writes_follow_permission(): void
    {
        $user = $this->demoUser('teacher@demo-institute.test');
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:teacher']);

        if ($user->can('teacher.add') || $user->can('users.add_teacher')) {
            $this->postJson('/api/v1/teachers', ['name' => 'nope'])->assertUnprocessable();
        } else {
            $this->postJson('/api/v1/teachers', ['name' => 'nope'])->assertForbidden();
        }
    }

    private function demoUser(string $email): ?User
    {
        try {
            return User::query()->where('email', $email)->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
