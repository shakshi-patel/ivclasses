<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Todo;
use App\Models\User;
use App\Services\TodoService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TodoController extends AdminController
{
    public function __construct(
        protected TodoService $todoService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();
        $user = auth()->user();

        $filters = $this->listFilterVars($request, []);
        $instituteId = $this->filterInstituteId($request);

        $todos = $this->todoService->listForUser($user);

        if ($this->isPlatform() && $request->filled('institute_id')) {
            $filterId = (int) $request->input('institute_id');
            $todos = $todos->filter(fn (Todo $todo) => (int) $todo->institute_id === $filterId)->values();
        }

        if ($request->filled('status') && array_key_exists($request->input('status'), Todo::STATUSES)) {
            $status = $request->input('status');
            $todos = $todos->filter(fn (Todo $todo) => $todo->status === $status)->values();
        }

        if ($request->filled('assignee_id')) {
            $assigneeId = (int) $request->input('assignee_id');
            $todos = $todos->filter(function (Todo $todo) use ($assigneeId) {
                if ((int) $todo->assigned_to_user_id === $assigneeId) {
                    return true;
                }

                return $todo->assignees->contains(fn ($assignee) => (int) $assignee->id === $assigneeId);
            })->values();
        }

        $canApprovePermission = $user && ($user->isPlatformUser() || $user->can('todo.approve'));
        $canRejectPermission = $user && ($user->isPlatformUser() || $user->can('todo.reject'));

        if ($user && ($canApprovePermission || $canRejectPermission)) {
            $todos->each(function (Todo $todo) use ($user, $canApprovePermission, $canRejectPermission) {
                $isReviewer = $todo->isReviewableBy($user);
                $todo->setAttribute('can_approve', $canApprovePermission && $isReviewer);
                $todo->setAttribute('can_reject', $canRejectPermission && $isReviewer);
            });
        }

        return view('admin.to_do_task.to_do_task', array_merge($filters, [
            'todos' => $todos,
            'statuses' => Todo::STATUSES,
            'assignees' => $this->todoService->assigneeOptions($user, $instituteId),
            'canAdd' => $user && ($user->isPlatformUser() || $user->can('todo.add')),
            'canEdit' => $user && ($user->isPlatformUser() || $user->can('todo.edit')),
            'canDelete' => $user && ($user->isPlatformUser() || $user->can('todo.delete')),
            'canCarryForward' => $user && ($user->isPlatformUser() || $user->can('todo.carry_forward')),
        ]));
    }

    public function create(): View
    {
        $this->ensureCan('todo.add');
        $user = auth()->user();

        return view('admin.to_do_task.add_to_do_task', [
            'todo' => null,
            'assignees' => $this->todoService->assigneeOptions($user),
            'defaultAssigneeIds' => old('assigned_to_user_ids', $this->defaultAssigneeIds($user)),
        ]);
    }

    public function edit(Todo $todo): View
    {
        $this->ensureCan('todo.edit');
        $this->assertVisible($todo);
        $user = auth()->user();

        return view('admin.to_do_task.add_to_do_task', [
            'todo' => $todo->load('assignees'),
            'assignees' => $this->todoService->assigneeOptions($user),
            'defaultAssigneeIds' => old('assigned_to_user_ids', $todo->assignees->pluck('id')->all()),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('todo.add');
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        $validated = $request->validate([
            'assigned_to_user_ids' => ['required', 'array', 'min:1'],
            'assigned_to_user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('institute_id', $instituteId)
                    ->whereNotIn('user_type', ['parent', 'student'])),
            ],
            'due_date' => ['required', 'date'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.title' => ['required', 'string', 'max:500'],
        ]);

        $this->todoService->createGrouped(
            $instituteId,
            array_map('intval', $validated['assigned_to_user_ids']),
            collect($validated['tasks'])->pluck('title')->all(),
            $validated['due_date']
        );

        if ($request->expectsJson()) {
            return Ajax::success('Tasks saved.', null, 201);
        }

        return redirect()->route('admin.to_do_task')->with('success', 'Tasks saved.');
    }

    public function update(Request $request, Todo $todo): JsonResponse|RedirectResponse
    {
        $this->ensureCan('todo.edit');
        $this->assertVisible($todo);

        $user = auth()->user();
        $instituteId = (int) $todo->institute_id;

        $validated = $request->validate([
            'assigned_to_user_ids' => ['required', 'array', 'min:1'],
            'assigned_to_user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('institute_id', $instituteId)
                    ->whereNotIn('user_type', ['parent', 'student'])),
            ],
            'due_date' => ['required', 'date'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.title' => ['required', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(array_keys(Todo::STATUSES))],
        ]);

        $this->todoService->update(
            $todo,
            $validated,
            array_map('intval', $validated['assigned_to_user_ids']),
            collect($validated['tasks'])->pluck('title')->all()
        );

        if ($request->expectsJson()) {
            return Ajax::success('Task updated.');
        }

        return redirect()->route('admin.to_do_task')->with('success', 'Task updated.');
    }

    public function destroy(Request $request, Todo $todo): JsonResponse|RedirectResponse
    {
        $this->ensureCan('todo.delete');
        $this->assertVisible($todo);
        $this->todoService->delete($todo);

        return $this->respondDestroyed($request, 'Task deleted.', 'admin.to_do_task');
    }

    public function approve(Request $request, Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.approve');
        $this->assertVisible($todo);
        $this->assertReviewer($todo);

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->todoService->approve($todo, $validated['remark'] ?? null);

        return Ajax::success('Task approved.');
    }

    public function reject(Request $request, Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.reject');
        $this->assertVisible($todo);
        $this->assertReviewer($todo);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:1000'],
        ]);

        $this->todoService->reject($todo, $validated['remark']);

        return Ajax::success('Task rejected.');
    }

    public function carryForward(Request $request): JsonResponse
    {
        $this->ensureCan('todo.carry_forward');

        $validated = $request->validate([
            'todo_ids' => ['required', 'array', 'min:1'],
            'todo_ids.*' => ['integer', 'exists:todos,id'],
            'due_date' => ['required', 'date'],
        ]);

        $user = auth()->user();
        $query = Todo::query()->whereIn('id', $validated['todo_ids']);
        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        $ids = $query->pluck('id')->all();
        $count = $this->todoService->carryForward($ids, $validated['due_date']);

        return Ajax::success("{$count} task(s) carried forward.");
    }

    protected function assertVisible(Todo $todo): void
    {
        $user = auth()->user();
        if ($user) {
            $this->todoService->assertUserCanAccess($user, $todo);

            return;
        }

        abort(404);
    }

    protected function assertReviewer(Todo $todo): void
    {
        $user = auth()->user();
        abort_unless($user && $todo->isReviewableBy($user), 403, 'Only the assigned user can review this task.');
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can('todo.view') || $user->can('todo.add'))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * @return array<int, int>
     */
    protected function defaultAssigneeIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $ids = $this->todoService->assigneeOptions($user)->pluck('id')->all();

        return in_array($user->id, $ids, true) ? [$user->id] : [];
    }
}
