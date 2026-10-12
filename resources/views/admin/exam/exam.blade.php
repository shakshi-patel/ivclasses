@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div><h4 class="admin-page-title"><i class="bx bx-notepad"></i> Exam</h4></div>
                    @if ($canAdd)
                        <a href="{{ route('admin.exam.add') }}" class="btn admin-btn-primary"><i class="bx bx-plus me-1"></i> Create Exam</a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Exam</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.exam')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-batch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                    :batches="$batches ?? collect()"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Exam List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1200px;">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Branch &amp; Batch</th>
                                    <th>Subject</th>
                                    <th>Exam Topic &amp; Date</th>
                                    <th>Timing &amp; Max Marks</th>
                                    <th>Created By</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exams as $exam)
                                    <tr>
                                        <td data-role="sr">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $exam->branch?->name ?? '—' }}</div>
                                            <div class="text-muted small">{{ $exam->batch?->name ?? '—' }}</div>
                                        </td>
                                        <td>{{ $exam->subject?->name ?? '—' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $exam->title }}</div>
                                            <div class="text-muted small">{{ $exam->exam_date?->format('d M Y') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $exam->start_time ? substr($exam->start_time, 0, 5) : '—' }} To {{ $exam->end_time ? substr($exam->end_time, 0, 5) : '—' }}</div>
                                            <div class="text-muted small">Max Marks: {{ $exam->total_marks }}</div>
                                        </td>
                                        <td>{{ $exam->creator?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($canDownload && $exam->attachment_path)
                                                <a href="{{ route('admin.exam.download', $exam) }}" class="admin-action-icon" title="Download"><i class="bx bx-download"></i></a>
                                            @endif
                                            @if ($canEdit)
                                                <a href="{{ route('admin.exam.edit', $exam) }}" class="admin-action-icon" title="Edit"><i class="bx bx-pencil"></i></a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-exam-del" data-url="{{ route('admin.exam.destroy', $exam) }}" title="Delete"><i class="bx bx-trash"></i></button>
                                            @endif
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-exam-view" title="View"
                                                    data-title="{{ $exam->title }}"
                                                    data-exam-date="{{ $exam->exam_date?->format('d M Y') }}"
                                                    data-start-time="{{ $exam->start_time ? substr($exam->start_time, 0, 5) : '' }}"
                                                    data-end-time="{{ $exam->end_time ? substr($exam->end_time, 0, 5) : '' }}"
                                                    data-hours="{{ $exam->duration_label }}"
                                                    data-max-marks="{{ $exam->total_marks }}"
                                                    data-exam-text="{{ $exam->description }}"><i class="bx bx-show"></i></button>
                                            @endif
                                            @if (! ($canDownload && $exam->attachment_path) && ! $canEdit && ! $canDelete && ! $canShow)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No exams yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="examViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Exam Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="ie-view-grid">
                        <div class="ie-view-item"><span class="k">Exam Title:</span><span class="v" id="examTitle">—</span></div>
                        <div class="ie-view-item"><span class="k">Exam Date:</span><span class="v" id="examDate">—</span></div>
                        <div class="ie-view-item"><span class="k">Exam Timing:</span><span class="v" id="examTiming">—</span></div>
                        <div class="ie-view-item"><span class="k">Exam Hours:</span><span class="v" id="examHours">—</span></div>
                        <div class="ie-view-item"><span class="k">Maximum Marks:</span><span class="v" id="examMarks">—</span></div>
                    </div>
                    <div class="inq-view-remarks mt-3"><strong>Exam Text:</strong> <span id="examText">—</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const modal = new bootstrap.Modal(document.getElementById('examViewModal'));
    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-exam-view');
        if (v) {
            document.getElementById('examTitle').textContent = v.dataset.title || '—';
            document.getElementById('examDate').textContent = v.dataset.examDate || '—';
            document.getElementById('examTiming').textContent = (v.dataset.startTime || '—') + ' To ' + (v.dataset.endTime || '—');
            document.getElementById('examHours').textContent = v.dataset.hours || '—';
            document.getElementById('examMarks').textContent = v.dataset.maxMarks || '—';
            document.getElementById('examText').textContent = v.dataset.examText || '—';
            modal.show();
            return;
        }
        const del = e.target.closest('.btn-exam-del');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete exam?', text: 'This cannot be undone easily.', confirmText: 'Yes, delete', icon: 'warning' })
                : confirm('Delete?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    const data = await AdminAjax.destroy(del.dataset.url);
                    AdminAjax.removeRow(del.closest('tr'));
                    await AdminAjax.success(data.message || 'Exam deleted.');
                }
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Delete failed.');
            }
        }
    });
})();
</script>
@endpush
