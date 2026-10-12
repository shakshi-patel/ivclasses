@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title"><i class="bx bx-bell"></i> {{ !empty($isParentView) ? 'Notices' : 'Notice' }}</h4>
                    </div>
                    @if ($canAdd)
                        <a href="{{ route('admin.notice.add') }}" class="btn admin-btn-primary"><i class="bx bx-plus me-1"></i> Add Notice</a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Notice</li>
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
                        :action="route('admin.notice')"
                        :show-institute="$isPlatform"
                        :show-branch="true"
                        :show-status="true"
                        :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                        :institutes="$institutes ?? collect()"
                        :branches="$branches ?? collect()"
                    />
                @endif

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Notice List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    @if (empty($isParentView))<th>Status</th>@endif
                                    <th class="text-center" style="width: 160px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notices as $notice)
                                    <tr>
                                        <td data-role="sr">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $notice->title }}</div>
                                            <div class="text-muted small">{{ $notice->scopeLabel() }}</div>
                                        </td>
                                        <td>{{ $notice->notice_date?->format('d M Y') }}</td>
                                        @if (empty($isParentView))
                                            <td>
                                                <span class="admin-status-pill {{ $notice->isActive() ? 'is-active' : 'is-muted' }}" data-role="status-pill">
                                                    {{ $notice->isActive() ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            @if ($canStatus && empty($isParentView))
                                                <button type="button" class="admin-action-icon btn-notice-toggle"
                                                    title="{{ $notice->isActive() ? 'Deactivate' : 'Activate' }}"
                                                    data-url="{{ route('admin.notice.toggle_status', $notice) }}"
                                                    data-active="{{ $notice->isActive() ? '1' : '0' }}">
                                                    <i class="bx {{ $notice->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if ($canEdit)
                                                <a href="{{ route('admin.notice.edit', $notice) }}" class="admin-action-icon" title="Edit"><i class="bx bx-pencil"></i></a>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="admin-action-icon btn-notice-del" title="Delete"
                                                    data-url="{{ route('admin.notice.destroy', $notice) }}"><i class="bx bx-trash"></i></button>
                                            @endif
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-notice-view" title="View"
                                                    data-title="{{ $notice->title }}"
                                                    data-date="{{ $notice->notice_date?->format('d M Y') }}"
                                                    data-scope="{{ $notice->scopeLabel() }}"
                                                    data-created-by="{{ $notice->creator?->name }}"
                                                    data-message="{{ $notice->body }}"
                                                    data-image="{{ $notice->imageUrl() }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @endif
                                            @if (! ($canStatus && empty($isParentView)) && ! $canEdit && ! $canDelete && ! $canShow)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ empty($isParentView) ? 5 : 4 }}" class="text-center text-muted py-4">No notices yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="noticeViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Notice Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="notice-view-top">
                        <div class="notice-view-thumb" id="noticeThumbWrap">
                            <div class="notice-view-thumb-placeholder" id="noticeImagePlaceholder"><i class="bx bx-image"></i></div>
                            <img src="" alt="Notice Photo" class="d-none" id="noticeImage">
                        </div>
                        <div class="notice-view-head">
                            <div class="notice-view-title" id="noticeTitle">—</div>
                            <div class="notice-view-date" id="noticeDate">—</div>
                            <div class="notice-view-sub" id="noticeBranchBatch">—</div>
                        </div>
                    </div>
                    <div class="inq-view-remarks mt-3"><strong>Message:</strong> <span id="noticeMessage">—</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const modal = new bootstrap.Modal(document.getElementById('noticeViewModal'));
    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-notice-view');
        if (v) {
            document.getElementById('noticeTitle').textContent = v.dataset.title || '—';
            document.getElementById('noticeDate').textContent = v.dataset.date || '—';
            document.getElementById('noticeBranchBatch').textContent = v.dataset.scope || '—';
            document.getElementById('noticeMessage').textContent = v.dataset.message || '—';
            const img = document.getElementById('noticeImage');
            const ph = document.getElementById('noticeImagePlaceholder');
            if (v.dataset.image) { img.src = v.dataset.image; img.classList.remove('d-none'); ph.classList.add('d-none'); }
            else { img.classList.add('d-none'); ph.classList.remove('d-none'); }
            modal.show();
            return;
        }
        const toggle = e.target.closest('.btn-notice-toggle');
        if (toggle) {
            try {
                const data = await AdminAjax.post(toggle.dataset.url);
                AdminAjax.applyStatusToggle(toggle, AdminAjax.statusFrom(data) === 'active');
                await AdminAjax.success(data.message || 'Status updated.');
            } catch (err) {
                await AdminAjax.error(err.message || 'Failed to update status');
            }
            return;
        }
        const del = e.target.closest('.btn-notice-del');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete notice?', text: 'This notice will be removed from the list.', confirmText: 'Yes, delete', icon: 'warning' })
                : confirm('Delete?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    const data = await AdminAjax.destroy(del.dataset.url);
                    AdminAjax.removeRow(del.closest('tr'));
                    await AdminAjax.success(data.message || 'Notice deleted.');
                }
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Delete failed.');
            }
        }
    });
})();
</script>
@endpush
