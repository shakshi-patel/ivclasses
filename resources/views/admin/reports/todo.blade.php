@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y report-page">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                @include('admin.reports._header', [
                    'reportTitle' => 'To Do Task Report',
                    'reportSubtitle' => 'Tasks by due date, status and assignee',
                    'reportIcon' => 'bx-task',
                    'reportBackUrl' => route('admin.reports'),
                ])

                <div class="admin-filter-section no-print mt-3">
                    <form method="GET" action="{{ route('admin.reports.todo') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">From</label>
                                <input type="date" class="form-control form-control-sm" name="from" value="{{ $filters['from'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">To</label>
                                <input type="date" class="form-control form-control-sm" name="to" value="{{ $filters['to'] }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select form-select-sm" name="status" data-no-search>
                                    <option value="">All</option>
                                    @foreach (\App\Models\Todo::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Assignee</label>
                                <select class="form-select form-select-sm" name="assignee_id">
                                    <option value="">All</option>
                                    @foreach ($assignees as $a)
                                        <option value="{{ $a['id'] }}" @selected((int) ($filters['assignee_id'] ?? 0) === (int) $a['id'])>
                                            {{ $a['label'] ?? $a['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm w-100 admin-btn-primary">
                                    <i class="bx bx-filter-alt me-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="admin-table-wrap mt-3">
                    <div class="admin-list-card-bar">
                        <h5>Tasks ({{ $rows->count() }})</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Sr.</th>
                                    <th>Task Date</th>
                                    <th>Tasks</th>
                                    <th>Assignees</th>
                                    <th>Created By</th>
                                    <th style="width:110px;">Status</th>
                                    <th style="width:110px;">Action Date</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $row->due_date?->format('d M Y') ?? '—' }}</td>
                                        <td style="white-space: pre-line;">{{ $row->tasksDisplay() }}</td>
                                        <td>{{ $row->assignees->pluck('name')->filter()->implode(', ') ?: ($row->assignee?->name ?? '—') }}</td>
                                        <td>{{ $row->creator?->name ?? '—' }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->statusPillClass() }}">
                                                {{ \App\Models\Todo::STATUSES[$row->status] ?? ucfirst($row->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $row->reviewed_at?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ $row->remark ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No tasks found for filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
