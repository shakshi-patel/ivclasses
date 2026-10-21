<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\TodoResource;
use App\Models\Todo;
use App\Services\TodoService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TodoController extends ApiController
{
    public function __construct(
        protected TodoService $todoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCanAny(['todo.view', 'todo.add']);

        $query = $this->todoService->queryForUser($this->actor())
            ->with(['assignees:id,name', 'creator:id,name']);

        if ($request->filled('status') && array_key_exists($request->input('status'), Todo::STATUSES)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderBy('due_date')->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Todo $todo) => (new TodoResource($todo))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('todo.add');

        $instituteId = (int) $this->actor()->institute_id;
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

        $todo = $this->todoService->createGrouped(
            $instituteId,
            array_map('intval', $validated['assigned_to_user_ids']),
            collect($validated['tasks'])->pluck('title')->all(),
            $validated['due_date']
        );
        $todo->load(['assignees:id,name', 'creator:id,name']);

        return Ajax::success('Tasks saved.', (new TodoResource($todo))->resolve(), 201);
    }

    public function update(Request $request, Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.edit');
        $this->todoService->assertUserCanAccess($this->actor(), $todo);

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

        $todo = $this->todoService->update(
            $todo,
            $validated,
            array_map('intval', $validated['assigned_to_user_ids']),
            collect($validated['tasks'])->pluck('title')->all()
        );
        $todo->load(['assignees:id,name', 'creator:id,name']);

        return Ajax::success('Task updated.', (new TodoResource($todo))->resolve());
    }

    public function destroy(Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.delete');
        $this->todoService->assertUserCanAccess($this->actor(), $todo);
        $this->todoService->delete($todo);

        return Ajax::success('Task deleted.');
    }

    public function approve(Request $request, Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.approve');
        $this->todoService->assertUserCanAccess($this->actor(), $todo);
        $this->assertReviewer($todo);

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);

        $todo = $this->todoService->approve($todo, $validated['remark'] ?? null);
        $todo->load(['assignees:id,name', 'creator:id,name']);

        return Ajax::success('Task approved.', (new TodoResource($todo))->resolve());
    }

    public function reject(Request $request, Todo $todo): JsonResponse
    {
        $this->ensureCan('todo.reject');
        $this->todoService->assertUserCanAccess($this->actor(), $todo);
        $this->assertReviewer($todo);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:1000'],
        ]);

        $todo = $this->todoService->reject($todo, $validated['remark']);
        $todo->load(['assignees:id,name', 'creator:id,name']);

        return Ajax::success('Task rejected.', (new TodoResource($todo))->resolve());
    }

    public function carryForward(Request $request): JsonResponse
    {
        $this->ensureCan('todo.carry_forward');

        $validated = $request->validate([
            'todo_ids' => ['required', 'array', 'min:1'],
            'todo_ids.*' => ['integer', 'exists:todos,id'],
            'due_date' => ['required', 'date'],
        ]);

        $query = Todo::query()->whereIn('id', $validated['todo_ids'])
            ->where('institute_id', $this->actor()->institute_id);

        $ids = $query->pluck('id')->all();
        $count = $this->todoService->carryForward($ids, $validated['due_date']);

        return Ajax::success("{$count} task(s) carried forward.", ['count' => $count]);
    }

    protected function assertReviewer(Todo $todo): void
    {
        abort_unless($todo->isReviewableBy($this->actor()), 403, 'Only the assigned user can review this task.');
    }
}
