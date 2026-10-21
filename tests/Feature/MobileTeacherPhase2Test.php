<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\User;
use App\Support\OwnBatchScope;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileTeacherPhase2Test extends TestCase
{
    public function test_teacher_dashboard_and_own_batches_are_scoped(): void
    {
        $user = $this->demoTeacher();
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:teacher']);

        $ownIds = OwnBatchScope::batchIds($user);
        $this->assertNotSame([], $ownIds, 'Demo teacher should be assigned to at least one batch.');

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'teacher')
            ->assertJsonStructure([
                'data' => [
                    'greeting',
                    'name',
                    'summary',
                    'attention',
                    'quick_actions',
                    'batches',
                    'upcoming_exams',
                    'recent_homework',
                    'notices',
                    'allowed_widgets',
                ],
            ]);

        $me = $this->getJson('/api/v1/auth/me')->assertOk();
        $meIds = collect($me->json('data.batches'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame(collect($ownIds)->sort()->values()->all(), $meIds);

        $list = $this->getJson('/api/v1/batches?per_page=50')->assertOk();
        $listIds = collect($list->json('data.items'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame(collect($ownIds)->sort()->values()->all(), $listIds);

        $this->getJson('/api/v1/batches/'.$ownIds[0])->assertOk()->assertJsonPath('data.id', $ownIds[0]);

        $other = Batch::query()
            ->where('institute_id', $user->institute_id)
            ->whereNotIn('id', $ownIds)
            ->value('id');
        if ($other) {
            $this->getJson('/api/v1/batches/'.$other)->assertNotFound();
        }
    }

    public function test_default_teacher_is_denied_modules_outside_option_b(): void
    {
        $user = $this->demoTeacher();
        if (! $user) {
            $this->markTestSkipped('Demo teacher not seeded.');
        }

        Sanctum::actingAs($user, ['mobile:teacher']);

        $this->getJson('/api/v1/exams')->assertForbidden();
        $this->getJson('/api/v1/homework')->assertForbidden();
        $this->getJson('/api/v1/students')->assertForbidden();
        $this->getJson('/api/v1/branch')->assertForbidden();
        $this->getJson('/api/v1/attendance/student?batch_id=1&date=2026-01-03')->assertForbidden();
    }

    private function demoTeacher(): ?User
    {
        return User::query()->where('email', 'teacher@demo-institute.test')->first();
    }
}
