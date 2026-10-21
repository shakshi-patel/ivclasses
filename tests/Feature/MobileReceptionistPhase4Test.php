<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileApiRole;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileReceptionistPhase4Test extends TestCase
{
    public function test_receptionist_can_read_front_desk_surfaces(): void
    {
        $user = $this->demoReceptionist();
        if (! $user) {
            $this->markTestSkipped('Demo receptionist not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:receptionist']);

        $this->assertSame(MobileApiRole::RECEPTIONIST, MobileApiRole::resolve($user));

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'institute')
            ->assertJsonPath('data.persona', 'receptionist');

        $this->getJson('/api/v1/inquiries?per_page=5')->assertOk();
        $this->getJson('/api/v1/follow-ups?per_page=5')->assertOk();
        $this->getJson('/api/v1/students?per_page=5')->assertOk();
        $this->getJson('/api/v1/batches?per_page=5')->assertOk();
    }

    public function test_default_receptionist_is_denied_non_front_desk_modules(): void
    {
        $user = $this->demoReceptionist();
        if (! $user) {
            $this->markTestSkipped('Demo receptionist not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:receptionist']);

        $this->getJson('/api/v1/fees')->assertForbidden();
        $this->getJson('/api/v1/teachers')->assertForbidden();
        $this->getJson('/api/v1/branch')->assertForbidden();
        $this->postJson('/api/v1/batches', ['name' => 'nope'])->assertForbidden();
        $this->assertFalse($user->can('inquiry.convert'));
        $this->assertFalse($user->can('inquiry.delete'));
        $this->assertFalse($user->can('student.edit'));
        $this->assertFalse($user->can('student.status'));
        $this->assertFalse($user->can('attendance.view'));
    }

    private function demoReceptionist(): ?User
    {
        try {
            return User::query()->where('email', 'receptionist@demo-institute.test')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
