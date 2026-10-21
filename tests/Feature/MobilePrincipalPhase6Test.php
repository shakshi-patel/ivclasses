<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileApiRole;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilePrincipalPhase6Test extends TestCase
{
    public function test_principal_gets_institute_dashboard_and_bm_reads(): void
    {
        $user = $this->demoPrincipal();
        if (! $user) {
            $this->markTestSkipped('Demo principal not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:principal']);

        $this->assertSame(MobileApiRole::PRINCIPAL, MobileApiRole::resolve($user));

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'institute')
            ->assertJsonPath('data.persona', 'principal');

        $this->getJson('/api/v1/branch')->assertOk();
        $this->getJson('/api/v1/batches?per_page=5')->assertOk();
        $this->getJson('/api/v1/students?per_page=5')->assertOk();
        $this->getJson('/api/v1/inquiries?per_page=5')->assertOk();
        $this->getJson('/api/v1/teachers?per_page=5')->assertOk();
        $this->getJson('/api/v1/fees?per_page=5')->assertOk();
    }

    public function test_principal_inventory_is_not_built(): void
    {
        $user = $this->demoPrincipal();
        if (! $user) {
            $this->markTestSkipped('Demo principal not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:principal']);

        $this->getJson('/api/v1/inventory-assets')->assertNotFound();
    }

    private function demoPrincipal(): ?User
    {
        try {
            return User::query()->where('email', 'principal@demo-institute.test')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
