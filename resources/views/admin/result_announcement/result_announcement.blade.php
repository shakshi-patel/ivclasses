@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div><h4 class="admin-page-title"><i class="bx bx-trophy"></i> {{ !empty($isParentView) ? 'Results' : 'Result Announcement' }}</h4></div>
                    @if ($canAdd)
                        <a href="{{ route('admin.result_announcement.add') }}" class="btn admin-btn-primary"><i class="bx bx-plus me-1"></i> Add Result Announcement</a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ !empty($isParentView) ? 'Results' : 'Result Announcement' }}</li>
                    </ol>
                </nav>

                @if (!empty($isParentView) && ($parentStudents ?? collect())->count() > 1)
                    <form method="get" class="row g-2 align-items-end mt-3">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label mb-1">Child</label>
                            <select name="student_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All children</option>
                                @foreach ($parentStudents as $child)
                                    <option value="{{ $child->id }}" @selected(($selectedStudentId ?? null) === $child->id)>{{ $child->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @elseif (empty($isParentView))
                    <x-admin.list-filters
                        :action="route('admin.result_announcement')"
                        :show-institute="$isPlatform"
                        :show-branch="true"
                        :show-batch="true"
                        :institutes="$institutes ?? collect()"
                        :branches="$branches ?? collect()"
                        :batches="$batches ?? collect()"
                    />
                @endif

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>{{ !empty($isParentView) ? 'Published Results' : 'Result Announcement List' }}</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1080px;">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Branch</th>
                                    <th>Batch</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($announcements as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->exam?->branch?->name ?? '—' }}</td>
                                        <td>{{ $item->exam?->batch?->name ?? '—' }}</td>
                                        <td>{{ $item->exam?->subject?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ $item->exam?->title }} | {{ $item->exam?->exam_date?->format('Y-m-d') }}</td>
                                        @php($studentQuery = !empty($selectedStudentId) ? '?student_id=' . $selectedStudentId : '')
                                        <td class="text-center">
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-result-view" title="View"
                                                    data-url="{{ route('admin.result_announcement.preview', $item) }}{{ $studentQuery }}"
                                                    data-pdf="{{ $canExport ? route('admin.result_announcement.export_pdf', $item) . $studentQuery : '' }}"><i class="bx bx-show"></i></button>
                                            @endif
                                            @if ($canExport)
                                                <a href="{{ route('admin.result_announcement.export_pdf', $item) }}{{ $studentQuery }}"
                                                    class="admin-action-icon" title="Export PDF"><i class="bx bx-download"></i></a>
                                            @endif
                                            @if ($canEdit && empty($isParentView))
                                                <a href="{{ route('admin.result_announcement.edit', $item) }}" class="admin-action-icon" title="Edit"><i class="bx bx-pencil"></i></a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-result-del" title="Delete"
                                                    data-url="{{ route('admin.result_announcement.destroy', $item) }}"><i class="bx bx-trash"></i></button>
                                            @endif
                                            @if (! $canShow && ! $canExport && ! ($canEdit && empty($isParentView)) && ! $canDelete)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No result announcements yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="resultPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Result Report Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="resultPreviewBody"><p class="text-muted mb-0">Loading…</p></div>
                <div class="modal-footer">
                    @if ($canExport)
                        <a href="#" id="resultPreviewExportPdf" class="btn btn-sm btn-export-students">
                            <i class="bx bx-download me-1"></i> Export PDF
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const modal = new bootstrap.Modal(document.getElementById('resultPreviewModal'));
    document.body.addEventListener('click', async e => {
        const view = e.target.closest('.btn-result-view');
        if (view) {
            document.getElementById('resultPreviewBody').innerHTML = '<p class="text-muted">Loading…</p>';
            const pdfBtn = document.getElementById('resultPreviewExportPdf');
            if (pdfBtn) pdfBtn.href = view.dataset.pdf || '#';
            modal.show();
            const r = await fetch(view.dataset.url, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            const d = await r.json();
            if (!d.success) return;
            const exam = d.data.exam;
            const isParent = @json(!empty($isParentView));
            let html = '<div class="result-preview-grid mb-3"><div><strong>Exam:</strong> ' + (exam.title || '') + '</div><div><strong>Subject:</strong> ' + (exam.subject?.name || exam.subject || '—') + '</div><div><strong>Total Marks:</strong> ' + (exam.total_marks || '') + '</div></div>';
            if (isParent) {
                html += '<div class="result-section-chip is-present">Your Child Result</div><div class="table-responsive mb-3"><table class="result-entry-table"><thead><tr><th>Roll</th><th>Name</th><th>Marks</th><th>Remark</th><th>Attempt</th></tr></thead><tbody>';
                const rows = [...(d.data.present || []), ...(d.data.absent || [])];
                if (!rows.length) {
                    html += '<tr><td colspan="5" class="text-muted text-center">No result found for your child.</td></tr>';
                } else {
                    rows.forEach(row => {
                        html += '<tr><td>' + (row.roll_no || '—') + '</td><td>' + row.name + '</td><td>' + (row.marks_obtained ?? '—') + '</td><td>' + (row.remark || '—') + '</td><td>' + (row.is_attempted === false ? 'No' : (row.is_attempted ? 'Yes' : '—')) + '</td></tr>';
                    });
                }
                html += '</tbody></table></div>';
            } else {
            html += '<div class="result-section-chip is-present">Present Students</div><div class="table-responsive mb-3"><table class="result-entry-table"><thead><tr><th>Roll</th><th>Name</th><th>Marks</th><th>Remark</th><th>Attempt</th></tr></thead><tbody>';
            (d.data.present || []).forEach(row => {
                html += '<tr><td>' + (row.roll_no || '—') + '</td><td>' + row.name + '</td><td>' + (row.marks_obtained ?? '—') + '</td><td>' + (row.remark || '—') + '</td><td>' + (row.is_attempted ? 'Yes' : 'No') + '</td></tr>';
            });
            html += '</tbody></table></div><div class="result-section-chip is-absent">Absent Students</div><div class="table-responsive"><table class="result-entry-table"><thead><tr><th>Roll</th><th>Name</th></tr></thead><tbody>';
            (d.data.absent || []).forEach(row => {
                html += '<tr><td>' + (row.roll_no || '—') + '</td><td>' + row.name + '</td></tr>';
            });
            html += '</tbody></table></div>';
            }
            document.getElementById('resultPreviewBody').innerHTML = html;
            return;
        }
        const del = e.target.closest('.btn-result-del');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete result?', text: 'This will remove the result announcement.', confirmText: 'Yes, delete', icon: 'warning' })
                : confirm('Delete?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    const data = await AdminAjax.destroy(del.dataset.url);
                    AdminAjax.removeRow(del.closest('tr'));
                    await AdminAjax.success(data.message || 'Result announcement deleted.');
                }
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Delete failed.');
            }
        }
    });
})();
</script>
@endpush
