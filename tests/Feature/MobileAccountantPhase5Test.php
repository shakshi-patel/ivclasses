<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileApiRole;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAccountantPhase5Test extends TestCase
{
    public function test_accountant_can_read_finance_surfaces(): void
    {
        $user = $this->demoAccountant();
        if (! $user) {
            $this->markTestSkipped('Demo accountant not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:accountant']);

        $this->assertSame(MobileApiRole::ACCOUNTANT, MobileApiRole::resolve($user));

        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/fees?per_page=5')->assertOk();
        $this->getJson('/api/v1/fees/follow-ups?per_page=5')->assertOk();
        $this->getJson('/api/v1/gst-invoices?per_page=5')->assertOk();
        $this->getJson('/api/v1/income-expense?per_page=5')->assertOk();
        $this->getJson('/api/v1/income-expense-categories?per_page=5')->assertOk();
        $this->getJson('/api/v1/students?per_page=5')->assertOk();
    }

    public function test_default_accountant_is_denied_non_finance_writes_and_deletes(): void
    {
        $user = $this->demoAccountant();
        if (! $user) {
            $this->markTestSkipped('Demo accountant not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:accountant']);

        $this->getJson('/api/v1/inquiries')->assertForbidden();
        $this->postJson('/api/v1/batches', ['name' => 'nope'])->assertForbidden();
        $this->getJson('/api/v1/fees/follow-ups/export-pdf')->assertForbidden();
        $this->getJson('/api/v1/inventory-assets')->assertForbidden();
        $this->assertFalse($user->can('fees.delete'));
        $this->assertFalse($user->can('income_expense.delete'));
        $this->assertFalse($user->can('fees_follow_up.export'));
    }

    private function demoAccountant(): ?User
    {
        try {
            return User::query()->where('email', 'accountant@demo-institute.test')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
