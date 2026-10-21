<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebAccountProfileTest extends TestCase
{
    public function test_web_profile_save_updates_dashboard_greeting_and_branch_contact(): void
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

        DB::table('users')->where('id', $user->id)->update([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $user = $user->fresh();
        $campusName = $branch->name;

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Web Greeting QA',
                'phone' => '9000088888',
            ])
            ->assertRedirect(route('admin.profile', ['tab' => 'personal']));

        $this->assertSame('Web Greeting QA', DB::table('users')->where('id', $user->id)->value('name'));

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Web Greeting QA', false);

        $this->actingAs($user->fresh())
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('Web Greeting QA', false);

        $freshBranch = DB::table('branches')->where('id', $branch->id)->first();
        $this->assertSame($campusName, $freshBranch->name);
        $this->assertSame('Web Greeting QA', $freshBranch->contact_person);
        $this->assertSame('9000088888', $freshBranch->phone);
    }
}
