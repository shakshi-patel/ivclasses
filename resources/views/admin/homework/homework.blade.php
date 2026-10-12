@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-edit"></i>
                            {{ !empty($isParentView) ? 'Child Homework' : 'Homework' }}
                        </h4>
                        @if (!empty($isParentView))
                            <p class="text-muted small mb-0">Homework assigned to your child&rsquo;s batch.</p>
                        @endif
                    </div>
                    @if ($canAdd)
                        <a href="{{ route('admin.homework.add') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Homework
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ !empty($isParentView) ? 'Child Homework' : 'Homework' }}</li>
                    </ol>
                </nav>

                @if (!empty($isParentView) && ($parentStudents ?? collect())->count() > 1)
                    <form method="get" action="{{ route('admin.homework') }}" class="row g-2 align-items-end mt-3">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label mb-1" for="parentStudentFilter">Child</label>
                            <select name="student_id" id="parentStudentFilter" class="form-select" onchange="this.form.submit()">
                                <option value="">All children</option>
                                @foreach ($parentStudents as $child)
                                    <option value="{{ $child->id }}" @selected(($selectedStudentId ?? null) === $child->id)>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @elseif (empty($isParentView))
                    <x-admin.list-filters
                        :action="route('admin.homework')"
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
                    <div class="admin-list-card-bar"><h5>Homework List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1000px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Title &amp; Batch</th>
                                    <th>Homework Date</th>
                                    <th>Submission Date</th>
                                    @if (empty($isParentView))
                                        <th>Created By</th>
                                    @endif
                                    <th class="text-center" style="width: {{ !empty($isParentView) ? '80px' : '120px' }};">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($homeworks as $homework)
                                    <tr>
                                        <td data-role="sr">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $homework->title }}</div>
                                            <div class="text-muted small">
                                                {{ $homework->batch?->name ?? '—' }}
                                                @if ($homework->subject?->name)
                                                    · {{ $homework->subject->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $homework->homework_date?->format('d M Y') }}</td>
                                        <td>{{ $homework->due_date?->format('d M Y') }}</td>
                                        @if (empty($isParentView))
                                            <td>{{ $homework->creator?->name ?? '—' }}</td>
                                        @endif
                                        <td class="text-center">
                                            @if ($canDownload && $homework->attachment_path)
                                                <a href="{{ route('admin.homework.download', $homework) }}" class="admin-action-icon" title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                            @endif
                                            @if ($canEdit)
                                                <a href="{{ route('admin.homework.edit', $homework) }}" class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-hw-del" title="Delete"
                                                    data-url="{{ route('admin.homework.destroy', $homework) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-hw-view" title="View"
                                                    data-title="{{ $homework->title }}"
                                                    data-batch="{{ $homework->batch?->name }}"
                                                    data-subject="{{ $homework->subject?->name }}"
                                                    data-homework-date="{{ $homework->homework_date?->format('d M Y') }}"
                                                    data-submission-date="{{ $homework->due_date?->format('d M Y') }}"
                                                    data-created-by="{{ $homework->creator?->name }}"
                                                    data-type="{{ $homework->typeLabel() }}"
                                                    data-text="{{ $homework->description }}"
                                                    data-attachment="{{ ($canDownload && $homework->attachment_path) ? route('admin.homework.download', $homework) : '' }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @endif
                                            @if (! ($canDownload && $homework->attachment_path) && ! $canEdit && ! $canDelete && ! $canShow)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ !empty($isParentView) ? 5 : 6 }}" class="text-center text-muted py-4">
                                        {{ !empty($isParentView) ? 'No homework assigned for your child yet.' : 'No homework yet.' }}
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="homeworkViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Homework Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="inq-view-hero">
                        <div class="inq-name" id="hwTitle">—</div>
                        <div class="inq-date" id="hwBatch">—</div>
                    </div>
                    <div class="inq-view-meta">
                        <div class="inq-view-row"><span class="k">Subject:</span><span class="v" id="hwSubject">—</span></div>
                        <div class="inq-view-row"><span class="k">Homework Date:</span><span class="v" id="hwDate">—</span></div>
                        <div class="inq-view-row"><span class="k">Submission Date:</span><span class="v" id="hwSubmission">—</span></div>
                        <div class="inq-view-row"><span class="k">Type:</span><span class="v" id="hwType">—</span></div>
                        <div class="inq-view-row"><span class="k">Created By:</span><span class="v" id="hwCreated">—</span></div>
                    </div>
                    <div class="inq-view-remarks mb-2">
                        <strong>Homework Text:</strong>
                        <span id="hwText">—</span>
                    </div>
                    <div id="hwAttachmentWrap" hidden>
                        <a href="#" id="hwAttachment" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                            <i class="bx bx-download me-1"></i> Download Attachment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const viewModal = new bootstrap.Modal(document.getElementById('homeworkViewModal'));

    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-hw-view');
        if (v) {
            document.getElementById('hwTitle').textContent = v.dataset.title || '—';
            document.getElementById('hwBatch').textContent = v.dataset.batch || '—';
            document.getElementById('hwSubject').textContent = v.dataset.subject || '—';
            document.getElementById('hwDate').textContent = v.dataset.homeworkDate || '—';
            document.getElementById('hwSubmission').textContent = v.dataset.submissionDate || '—';
            document.getElementById('hwType').textContent = v.dataset.type || '—';
            document.getElementById('hwCreated').textContent = v.dataset.createdBy || '—';
            document.getElementById('hwText').textContent = v.dataset.text || '—';
            const wrap = document.getElementById('hwAttachmentWrap');
            const link = document.getElementById('hwAttachment');
            if (v.dataset.attachment) {
                link.href = v.dataset.attachment;
                wrap.hidden = false;
            } else {
                wrap.hidden = true;
            }
            viewModal.show();
            return;
        }
        const del = e.target.closest('.btn-hw-del');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete homework?', text: 'This cannot be undone.', icon: 'warning' })
                : confirm('Delete this homework?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    await AdminAjax.destroy(del.dataset.url);
                    AdminAjax.removeRow(del.closest('tr'));
                    await AdminAjax.success('Homework deleted.');
                } else {
                    const r = await fetch(del.dataset.url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } });
                    const d = await r.json();
                    if (!d.success) throw new Error(d.message);
                    del.closest('tr')?.remove();
                }
            } catch (err) {
                if (window.AdminAjax) AdminAjax.error(err.message);
                else alert(err.message);
            }
        }
    });
})();
</script>
@endpush
