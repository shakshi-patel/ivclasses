<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAuthAccountTest extends TestCase
{
    public function test_profile_update_persists_in_database(): void
    {
        $user = User::query()->where('email', 'teacher@demo-institute.test')->first();
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:teacher']);

        $response = $this->postJson('/api/v1/profile', [
            'name' => 'Persist Profile QA',
            'phone' => '9111122222',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $row = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame('Persist Profile QA', $row->name);
        $this->assertSame('9111122222', $row->phone);
    }

    public function test_profile_update_syncs_managed_branch_contact(): void
    {
        $branch = DB::table('branches')
            ->whereNotNull('manager_user_id')
            ->whereNull('deleted_at')
            ->first();

        if (! $branch) {
            $this->markTestSkipped('No branch with manager_user_id.');
        }

        $user = User::query()->find($branch->manager_user_id);
        if (! $user) {
            $this->markTestSkipped('Branch manager user missing.');
        }

        $campusName = $branch->name;

        Sanctum::actingAs($user, ['mobile:branch_manager']);

        $this->postJson('/api/v1/profile', [
            'name' => 'Synced Manager QA',
            'phone' => '9000090000',
        ])->assertOk()->assertJsonPath('success', true);

        $fresh = DB::table('branches')->where('id', $branch->id)->first();
        $this->assertSame($campusName, $fresh->name, 'Campus name must not change.');
        $this->assertSame('Synced Manager QA', $fresh->contact_person);
        $this->assertSame('9000090000', $fresh->phone);
        $this->assertSame($user->email, $fresh->email);
    }

    public function test_change_password_persists_and_old_password_fails(): void
    {
        $user = User::query()->where('email', 'teacher@demo-institute.test')->first();
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make('OldPass123'),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user->fresh(), ['mobile:teacher']);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPass123',
            'password' => 'BrandNew99!',
            'password_confirmation' => 'BrandNew99!',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $hash = DB::table('users')->where('id', $user->id)->value('password');
        $this->assertTrue(Hash::check('BrandNew99!', $hash));
        $this->assertFalse(Hash::check('OldPass123', $hash));
    }

    public function test_logout_rejects_the_old_token_on_profile_update(): void
    {
        $user = User::query()->where('email', 'teacher@demo-institute.test')->first();
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        $plain = $user->createToken('mobile', ['mobile:teacher'])->plainTextToken;

        $this->withToken($plain)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($plain)
            ->postJson('/api/v1/profile', [
                'name' => 'Should Not Save',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
