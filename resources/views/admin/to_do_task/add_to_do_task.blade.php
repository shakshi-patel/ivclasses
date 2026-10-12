@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($todo) && $todo;
        $taskLines = old('tasks')
            ? collect(old('tasks'))->pluck('title')->filter()->values()->all()
            : ($isEdit ? $todo->taskLines() : ['']);
        if ($taskLines === []) {
            $taskLines = [''];
        }
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-task"></i> {{ $isEdit ? 'Edit' : 'Add' }} To Do Task</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.to_do_task') }}">To Do Task</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Task' : 'Create Task' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.to_do_task.update', $todo) : route('admin.to_do_task.store') }}"
                                id="createTodoForm">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif

                                <div id="todoTaskRows">
                                    @foreach ($taskLines as $index => $line)
                                        <div class="todo-task-row">
                                            <div class="todo-task-fields">
                                                <input type="text" class="form-control" name="tasks[{{ $index }}][title]"
                                                    placeholder="Enter task title" required value="{{ $line }}">
                                            </div>
                                            <div class="todo-task-actions">
                                                <button type="button" class="todo-row-btn is-remove" title="Remove"
                                                    @disabled(count($taskLines) <= 1)>
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                                <button type="button" class="todo-row-btn is-add" title="Add row">
                                                    <i class="bx bx-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="row g-3 mt-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Task Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="due_date" required
                                            value="{{ old('due_date', $isEdit ? $todo->due_date?->format('Y-m-d') : date('Y-m-d')) }}">
                                    </div>
                                    @if ($isEdit)
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                @foreach (\App\Models\Todo::STATUSES as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('status', $todo->status) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Assign User <span class="req">*</span></label>
                                    <select class="form-select" name="assigned_to_user_ids[]" id="todoAssigneeSelect" multiple required
                                        data-placeholder="Select staff / teacher">
                                        @foreach ($assignees as $assignee)
                                            <option value="{{ $assignee['id'] }}"
                                                @selected(in_array($assignee['id'], $defaultAssigneeIds ?? [], true))>
                                                {{ $assignee['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Parent users are not shown. Select one or more staff / teachers.</div>
                                </div>

                                <div class="master-stu-form-actions justify-content-end">
                                    <a href="{{ route('admin.to_do_task') }}" class="btn btn-outline-secondary">Back</a>
                                    <button type="submit" class="btn admin-btn-primary"><i class="bx bx-check me-1"></i> Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('createTodoForm');
    const wrap = document.getElementById('todoTaskRows');
    if (wrap) {
        let rowIndex = wrap.querySelectorAll('.todo-task-row').length;
        function refreshRemoveState() {
            const rows = wrap.querySelectorAll('.todo-task-row');
            rows.forEach(row => {
                const btn = row.querySelector('.is-remove');
                if (btn) btn.disabled = rows.length <= 1;
            });
        }
        wrap.addEventListener('click', e => {
            if (e.target.closest('.is-add')) {
                const row = document.createElement('div');
                row.className = 'todo-task-row';
                row.innerHTML = '<div class="todo-task-fields"><input type="text" class="form-control" name="tasks[' + rowIndex + '][title]" placeholder="Enter task title" required></div><div class="todo-task-actions"><button type="button" class="todo-row-btn is-remove" title="Remove"><i class="bx bx-trash"></i></button><button type="button" class="todo-row-btn is-add" title="Add row"><i class="bx bx-plus"></i></button></div>';
                wrap.appendChild(row);
                rowIndex++;
                refreshRemoveState();
            }
            const removeBtn = e.target.closest('.is-remove');
            if (removeBtn && !removeBtn.disabled) {
                removeBtn.closest('.todo-task-row')?.remove();
                refreshRemoveState();
            }
        });
        refreshRemoveState();
    }
    if (typeof window.initAdminSearchableSelects === 'function') {
        window.initAdminSearchableSelects(form);
    }
    form?.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(form, { redirectUrl: @json(route('admin.to_do_task')) });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush
