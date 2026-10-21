<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use App\Support\ParentBatchScope;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileParentPhase3Test extends TestCase
{
    public function test_parent_dashboard_and_children_are_scoped(): void
    {
        $user = $this->demoParent();
        if (! $user) {
            $this->markTestSkipped('Demo parent not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:parent']);

        $childIds = ParentBatchScope::studentIds($user)->map(fn ($id) => (int) $id)->all();
        $this->assertNotSame([], $childIds, 'Demo parent should have at least one child.');

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'parent')
            ->assertJsonStructure([
                'data' => [
                    'children',
                    'selected_student_id',
                    'summary',
                    'quick_actions',
                    'enrollments',
                    'upcoming_exams',
                    'homework',
                    'results',
                    'notices',
                    'holidays',
                    'leaves',
                    'allowed_widgets',
                ],
            ]);

        $me = $this->getJson('/api/v1/auth/me')->assertOk();
        $meIds = collect($me->json('data.children'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame(collect($childIds)->sort()->values()->all(), $meIds);

        $list = $this->getJson('/api/v1/students?per_page=50')->assertOk();
        $listIds = collect($list->json('data.items'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame(collect($childIds)->sort()->values()->all(), $listIds);

        $this->getJson('/api/v1/students/'.$childIds[0])->assertOk()->assertJsonPath('data.id', $childIds[0]);
        $this->getJson('/api/v1/homework')->assertOk();
        $this->getJson('/api/v1/exams')->assertOk();
        $this->getJson('/api/v1/notices')->assertOk();
        $this->getJson('/api/v1/leave-requests/student')->assertOk();
    }

    public function test_default_parent_is_denied_staff_modules(): void
    {
        $user = $this->demoParent();
        if (! $user) {
            $this->markTestSkipped('Demo parent not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:parent']);

        $this->getJson('/api/v1/branch')->assertForbidden();
        $this->getJson('/api/v1/inquiries')->assertForbidden();
        $this->postJson('/api/v1/homework', ['title' => 'nope'])->assertForbidden();
        $this->postJson('/api/v1/exams', ['title' => 'nope'])->assertForbidden();
    }

    private function demoParent(): ?User
    {
        return User::query()
            ->where('user_type', UserType::Parent)
            ->whereNotNull('institute_id')
            ->orderBy('id')
            ->first();
    }
}
