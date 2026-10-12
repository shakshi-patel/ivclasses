@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-shield-quarter"></i>
                            Roles
                        </h4>
                        @if (auth()->user()?->isPlatformUser())
                            <p class="text-muted small mb-0 mt-1">
                                Platform roles are templates for institutes. Edit permissions here to update institute copies.
                            </p>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('roles.view'))
                            <a href="{{ route('admin.export.roles') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('roles.add'))
                            <button type="button" class="btn admin-btn-primary" id="btnAddRole">
                                <i class="bx bx-plus me-1"></i> Add Role
                            </button>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item">Role &amp; Permissions</li>
                        <li class="breadcrumb-item active" aria-current="page">Roles</li>
                    </ol>
                </nav>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Roles List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Sr No.</th>
                                    <th style="width: 90px;">Image</th>
                                    <th>Role Name</th>
                                    <th>Parent</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 140px;">Created At</th>
                                    <th class="text-center" style="width: 160px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $i => $role)
                                    <tr data-role-id="{{ $role->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <img src="{{ $role->avatarUrl() }}"
                                                alt="{{ $role->name }}" class="role-table-avatar">
                                        </td>
                                        <td class="fw-semibold">
                                            {{ $role->name }}
                                        </td>
                                        <td class="text-muted">
                                            {{ $role->parentRole?->name ?? '—' }}
                                        </td>
                                        <td>
                                            @if ($role->status === 'active')
                                                <span class="admin-status-pill is-active">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($role->created_at)->format('d M Y') }}</td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('roles.edit'))
                                                <button type="button" class="admin-action-icon btn-edit-role"
                                                    title="Edit"
                                                    data-id="{{ $role->id }}"
                                                    data-name="{{ $role->name }}"
                                                    data-status="{{ $role->status }}"
                                                    data-parent="{{ $role->parent_role_id }}"
                                                    data-assignable="{{ $role->assignableRoles->pluck('id')->implode(',') }}"
                                                    data-system="{{ $role->is_system ? 1 : 0 }}">
                                                    <i class="bx bx-pencil"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('roles.assign_permissions'))
                                                <button type="button" class="admin-action-icon btn-role-perms"
                                                    title="Permissions"
                                                    data-id="{{ $role->id }}"
                                                    data-role-name="{{ $role->name }}">
                                                    <i class="bx bx-key"></i>
                                                </button>
                                            @endif
                                            @if (
                                                ! $role->is_system
                                                && (auth()->user()?->isPlatformUser() || auth()->user()?->can('roles.delete'))
                                            )
                                                <button type="button" class="admin-action-icon btn-delete-role"
                                                    title="Delete" data-id="{{ $role->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No roles yet. Add one to get started.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit Role --}}
    <div class="modal fade admin-modal" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roleForm" enctype="multipart/form-data">
                        <input type="hidden" id="roleId" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="roleName">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="roleName" name="name"
                                    placeholder="e.g. Teacher" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="roleStatus">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="roleStatus" name="status" required data-no-search>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="roleParent">Parent Role</label>
                                <select class="form-select" id="roleParent" name="parent_role_id">
                                    <option value="">{{ auth()->user()?->isPlatformUser() ? 'Top (no parent)' : 'Select parent role' }}</option>
                                    @foreach ($parentOptions as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                                @if (auth()->user()?->isPlatformUser())
                                    <div class="form-text">
                                        Platform templates: <strong>developer</strong> is top parent. Institute roles inherit permissions from these templates.
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="roleImage">Role Image</label>
                                <input type="file" class="form-control" id="roleImage" name="image" accept="image/*">
                                <div class="form-text">JPG or PNG, max 2 MB.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Assignable Roles</label>
                                <div class="role-assignable-wrap" id="roleAssignableWrap">
                                    @forelse ($assignableOptions as $opt)
                                        <div class="form-check">
                                            <input class="form-check-input role-assignable-cb" type="checkbox"
                                                id="assignRole{{ $opt->id }}" value="{{ $opt->id }}">
                                            <label class="form-check-label" for="assignRole{{ $opt->id }}">{{ $opt->name }}</label>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">Create more roles to use assignable roles.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="roleSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Role Permissions --}}
    <div class="modal fade admin-modal" id="rolePermissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-key me-1"></i> Assign Permissions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="permRoleId" value="">
                    <div class="role-perm-toolbar">
                        <span class="role-perm-role-badge">
                            <i class="bx bx-shield-quarter"></i>
                            Role: <span id="rolePermTitle">Role</span>
                        </span>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <input type="search" class="form-control form-control-sm" id="rolePermSearch"
                                placeholder="Search module...">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="permSelectAll">
                                <label class="form-check-label" for="permSelectAll">Select all</label>
                            </div>
                        </div>
                    </div>

                    <div class="role-perm-table-wrap">
                        <div class="admin-list-card-bar">
                            <h5><i class="bx bx-list-check me-1"></i> Module Permissions</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="role-perm-table">
                                <thead>
                                    <tr>
                                        <th style="width: 220px;">Module</th>
                                        <th>Permissions</th>
                                        <th class="text-center" style="width: 100px;">All</th>
                                    </tr>
                                </thead>
                                <tbody id="rolePermTableBody">
                                    @forelse ($permissionModules as $moduleKey => $perms)
                                        <tr class="role-perm-row"
                                            data-module-name="{{ strtolower($moduleMeta[$moduleKey] ?? $moduleKey) }}">
                                            <td>
                                                <div class="role-perm-module-name">
                                                    <span class="role-perm-module-icon">
                                                        <i class="bx bx-grid-alt"></i>
                                                    </span>
                                                    {{ $moduleMeta[$moduleKey] ?? \Illuminate\Support\Str::headline($moduleKey) }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="role-perm-checks" data-module-body="{{ $moduleKey }}">
                                                    @foreach ($perms as $perm)
                                                        <label class="role-perm-chip" for="perm_{{ $perm->id }}">
                                                            <input class="form-check-input perm-action" type="checkbox"
                                                                id="perm_{{ $perm->id }}"
                                                                value="{{ $perm->id }}"
                                                                data-perm-id="{{ $perm->id }}">
                                                            <span class="form-check-label">{{ $perm->description ?: $perm->action }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="text-center role-perm-all-cell">
                                                <div class="form-check justify-content-center">
                                                    <input class="form-check-input perm-module-all" type="checkbox"
                                                        id="moduleAll_{{ $moduleKey }}" data-module="{{ $moduleKey }}">
                                                    <label class="form-check-label"
                                                        for="moduleAll_{{ $moduleKey }}">All</label>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                No active permissions. Run Sync on the Permissions page.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="rolePermSaveBtn">
                        <i class="bx bx-save me-1"></i> Save permissions
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const routes = {
                store: @json(route('admin.master.roles.store')),
                update: @json(route('admin.master.roles')),
                destroy: @json(route('admin.master.roles')),
                perms: @json(route('admin.master.roles')),
            };

            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            }

            async function postForm(url, formData, method) {
                formData.append('_method', method || 'POST');
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Request failed');
                    throw new Error(msg);
                }
                return data;
            }

            async function postJson(url, body) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body || {})
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    throw new Error(data.message || 'Request failed');
                }
                return data;
            }

            async function getJson(url) {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    throw new Error(data.message || 'Request failed');
                }
                return data;
            }

            const roleModalEl = document.getElementById('addRoleModal');
            const roleModal = roleModalEl ? new bootstrap.Modal(roleModalEl) : null;
            const permModalEl = document.getElementById('rolePermissionsModal');
            const permModal = permModalEl ? new bootstrap.Modal(permModalEl) : null;

            function resetRoleForm() {
                document.getElementById('roleId').value = '';
                document.getElementById('roleForm')?.reset();
                document.getElementById('roleStatus').value = 'active';
                document.getElementById('roleModalTitle').textContent = 'Add Role';
                document.querySelectorAll('.role-assignable-cb').forEach(function(cb) {
                    cb.checked = false;
                    cb.disabled = false;
                });
            }

            function openAddModal() {
                resetRoleForm();
                roleModal?.show();
            }

            document.getElementById('btnAddRole')?.addEventListener('click', openAddModal);

            document.querySelectorAll('.btn-edit-role').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    resetRoleForm();
                    document.getElementById('roleModalTitle').textContent = 'Edit Role';
                    document.getElementById('roleId').value = btn.dataset.id;
                    document.getElementById('roleName').value = btn.dataset.name || '';
                    document.getElementById('roleStatus').value = btn.dataset.status || 'active';
                    document.getElementById('roleParent').value = btn.dataset.parent || '';
                    const assignable = (btn.dataset.assignable || '').split(',').filter(Boolean);
                    document.querySelectorAll('.role-assignable-cb').forEach(function(cb) {
                        cb.checked = assignable.indexOf(cb.value) !== -1;
                        cb.disabled = cb.value === btn.dataset.id;
                    });
                    if (btn.dataset.system === '1' && btn.dataset.name === 'developer') {
                        document.getElementById('roleName').readOnly = true;
                    } else {
                        document.getElementById('roleName').readOnly = false;
                    }
                    roleModal?.show();
                });
            });

            document.getElementById('roleSaveBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('roleId').value;
                const fd = new FormData(document.getElementById('roleForm'));
                document.querySelectorAll('.role-assignable-cb:checked').forEach(function(cb) {
                    fd.append('assignable_role_ids[]', cb.value);
                });
                try {
                    let data;
                    if (id) {
                        data = await postForm(routes.update + '/' + id, fd, 'PUT');
                    } else {
                        data = await postForm(routes.store, fd, 'POST');
                    }
                    roleModal?.hide();
                    await AdminAjax.success(data.message || 'Role saved.');
                    window.location.reload();
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });

            document.querySelectorAll('.btn-delete-role').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete role?',
                        text: 'This cannot be undone easily.',
                        confirmText: 'Yes, delete'
                    });
                    if (!ok) return;
                    const fd = new FormData();
                    try {
                        const data = await postForm(routes.destroy + '/' + btn.dataset.id, fd, 'DELETE');
                        AdminAjax.removeRow(btn.closest('tr'));
                        await AdminAjax.success(data.message || 'Deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            function syncModuleAllChecks() {
                permModalEl.querySelectorAll('[data-module-body]').forEach(function(body) {
                    const idx = body.getAttribute('data-module-body');
                    const allChk = permModalEl.querySelector('.perm-module-all[data-module="' + idx + '"]');
                    if (!allChk) return;
                    const actions = body.querySelectorAll('.perm-action');
                    const checked = body.querySelectorAll('.perm-action:checked');
                    allChk.checked = actions.length > 0 && actions.length === checked.length;
                });
            }

            document.querySelectorAll('.btn-role-perms').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    document.getElementById('permRoleId').value = btn.dataset.id;
                    document.getElementById('rolePermTitle').textContent = btn.dataset.roleName || 'Role';
                    document.getElementById('permSelectAll').checked = false;
                    document.getElementById('rolePermSearch').value = '';
                    permModalEl.querySelectorAll('.role-perm-row').forEach(function(row) {
                        row.style.display = '';
                    });
                    permModalEl.querySelectorAll('.perm-action').forEach(function(el) {
                        el.checked = false;
                    });
                    try {
                        const data = await getJson(routes.perms + '/' + btn.dataset.id + '/permissions');
                        const ids = (data.data && data.data.permission_ids) || [];
                        ids.forEach(function(id) {
                            const el = permModalEl.querySelector('.perm-action[data-perm-id="' + id + '"]');
                            if (el) el.checked = true;
                        });
                        syncModuleAllChecks();
                        permModal?.show();
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            document.getElementById('rolePermSaveBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('permRoleId').value;
                const permission_ids = Array.from(permModalEl.querySelectorAll('.perm-action:checked'))
                    .map(function(el) { return parseInt(el.value, 10); });
                try {
                    const data = await postJson(routes.perms + '/' + id + '/permissions', { permission_ids: permission_ids });
                    permModal?.hide();
                    await AdminAjax.success(data.message || 'Permissions saved.');
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });

            const selectAll = document.getElementById('permSelectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    permModalEl.querySelectorAll('.perm-action, .perm-module-all').forEach(function(el) {
                        if (el.closest('.role-perm-row')?.style.display === 'none') return;
                        el.checked = selectAll.checked;
                    });
                });
            }

            permModalEl?.querySelectorAll('.perm-module-all').forEach(function(chk) {
                chk.addEventListener('change', function() {
                    const idx = chk.getAttribute('data-module');
                    const body = permModalEl.querySelector('[data-module-body="' + idx + '"]');
                    if (!body) return;
                    body.querySelectorAll('.perm-action').forEach(function(el) {
                        el.checked = chk.checked;
                    });
                });
            });

            permModalEl?.querySelectorAll('[data-module-body]').forEach(function(body) {
                body.addEventListener('change', function(e) {
                    if (!e.target.classList.contains('perm-action')) return;
                    syncModuleAllChecks();
                });
            });

            document.getElementById('rolePermSearch')?.addEventListener('input', function() {
                const q = (this.value || '').toLowerCase().trim();
                permModalEl.querySelectorAll('.role-perm-row').forEach(function(row) {
                    const name = row.getAttribute('data-module-name') || '';
                    row.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        })();
    </script>
@endpush
