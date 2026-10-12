@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-book-content"></i>
                            {{ !empty($isParentView) ? 'Child Study Material' : 'Study Material' }}
                        </h4>
                        @if (!empty($isParentView))
                            <p class="text-muted small mb-0">Study material shared for your child&rsquo;s batch.</p>
                        @endif
                    </div>
                    @if ($canAdd)
                        <a href="{{ route('admin.study_material.add') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Study Material
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ !empty($isParentView) ? 'Child Study Material' : 'Study Material' }}</li>
                    </ol>
                </nav>

                @if (!empty($isParentView) && ($parentStudents ?? collect())->count() > 1)
                    <form method="get" action="{{ route('admin.study_material') }}" class="row g-2 align-items-end mt-3">
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
                @endif

                @if (empty($isParentView))
                    <x-admin.list-filters
                        :action="route('admin.study_material')"
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
                    <div class="admin-list-card-bar"><h5>Study Material List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1050px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Study Material Title</th>
                                    <th>Branch Name</th>
                                    <th>Batch Name</th>
                                    @if (empty($isParentView))
                                        <th>Uploaded By</th>
                                    @endif
                                    <th>Date &amp; Time</th>
                                    <th class="text-center" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materials as $material)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $material->title }}</div>
                                            @if ($material->subject?->name)
                                                <div class="text-muted small">{{ $material->subject->name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $material->branch?->name ?? '—' }}</td>
                                        <td>{{ $material->batch?->name ?? '—' }}</td>
                                        @if (empty($isParentView))
                                            <td>{{ $material->creator?->name ?? '—' }}</td>
                                        @endif
                                        <td>
                                            <div>{{ $material->created_at?->format('d M Y') }}</div>
                                            <div class="text-muted small">{{ $material->created_at?->format('h:i A') }}</div>
                                        </td>
                                        <td class="text-center">
                                            @if ($canDownload && $material->file_path)
                                                <a href="{{ route('admin.study_material.download', $material) }}" class="admin-action-icon" title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                            @endif
                                            @if ($canEdit)
                                                <a href="{{ route('admin.study_material.edit', $material) }}" class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-sm-del" title="Delete"
                                                    data-url="{{ route('admin.study_material.destroy', $material) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-sm-view" title="View"
                                                    data-title="{{ $material->title }}"
                                                    data-date="{{ $material->created_at?->format('d M Y h:i A') }}"
                                                    data-branch="{{ $material->branch?->name }}"
                                                    data-batch="{{ $material->batch?->name }}"
                                                    data-subject="{{ $material->subject?->name }}"
                                                    data-uploaded="{{ $material->creator?->name }}"
                                                    data-description="{{ $material->description }}"
                                                    data-file="{{ ($canDownload && $material->file_path) ? route('admin.study_material.download', $material) : '' }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @endif
                                            @if (! ($canDownload && $material->file_path) && ! $canEdit && ! $canDelete && ! $canShow)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ !empty($isParentView) ? 6 : 7 }}" class="text-center text-muted py-4">
                                            {{ !empty($isParentView) ? 'No study material shared for your child yet.' : 'No study material yet.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="studyMaterialViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Study Material Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="inq-view-hero">
                        <div class="inq-name" id="smTitle">—</div>
                        <div class="inq-date" id="smDateBranch">—</div>
                    </div>
                    <div class="inq-view-meta">
                        <div class="inq-view-row"><span class="k">Batch:</span><span class="v" id="smBatch">—</span></div>
                        <div class="inq-view-row"><span class="k">Subject:</span><span class="v" id="smSubject">—</span></div>
                        <div class="inq-view-row"><span class="k">Uploaded By:</span><span class="v" id="smUploaded">—</span></div>
                    </div>
                    <div class="inq-view-remarks mb-2">
                        <strong>Material Description:</strong>
                        <span id="smDescription">—</span>
                    </div>
                    <div id="smFileWrap" hidden>
                        <a href="#" id="smFileLink" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                            <i class="bx bx-download me-1"></i> Download File
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
    const viewModal = new bootstrap.Modal(document.getElementById('studyMaterialViewModal'));

    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-sm-view');
        if (v) {
            document.getElementById('smTitle').textContent = v.dataset.title || '—';
            document.getElementById('smDateBranch').textContent =
                (v.dataset.date || '—') + (v.dataset.branch ? ' | ' + v.dataset.branch : '');
            document.getElementById('smBatch').textContent = v.dataset.batch || '—';
            document.getElementById('smSubject').textContent = v.dataset.subject || '—';
            document.getElementById('smUploaded').textContent = v.dataset.uploaded || '—';
            document.getElementById('smDescription').textContent = v.dataset.description || '—';
            const wrap = document.getElementById('smFileWrap');
            const link = document.getElementById('smFileLink');
            if (v.dataset.file) {
                link.href = v.dataset.file;
                wrap.hidden = false;
            } else {
                wrap.hidden = true;
            }
            viewModal.show();
            return;
        }
        const del = e.target.closest('.btn-sm-del');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete study material?', text: 'This cannot be undone.', icon: 'warning' })
                : confirm('Delete this study material?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    await AdminAjax.destroy(del.dataset.url);
                    await AdminAjax.success('Study material deleted.');
                } else {
                    const r = await fetch(del.dataset.url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } });
                    const d = await r.json();
                    if (!d.success) throw new Error(d.message);
                }
                del.closest('tr')?.remove();
            } catch (err) {
                if (window.AdminAjax) AdminAjax.error(err.message);
                else alert(err.message);
            }
        }
    });
})();
</script>
@endpush
