@extends('admin.layouts.layout')

@section('content')
    @php
        $isPlatform = $isPlatform ?? (auth()->user()?->isPlatformUser() ?? false);
        $defaultInstituteId = $isPlatform ? '' : (auth()->user()?->institute_id ?? '');
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-book"></i>
                            Subject
                        </h4>
                    </div>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.add'))
                        <button type="button" class="btn admin-btn-primary" id="btnAddSubject">
                            <i class="bx bx-plus me-1"></i> Add Subject
                        </button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active" aria-current="page">Subject</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.subject')"
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
                    <div class="admin-list-card-bar">
                        <h5>Subject List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Sr. No.</th>
                                    @if ($isPlatform)
                                        <th>Classes / Institute</th>
                                    @endif
                                    <th>Subject Name</th>
                                    <th style="width: 120px;">Code</th>
                                    <th style="width: 120px;">Status</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $i => $row)
                                    <tr data-id="{{ $row->id }}">
                                        <td data-role="sr">{{ $i + 1 }}</td>
                                        @if ($isPlatform)
                                            <td>{{ $row->institute?->name ?? '—' }}</td>
                                        @endif
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td>{{ $row->code ?: '—' }}</td>
                                        <td>
                                            @if ($row->status === 'active')
                                                <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.edit'))
                                                <button type="button" class="admin-action-icon btn-edit-subject"
                                                    title="Edit"
                                                    data-id="{{ $row->id }}"
                                                    data-institute-id="{{ $row->institute_id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-code="{{ $row->code }}"
                                                    data-status="{{ $row->status }}">
                                                    <i class="bx bx-pencil"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.status'))
                                                <button type="button" class="admin-action-icon btn-toggle-subject"
                                                    title="{{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                                    data-id="{{ $row->id }}"
                                                    data-active="{{ $row->status === 'active' ? '1' : '0' }}">
                                                    <i class="bx {{ $row->status === 'active' ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.delete'))
                                                <button type="button" class="admin-action-icon btn-delete-subject"
                                                    title="Delete" data-id="{{ $row->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isPlatform ? 6 : 5 }}" class="text-center text-muted py-4">
                                            No subjects yet. Add Maths, Science, English…
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="subjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalTitle">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="subjectId" value="">
                    @if ($isPlatform)
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="subjectInstitute">
                                Classes / Institute <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="subjectInstitute" required>
                                <option value="">Select institute</option>
                                @foreach ($institutes as $inst)
                                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="subjectInstitute" value="{{ $defaultInstituteId }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="subjectName">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subjectName" placeholder="e.g. Maths" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="subjectCode">Code</label>
                        <input type="text" class="form-control" id="subjectCode" placeholder="e.g. MTH">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="subjectStatus">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="subjectStatus" required data-no-search>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="text-danger small mt-2" id="subjectFormError" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="subjectSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const storeUrl = @json(route('admin.master.subject.store'));
            const baseUrl = @json(route('admin.master.subject'));
            const isPlatform = @json($isPlatform);
            const defaultInstituteId = @json((string) $defaultInstituteId);
            const canEdit = @json(auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.edit'));
            const canStatus = @json(auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.status'));
            const canDelete = @json(auth()->user()?->isPlatformUser() || auth()->user()?->can('subject.delete'));
            const modalEl = document.getElementById('subjectModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const tbody = document.querySelector('.admin-list-table tbody');

            function setInstituteValue(value) {
                const el = document.getElementById('subjectInstitute');
                if (!el) return;
                el.value = value || '';
                if (window.jQuery && jQuery(el).hasClass('select2-hidden-accessible')) {
                    jQuery(el).val(value || '').trigger('change');
                }
            }

            function resetForm() {
                document.getElementById('subjectId').value = '';
                setInstituteValue(isPlatform ? '' : defaultInstituteId);
                document.getElementById('subjectName').value = '';
                document.getElementById('subjectCode').value = '';
                document.getElementById('subjectStatus').value = 'active';
                document.getElementById('subjectModalTitle').textContent = 'Add Subject';
                const err = document.getElementById('subjectFormError');
                err.hidden = true;
                err.textContent = '';
            }

            function bindRowActions(tr) {
                tr.querySelector('.btn-edit-subject')?.addEventListener('click', onEdit);
                tr.querySelector('.btn-toggle-subject')?.addEventListener('click', onToggle);
                tr.querySelector('.btn-delete-subject')?.addEventListener('click', onDelete);
            }

            function buildRow(row, sr) {
                const active = row.status === 'active';
                const tr = document.createElement('tr');
                tr.dataset.id = row.id;
                let html = '<td data-role="sr">' + sr + '</td>';
                if (isPlatform) {
                    html += '<td>' + (row.institute?.name || '—') + '</td>';
                }
                html += '<td class="fw-semibold">' + (row.name || '') + '</td>';
                html += '<td>' + (row.code || '—') + '</td>';
                html += '<td><span class="admin-status-pill ' + (active ? 'is-active' : 'is-muted') + '" data-role="status-pill">' +
                    (active ? 'Active' : 'Inactive') + '</span></td>';
                html += '<td class="text-center">';
                if (canEdit) {
                    html += '<button type="button" class="admin-action-icon btn-edit-subject" title="Edit"' +
                        ' data-id="' + row.id + '" data-institute-id="' + (row.institute_id || '') + '"' +
                        ' data-name="' + (row.name || '').replace(/"/g, '&quot;') + '"' +
                        ' data-code="' + (row.code || '').replace(/"/g, '&quot;') + '"' +
                        ' data-status="' + row.status + '"><i class="bx bx-pencil"></i></button>';
                }
                if (canStatus) {
                    html += '<button type="button" class="admin-action-icon btn-toggle-subject" title="' + (active ? 'Deactivate' : 'Activate') + '"' +
                        ' data-id="' + row.id + '" data-active="' + (active ? '1' : '0') + '">' +
                        '<i class="bx ' + (active ? 'bx-user-x' : 'bx-user-check') + '"></i></button>';
                }
                if (canDelete) {
                    html += '<button type="button" class="admin-action-icon btn-delete-subject" title="Delete" data-id="' + row.id + '">' +
                        '<i class="bx bx-trash"></i></button>';
                }
                html += '</td>';
                tr.innerHTML = html;
                bindRowActions(tr);
                return tr;
            }

            function dt() {
                if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(jQuery('.admin-list-table'))) {
                    return jQuery('.admin-list-table').DataTable();
                }
                return null;
            }

            function onEdit(e) {
                const btn = e.currentTarget;
                resetForm();
                document.getElementById('subjectModalTitle').textContent = 'Edit Subject';
                document.getElementById('subjectId').value = btn.dataset.id;
                setInstituteValue(btn.dataset.instituteId || defaultInstituteId);
                document.getElementById('subjectName').value = btn.dataset.name || '';
                document.getElementById('subjectCode').value = btn.dataset.code || '';
                document.getElementById('subjectStatus').value = btn.dataset.status || 'active';
                modal?.show();
            }

            async function onToggle(e) {
                const btn = e.currentTarget;
                const tr = btn.closest('tr');
                try {
                    const data = await AdminAjax.post(baseUrl + '/' + btn.dataset.id + '/toggle-status');
                    const status = AdminAjax.statusFrom(data);
                    AdminAjax.applyStatusToggle(btn, status === 'active');
                    const editBtn = tr.querySelector('.btn-edit-subject');
                    if (editBtn) editBtn.dataset.status = status;
                    await AdminAjax.success(data.message || 'Status updated.');
                } catch (err) {
                    await AdminAjax.error(err.message);
                }
            }

            async function onDelete(e) {
                const btn = e.currentTarget;
                const ok = await AdminAjax.confirm({
                    title: 'Delete subject?',
                    text: 'This will soft-delete the subject.',
                    confirmText: 'Yes, delete'
                });
                if (!ok) return;
                try {
                    const data = await AdminAjax.destroy(baseUrl + '/' + btn.dataset.id);
                    AdminAjax.removeRow(btn.closest('tr'));
                    await AdminAjax.success(data.message || 'Deleted.');
                } catch (err) {
                    await AdminAjax.error(err.message);
                }
            }

            function openAddModal() {
                resetForm();
                modal?.show();
            }

            document.getElementById('btnAddSubject')?.addEventListener('click', openAddModal);
            document.querySelectorAll('.btn-edit-subject').forEach(function(btn) {
                btn.addEventListener('click', onEdit);
            });
            document.querySelectorAll('.btn-toggle-subject').forEach(function(btn) {
                btn.addEventListener('click', onToggle);
            });
            document.querySelectorAll('.btn-delete-subject').forEach(function(btn) {
                btn.addEventListener('click', onDelete);
            });


            document.getElementById('subjectSaveBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('subjectId').value;
                const instituteId = document.getElementById('subjectInstitute')?.value || '';
                const err = document.getElementById('subjectFormError');
                err.hidden = true;
                if (!instituteId) {
                    err.hidden = false;
                    err.textContent = 'Please select an institute.';
                    return;
                }
                const payload = {
                    institute_id: parseInt(instituteId, 10),
                    name: document.getElementById('subjectName').value.trim(),
                    code: document.getElementById('subjectCode').value.trim() || null,
                    status: document.getElementById('subjectStatus').value
                };
                try {
                    let data;
                    const table = dt();
                    if (id) {
                        data = await AdminAjax.put(baseUrl + '/' + id, payload);
                        const tr = tbody?.querySelector('tr[data-id="' + id + '"]') || document.querySelector('tr[data-id="' + id + '"]');
                        if (tr && data.data) {
                            const sr = tr.children[0]?.textContent || '1';
                            const newTr = buildRow(data.data, sr);
                            if (table) {
                                table.row(tr).remove();
                                table.row.add(newTr).draw(false);
                            } else {
                                tr.replaceWith(newTr);
                            }
                        }
                    } else {
                        data = await AdminAjax.post(storeUrl, payload);
                        if (data.data) {
                            const empty = tbody?.querySelector('td[colspan]');
                            if (empty && table) table.clear().draw(false);
                            else if (empty) tbody.innerHTML = '';
                            const sr = ((table ? table.rows().count() : tbody?.querySelectorAll('tr').length) || 0) + 1;
                            const newTr = buildRow(data.data, sr);
                            if (table) table.row.add(newTr).draw(false);
                            else if (tbody) tbody.appendChild(newTr);
                        }
                    }
                    modal?.hide();
                    await AdminAjax.success(data.message || 'Saved.');
                    if (!id && typeof AdminAjax.leaveForSetup === 'function' && AdminAjax.leaveForSetup()) {
                        return;
                    }
                } catch (e) {
                    err.hidden = false;
                    err.textContent = e.message;
                }
            });
        })();
    </script>
@endpush
