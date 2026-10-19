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
                    <h4 class="admin-page-title"><i class="bx bx-rupee"></i> Income &amp; Expense Category</h4>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('income_expense_category.add'))
                        <button type="button" class="btn admin-btn-primary" id="btnAddIeCategory">
                            <i class="bx bx-plus me-1"></i> Create
                        </button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active">Income &amp; Expense Category</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.income_expense')"
                    :show-institute="$isPlatform"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                    :institutes="$institutes ?? collect()"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Type</label>
                        <select class="form-select form-select-sm" name="type">
                            <option value="">All Types</option>
                            <option value="income" @selected(request('type') === 'income')>Income</option>
                            <option value="expense" @selected(request('type') === 'expense')>Expense</option>
                        </select>
                    </div>
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Category List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" id="ieCategoryTable">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr. No.</th>
                                    @if ($isPlatform)<th>Institute</th>@endif
                                    <th style="width: 90px;">Seq.</th>
                                    <th>Title</th>
                                    <th style="width: 110px;">Type</th>
                                    <th style="width: 110px;">Status</th>
                                    <th class="text-center" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        @if ($isPlatform)<td>{{ $row->institute?->name ?? '—' }}</td>@endif
                                        <td>{{ $row->sort_order }}</td>
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td>
                                            @if ($row->type === 'income')
                                                <span class="admin-status-pill is-active">Income</span>
                                            @else
                                                <span class="admin-status-pill is-navy">Expense</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->status === 'active' ? 'is-active' : 'is-muted' }}" data-role="status-pill">
                                                {{ $row->status === 'active' ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('income_expense_category.edit'))
                                                <button type="button" class="admin-action-icon btn-edit-ie-cat" title="Edit"
                                                    data-id="{{ $row->id }}"
                                                    data-institute-id="{{ $row->institute_id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-type="{{ $row->type }}"
                                                    data-sort="{{ $row->sort_order }}"
                                                    data-status="{{ $row->status }}"><i class="bx bx-pencil"></i></button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('income_expense_category.status'))
                                                <button type="button" class="admin-action-icon btn-toggle-ie-cat"
                                                    title="{{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                                    data-id="{{ $row->id }}"
                                                    data-active="{{ $row->status === 'active' ? '1' : '0' }}">
                                                    <i class="bx {{ $row->status === 'active' ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('income_expense_category.delete'))
                                                <button type="button" class="admin-action-icon btn-delete-ie-cat" title="Delete"
                                                    data-id="{{ $row->id }}"><i class="bx bx-trash"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $isPlatform ? 7 : 6 }}" class="text-center text-muted py-4">No categories yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="ieCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ieCategoryModalTitle">Create Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ieCategoryId">
                    @if ($isPlatform)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Institute <span class="text-danger">*</span></label>
                            <select class="form-select" id="ieCategoryInstitute" required>
                                <option value="">Select institute</option>
                                @foreach ($institutes as $inst)
                                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field-error="institute_id"></div>
                        </div>
                    @else
                        <input type="hidden" id="ieCategoryInstitute" value="{{ $defaultInstituteId }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ieCategoryName" required>
                        <div class="invalid-feedback" data-field-error="name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sequence No <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ieCategorySort" min="0" value="0" required>
                        <div class="invalid-feedback" data-field-error="sort_order"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="ieCategoryType" required data-no-search>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                        <div class="invalid-feedback" data-field-error="type"></div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="ieCategoryStatus" data-no-search>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field-error="status"></div>
                    </div>
                    <div class="text-danger small mt-2" id="ieCategoryError" data-form-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="ieCategorySaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const storeUrl = @json(route('admin.master.income_expense.store'));
    const baseUrl = @json(route('admin.master.income_expense'));
    const isPlatform = @json($isPlatform);
    const defaultInstituteId = @json((string) $defaultInstituteId);
    const modalEl = document.getElementById('ieCategoryModal');
    const modal = new bootstrap.Modal(modalEl);

    function instituteId() {
        return document.getElementById('ieCategoryInstitute')?.value || defaultInstituteId;
    }

    const fieldMap = {
        institute_id: '#ieCategoryInstitute',
        name: '#ieCategoryName',
        sort_order: '#ieCategorySort',
        type: '#ieCategoryType',
        status: '#ieCategoryStatus'
    };

    function resetForm() {
        document.getElementById('ieCategoryId').value = '';
        document.getElementById('ieCategoryName').value = '';
        document.getElementById('ieCategorySort').value = '0';
        document.getElementById('ieCategoryType').value = 'income';
        document.getElementById('ieCategoryStatus').value = 'active';
        if (isPlatform) document.getElementById('ieCategoryInstitute').value = '';
        document.getElementById('ieCategoryModalTitle').textContent = 'Create Category';
        AdminAjax.clearFieldErrors(modalEl);
    }

    function openAddModal() {
        resetForm();
        modal.show();
    }

    document.getElementById('btnAddIeCategory')?.addEventListener('click', openAddModal);


    document.getElementById('ieCategoryTable')?.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.btn-edit-ie-cat');
        if (editBtn) {
            document.getElementById('ieCategoryId').value = editBtn.dataset.id;
            if (isPlatform) document.getElementById('ieCategoryInstitute').value = editBtn.dataset.instituteId;
            document.getElementById('ieCategoryName').value = editBtn.dataset.name;
            document.getElementById('ieCategorySort').value = editBtn.dataset.sort;
            document.getElementById('ieCategoryType').value = editBtn.dataset.type;
            document.getElementById('ieCategoryStatus').value = editBtn.dataset.status;
            document.getElementById('ieCategoryModalTitle').textContent = 'Edit Category';
            modal.show();
            return;
        }

        const toggleBtn = e.target.closest('.btn-toggle-ie-cat');
        if (toggleBtn) {
            AdminAjax.post(baseUrl + '/' + toggleBtn.dataset.id + '/toggle-status').then(function(data) {
                AdminAjax.applyStatusToggle(toggleBtn, AdminAjax.statusFrom(data) === 'active');
                const editBtn = toggleBtn.closest('tr')?.querySelector('.btn-edit-ie-cat');
                if (editBtn) editBtn.dataset.status = AdminAjax.statusFrom(data);
                return AdminAjax.success(data.message || 'Status updated.');
            }).catch(function(err) {
                AdminAjax.error(err.message);
            });
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-ie-cat');
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

    document.getElementById('ieCategorySaveBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('ieCategoryId').value;
        const payload = {
            institute_id: parseInt(instituteId(), 10),
            name: document.getElementById('ieCategoryName').value.trim(),
            sort_order: parseInt(document.getElementById('ieCategorySort').value, 10) || 0,
            type: document.getElementById('ieCategoryType').value,
            status: document.getElementById('ieCategoryStatus').value
        };
        AdminAjax.clearFieldErrors(modalEl);
        try {
            const data = id
                ? await AdminAjax.put(baseUrl + '/' + id, payload)
                : await AdminAjax.post(storeUrl, payload);
            modal.hide();
            await AdminAjax.success(data.message || (id ? 'Category updated.' : 'Category saved.'));
            window.location.reload();
        } catch (e) {
            if (e.errors) {
                AdminAjax.showFieldErrors(e.errors, fieldMap, {
                    root: modalEl,
                    fallback: '#ieCategoryError'
                });
            } else {
                const errEl = document.getElementById('ieCategoryError');
                errEl.textContent = e.message;
                errEl.hidden = false;
            }
        }
    });

})();
</script>
@endpush
