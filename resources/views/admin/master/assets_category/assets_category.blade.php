@extends('admin.layouts.layout')

@section('content')
    @php
        $isPlatform = $isPlatform ?? (auth()->user()?->isPlatformUser() ?? false);
        $defaultInstituteId = $isPlatform ? '' : (auth()->user()?->institute_id ?? '');
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <h4 class="admin-page-title"><i class="bx bx-category"></i> Assets Category</h4>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('assets_category.add'))
                        <button type="button" class="btn admin-btn-primary" id="btnAddAssetCat">
                            <i class="bx bx-plus me-1"></i> Create
                        </button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active">Assets Category</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.assets_category')"
                    :show-institute="$isPlatform"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                    :institutes="$institutes ?? collect()"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Assets Category List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" id="assetCategoryTable">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr. No.</th>
                                    @if ($isPlatform)<th>Institute</th>@endif
                                    <th>Category Title</th>
                                    <th style="width: 110px;">Status</th>
                                    <th class="text-center" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        @if ($isPlatform)<td>{{ $row->institute?->name ?? '—' }}</td>@endif
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->status === 'active' ? 'is-active' : 'is-muted' }}" data-role="status-pill">
                                                {{ $row->status === 'active' ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('assets_category.edit'))
                                                <button type="button" class="admin-action-icon btn-edit-asset-cat" title="Edit"
                                                    data-id="{{ $row->id }}"
                                                    data-institute-id="{{ $row->institute_id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-status="{{ $row->status }}"><i class="bx bx-pencil"></i></button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('assets_category.status'))
                                                <button type="button" class="admin-action-icon btn-toggle-asset-cat"
                                                    title="{{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                                    data-id="{{ $row->id }}"
                                                    data-active="{{ $row->status === 'active' ? '1' : '0' }}">
                                                    <i class="bx {{ $row->status === 'active' ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('assets_category.delete'))
                                                <button type="button" class="admin-action-icon btn-delete-asset-cat" title="Delete"
                                                    data-id="{{ $row->id }}"><i class="bx bx-trash"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $isPlatform ? 5 : 4 }}" class="text-center text-muted py-4">No categories yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="assetCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assetCategoryModalTitle">Create Assets Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assetCategoryId">
                    @if ($isPlatform)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Institute <span class="text-danger">*</span></label>
                            <select class="form-select" id="assetCategoryInstitute" required>
                                <option value="">Select institute</option>
                                @foreach ($institutes as $inst)
                                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="assetCategoryInstitute" value="{{ $defaultInstituteId }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="assetCategoryName" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="assetCategoryStatus" data-no-search>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="text-danger small mt-2" id="assetCategoryError" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="assetCategorySaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const storeUrl = @json(route('admin.master.assets_category.store'));
    const baseUrl = @json(route('admin.master.assets_category'));
    const isPlatform = @json($isPlatform);
    const defaultInstituteId = @json((string) $defaultInstituteId);
    const modalEl = document.getElementById('assetCategoryModal');
    const modal = new bootstrap.Modal(modalEl);

    function instituteId() {
        return document.getElementById('assetCategoryInstitute')?.value || defaultInstituteId;
    }

    function resetForm() {
        document.getElementById('assetCategoryId').value = '';
        document.getElementById('assetCategoryName').value = '';
        document.getElementById('assetCategoryStatus').value = 'active';
        if (isPlatform) document.getElementById('assetCategoryInstitute').value = '';
        document.getElementById('assetCategoryModalTitle').textContent = 'Create Assets Category';
        document.getElementById('assetCategoryError').hidden = true;
    }

    function openAddModal() {
        resetForm();
        modal.show();
    }

    document.getElementById('btnAddAssetCat')?.addEventListener('click', openAddModal);


    document.getElementById('assetCategoryTable')?.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.btn-edit-asset-cat');
        if (editBtn) {
            document.getElementById('assetCategoryId').value = editBtn.dataset.id;
            if (isPlatform) document.getElementById('assetCategoryInstitute').value = editBtn.dataset.instituteId;
            document.getElementById('assetCategoryName').value = editBtn.dataset.name;
            document.getElementById('assetCategoryStatus').value = editBtn.dataset.status;
            document.getElementById('assetCategoryModalTitle').textContent = 'Edit Assets Category';
            modal.show();
            return;
        }

        const toggleBtn = e.target.closest('.btn-toggle-asset-cat');
        if (toggleBtn) {
            AdminAjax.post(baseUrl + '/' + toggleBtn.dataset.id + '/toggle-status').then(function(data) {
                AdminAjax.applyStatusToggle(toggleBtn, AdminAjax.statusFrom(data) === 'active');
                const editBtn = toggleBtn.closest('tr')?.querySelector('.btn-edit-asset-cat');
                if (editBtn) editBtn.dataset.status = AdminAjax.statusFrom(data);
                return AdminAjax.success(data.message || 'Status updated.');
            }).catch(function(err) {
                AdminAjax.error(err.message);
            });
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-asset-cat');
        if (deleteBtn) {
            AdminAjax.confirm({
                title: 'Delete category?',
                text: 'This category will be removed.',
                confirmText: 'Yes, delete',
                icon: 'warning'
            }).then(function(ok) {
                if (!ok) return;
                return AdminAjax.destroy(baseUrl + '/' + deleteBtn.dataset.id).then(function(data) {
                    AdminAjax.removeRow(deleteBtn.closest('tr'));
                    return AdminAjax.success(data.message || 'Category deleted.');
                });
            }).catch(function(err) {
                AdminAjax.error(err.message);
            });
        }
    });

    document.getElementById('assetCategorySaveBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('assetCategoryId').value;
        const payload = {
            institute_id: parseInt(instituteId(), 10),
            name: document.getElementById('assetCategoryName').value.trim(),
            status: document.getElementById('assetCategoryStatus').value
        };
        const errEl = document.getElementById('assetCategoryError');
        try {
            const data = id
                ? await AdminAjax.put(baseUrl + '/' + id, payload)
                : await AdminAjax.post(storeUrl, payload);
            modal.hide();
            await AdminAjax.success(data.message || (id ? 'Category updated.' : 'Category saved.'));
            window.location.reload();
        } catch (e) {
            errEl.textContent = e.message;
            errEl.hidden = false;
        }
    });

})();
</script>
@endpush
