<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Todo;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TodoService
{
    /**
     * @return Builder<Todo>
     */
    public function queryForUser(?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = Todo::query();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);

            $query->where(function ($q) use ($user) {
                $q->whereHas('assignees', fn ($sq) => $sq->where('users.id', $user->id))
                    ->orWhere('assigned_to_user_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, Todo>
     */
    public function listForUser(?User $user = null): Collection
    {
        $todos = $this->queryForUser($user)
            ->with(['assignees:id,name,user_type', 'creator:id,name'])
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();

        return $this->decorateTodos($todos, $user);
    }

    /**
     * Todos assigned to a specific user (staff profile / assign-work tab).
     *
     * @return Collection<int, Todo>
     */
    public function listForAssignee(int $userId, ?int $instituteId = null): Collection
    {
        $todos = $this->assigneeQuery($userId, $instituteId)
            ->limit(100)
            ->get();

        return $this->decorateTodos($todos);
    }

    public function paginateForAssignee(int $userId, ?int $instituteId = null, int $perPage = 10, string $pageName = 'work_page'): LengthAwarePaginator
    {
        $paginator = $this->assigneeQuery($userId, $instituteId)
            ->paginate($perPage, ['*'], $pageName);

        $this->decorateTodos(collect($paginator->items()));

        return $paginator;
    }

    /**
     * @return Builder<Todo>
     */
    protected function assigneeQuery(int $userId, ?int $instituteId = null): Builder
    {
        return Todo::query()
            ->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to_user_id', $userId)
                    ->orWhereHas('assignees', fn ($sq) => $sq->where('users.id', $userId));
            })
            ->with(['assignees:id,name,user_type', 'creator:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id');
    }

    /**
     * @param  Collection<int, Todo>  $todos
     * @return Collection<int, Todo>
     */
    protected function decorateTodos(Collection $todos, ?User $viewer = null): Collection
    {
        $instituteId = $viewer && ! $viewer->isPlatformUser() ? (int) $viewer->institute_id : null;
        if (! $instituteId && $todos->isNotEmpty()) {
            $instituteId = (int) $todos->first()->institute_id;
        }

        $userIds = $todos->pluck('assignees')->flatten()->pluck('id')->unique()->values();
        $roleMap = $instituteId ? $this->roleLabelsForUsers($userIds, $instituteId) : collect();

        return $todos->each(function (Todo $todo) use ($roleMap) {
            $todo->setAttribute('assignee_display', $todo->assignees
                ->map(fn (User $assignee) => $this->formatUserWithRole($assignee, $roleMap->get($assignee->id)))
                ->filter()
                ->implode(', '));
            $todo->setAttribute('tasks_display', $todo->tasksDisplay());
        });
    }

    /**
     * @param  array<int, string>  $taskTitles
     * @param  array<int, int>  $assignedUserIds
     */
    public function createGrouped(int $instituteId, array $assignedUserIds, array $taskTitles, string $dueDate): Todo
    {
        return DB::transaction(function () use ($instituteId, $assignedUserIds, $taskTitles, $dueDate) {
            $items = array_values(array_filter(array_map('trim', $taskTitles)));

            $todo = Todo::query()->create([
                'institute_id' => $instituteId,
                'assigned_to_user_id' => $assignedUserIds[0] ?? null,
                'title' => $items[0] ?? 'Task',
                'task_items' => $items,
                'due_date' => $dueDate,
                'status' => Todo::STATUS_PENDING,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $todo->assignees()->sync($assignedUserIds);

            return $todo->fresh(['assignees', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $assignedUserIds
     * @param  array<int, string>  $taskTitles
     */
    public function update(Todo $todo, array $data, array $assignedUserIds, array $taskTitles): Todo
    {
        $items = array_values(array_filter(array_map('trim', $taskTitles)));

        $todo->update([
            'assigned_to_user_id' => $assignedUserIds[0] ?? null,
            'title' => $items[0] ?? $todo->title,
            'task_items' => $items,
            'due_date' => $data['due_date'] ?? $todo->due_date,
            'status' => $data['status'] ?? $todo->status,
            'updated_by' => auth()->id(),
        ]);

        $todo->assignees()->sync($assignedUserIds);

        return $todo->fresh(['assignees', 'creator']);
    }

    /**
     * @param  array<int, int>  $todoIds
     */
    public function carryForward(array $todoIds, string $dueDate): int
    {
        return Todo::query()
            ->whereIn('id', $todoIds)
            ->update([
                'due_date' => $dueDate,
                'status' => Todo::STATUS_PENDING,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
    }

    public function delete(Todo $todo): void
    {
        $todo->assignees()->detach();
        $todo->delete();
    }

    public function approve(Todo $todo, ?string $remark = null): Todo
    {
        $todo->update([
            'status' => Todo::STATUS_APPROVED,
            'remark' => $remark,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $todo->fresh(['assignees', 'creator', 'reviewer']);
    }

    public function reject(Todo $todo, string $remark): Todo
    {
        $todo->update([
            'status' => Todo::STATUS_REJECTED,
            'remark' => $remark,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $todo->fresh(['assignees', 'creator', 'reviewer']);
    }

    public function assertUserCanAccess(User $user, Todo $todo): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $todo->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && ! $user->isPlatformUser() && ! $user->can('todo.view')) {
            $assigned = $todo->relationLoaded('assignees')
                ? $todo->assignees->contains('id', $user->id)
                : $todo->assignees()->where('users.id', $user->id)->exists();

            abort_unless(
                $assigned || (int) $todo->assigned_to_user_id === (int) $user->id,
                403,
                'You can only access your own tasks.'
            );
        }
    }

    /**
     * @return Collection<int, array{id:int,label:string,name:string,role_label:string}>
     */
    public function assigneeOptions(?User $user = null, ?int $instituteId = null): Collection
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return collect();
        }

        if ($user->isPlatformUser()) {
            if (! $instituteId) {
                return collect();
            }
        } else {
            $instituteId = (int) $user->institute_id;
        }

        if (! $instituteId) {
            return collect();
        }

        $query = User::query()
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereNotIn('user_type', [UserType::Parent->value, UserType::Student->value])
            ->orderBy('name');

        if (! $user->isPlatformUser() && BranchScope::for($user)->isRestricted()) {
            $branchIds = BranchScope::for($user)->branchIds();
            $query->whereHas('branches', fn ($q) => $q->whereIn('branches.id', $branchIds));
        }

        $users = $query->get(['id', 'name', 'user_type']);
        $roleMap = $this->roleLabelsForUsers($users->pluck('id'), $instituteId);

        return $users
            ->map(function (User $assignee) use ($roleMap) {
                $roleLabel = $this->formatRoleLabel($roleMap->get($assignee->id), $assignee->user_type);

                return [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'role_label' => $roleLabel,
                    'label' => $assignee->name.' ('.$roleLabel.')',
                ];
            })
            ->filter(fn (array $option) => strtolower($option['role_label']) !== 'parent')
            ->values();
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, string>
     */
    protected function roleLabelsForUsers(Collection $userIds, int $instituteId): Collection
    {
        $userIds = $userIds->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        $rows = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->whereIn('mhr.model_id', $userIds)
            ->where('mhr.team_id', $instituteId)
            ->where('r.team_id', $instituteId)
            ->whereNotIn('r.name', ['developer', 'parent'])
            ->select(['mhr.model_id as user_id', 'r.name'])
            ->get();

        return $rows->mapWithKeys(fn ($row) => [
            (int) $row->user_id => $this->formatRoleLabel($row->name, null),
        ]);
    }

    protected function formatUserWithRole(User $user, ?string $roleLabel): string
    {
        $roleLabel = $roleLabel ?: $this->formatRoleLabel(null, $user->user_type);

        return $user->name.' ('.$roleLabel.')';
    }

    protected function formatRoleLabel(?string $roleName, ?UserType $userType): string
    {
        if ($roleName) {
            return match ($roleName) {
                'teacher' => 'Teacher',
                'principal' => 'Principal',
                'accountant' => 'Accountant',
                'receptionist' => 'Receptionist',
                'peon' => 'Peon',
                'institute_admin' => 'Admin',
                'parent' => 'Parent',
                default => ucwords(str_replace('_', ' ', $roleName)),
            };
        }

        return match ($userType) {
            UserType::Teacher => 'Teacher',
            UserType::InstituteAdmin => 'Admin',
            UserType::InstituteStaff => 'Staff',
            UserType::BranchManager => 'Branch Manager',
            default => 'Staff',
        };
    }
}
