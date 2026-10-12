@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title"><i class="bx bx-task"></i> To Do Task</h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($canCarryForward)
                            <button type="button" class="btn todo-carry-btn d-none" id="todoCarryForwardBtn">
                                <i class="bx bx-calendar me-1"></i> Carry Forward Selected
                            </button>
                        @endif
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('todo.view'))
                            <a href="{{ route('admin.export.todos') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if ($canAdd)
                            <a href="{{ route('admin.to_do_task.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Add To Do Task
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">To Do Task</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.to_do_task')"
                    :show-institute="$isPlatform"
                    :show-status="true"
                    :statuses="$statuses ?? \App\Models\Todo::STATUSES"
                    :institutes="$institutes ?? collect()"
                >
                    @if (($assignees ?? collect())->isNotEmpty())
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Assignee</label>
                            <select class="form-select form-select-sm" name="assignee_id">
                                <option value="">All Assignees</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee['id'] }}" @selected((string) request('assignee_id') === (string) $assignee['id'])>
                                        {{ $assignee['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>To Do Task List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" id="todoTaskTable" style="min-width: 1000px;">
                            <thead>
                                <tr>
                                    @if ($canCarryForward)
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" class="form-check-input todo-check-all" id="todoCheckAll" title="Select all">
                                        </th>
                                    @endif
                                    <th style="width: 70px;">Sr No</th>
                                    <th>Tasks</th>
                                    <th style="width: 130px;">Task Date</th>
                                    <th>Assigned To</th>
                                    <th>Created By</th>
                                    <th style="width: 110px;">Status</th>
                                    <th class="text-center" style="width: 190px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todos as $todo)
                                    <tr>
                                        @if ($canCarryForward)
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input todo-row-check" value="{{ $todo->id }}">
                                            </td>
                                        @endif
                                        <td>{{ $loop->iteration }}</td>
                                        <td style="max-width: 360px; white-space: pre-line;">{{ $todo->tasks_display ?? $todo->tasksDisplay() }}</td>
                                        <td>{{ $todo->due_date?->format('d/m/Y') }}</td>
                                        <td>{{ $todo->assignee_display ?: '—' }}</td>
                                        <td>{{ $todo->creator?->name ?? '—' }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $todo->statusPillClass() }}" title="{{ $todo->remark }}">
                                                {{ \App\Models\Todo::STATUSES[$todo->status] ?? ucfirst($todo->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (($todo->can_approve ?? false) || ($todo->can_reject ?? false))
                                                <div class="d-flex align-items-center justify-content-center gap-1 mb-1 todo-review-cell">
                                                    <input type="text" class="form-control form-control-sm todo-remark-input"
                                                        style="width: 110px;" placeholder="Remark">
                                                    @if ($todo->can_approve ?? false)
                                                        <button type="button" class="admin-action-icon btn-todo-review" title="Approve"
                                                            data-action="approve"
                                                            data-url="{{ route('admin.to_do_task.approve', $todo) }}">
                                                            <i class="bx bx-check-circle text-success"></i>
                                                        </button>
                                                    @endif
                                                    @if ($todo->can_reject ?? false)
                                                        <button type="button" class="admin-action-icon btn-todo-review" title="Reject"
                                                            data-action="reject"
                                                            data-url="{{ route('admin.to_do_task.reject', $todo) }}">
                                                            <i class="bx bx-x-circle text-danger"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                            @if ($canEdit)
                                                <a href="{{ route('admin.to_do_task.edit', $todo) }}" class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-todo-del" title="Delete"
                                                    data-url="{{ route('admin.to_do_task.destroy', $todo) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $canCarryForward ? 8 : 7 }}" class="text-center text-muted py-4">No tasks yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    @if ($canCarryForward)
    <div class="modal fade admin-modal" id="carryForwardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carry Forward Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Forward To Date</label>
                    <input type="date" class="form-control" id="carryForwardDate" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="forwardTaskBtn">
                        <i class="bx bx-right-arrow-alt me-1"></i> Forward Task
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    function updateCarryBtn() {
        const checked = document.querySelectorAll('.todo-row-check:checked').length;
        document.getElementById('todoCarryForwardBtn')?.classList.toggle('d-none', checked === 0);
    }
    document.getElementById('todoCheckAll')?.addEventListener('change', function() {
        document.querySelectorAll('.todo-row-check').forEach(cb => cb.checked = this.checked);
        updateCarryBtn();
    });
    document.querySelectorAll('.todo-row-check').forEach(cb => cb.addEventListener('change', updateCarryBtn));
    document.getElementById('todoCarryForwardBtn')?.addEventListener('click', () => {
        if (!document.querySelectorAll('.todo-row-check:checked').length) return;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('carryForwardModal')).show();
    });
    document.getElementById('forwardTaskBtn')?.addEventListener('click', async () => {
        const ids = [...document.querySelectorAll('.todo-row-check:checked')].map(cb => cb.value);
        const dueDate = document.getElementById('carryForwardDate')?.value;
        if (!dueDate) return alert('Please select date.');
        try {
            const r = await fetch(@json(route('admin.to_do_task.carry_forward')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ todo_ids: ids, due_date: dueDate })
            });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            if (window.AdminAjax) await AdminAjax.success(d.message);
            location.reload();
        } catch (e) {
            if (window.AdminAjax) AdminAjax.error(e.message); else alert(e.message);
        }
    });
    document.body.addEventListener('click', async e => {
        const del = e.target.closest('.btn-todo-del');
        if (!del) return;
        const ok = window.AdminAjax ? await AdminAjax.confirm({ title: 'Delete task?', icon: 'warning' }) : confirm('Delete?');
        if (!ok) return;
        try {
            if (window.AdminAjax) await AdminAjax.destroy(del.dataset.url);
            else await fetch(del.dataset.url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            del.closest('tr')?.remove();
        } catch (err) {
            if (window.AdminAjax) AdminAjax.error(err.message);
        }
    });

    document.body.addEventListener('click', async e => {
        const btn = e.target.closest('.btn-todo-review');
        if (!btn) return;
        const action = btn.dataset.action;
        const remarkInput = btn.closest('.todo-review-cell')?.querySelector('.todo-remark-input');
        const remark = remarkInput ? remarkInput.value.trim() : '';

        if (action === 'reject' && !remark) {
            if (window.AdminAjax) await AdminAjax.error('Remark is required to reject this task.');
            else alert('Remark is required to reject this task.');
            remarkInput?.focus();
            return;
        }

        const ok = window.AdminAjax
            ? await AdminAjax.confirm({
                title: action === 'reject' ? 'Reject this task?' : 'Approve this task?',
                text: action === 'reject' ? 'This task will be marked as rejected.' : 'This task will be marked as approved.',
                confirmText: action === 'reject' ? 'Yes, reject' : 'Yes, approve',
                icon: action === 'reject' ? 'warning' : 'question'
            })
            : confirm(action === 'reject' ? 'Reject this task?' : 'Approve this task?');
        if (!ok) return;

        btn.disabled = true;
        try {
            const data = window.AdminAjax
                ? await AdminAjax.post(btn.dataset.url, { remark })
                : await (await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ remark })
                })).json();
            if (window.AdminAjax) await AdminAjax.success(data.message || 'Task updated.');
            location.reload();
        } catch (err) {
            if (window.AdminAjax) await AdminAjax.error(err.message || 'Action failed.');
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
