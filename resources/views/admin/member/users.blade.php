@extends('admin.layouts.layout')

@section('content')
    @php
        $authUser = auth()->user();
        $can = fn (string $perm) => $authUser && ($authUser->isPlatformUser() || $authUser->can($perm));

        $addLinks = [];
        if ($can('teacher.add')) {
            $addLinks[] = ['label' => 'Teacher', 'url' => route('admin.master.teacher.add')];
        }
        if ($can('staff.add')) {
            $addLinks[] = ['label' => 'Principal', 'url' => route('admin.staff.add', ['role' => 'principal'])];
            $addLinks[] = ['label' => 'Receptionist', 'url' => route('admin.staff.add', ['role' => 'receptionist'])];
            $addLinks[] = ['label' => 'Accountant', 'url' => route('admin.staff.add', ['role' => 'accountant'])];
        }
        if ($can('branch.add')) {
            $addLinks[] = ['label' => 'Branch Manager', 'url' => route('admin.master.branch.add')];
        }

        $roleAdds = array_filter([
            'teacher' => $can('teacher.add')
                ? ['label' => 'Add Teacher', 'url' => route('admin.master.teacher.add')]
                : null,
            'principal' => $can('staff.add')
                ? ['label' => 'Add Principal', 'url' => route('admin.staff.add', ['role' => 'principal'])]
                : null,
            'receptionist' => $can('staff.add')
                ? ['label' => 'Add Receptionist', 'url' => route('admin.staff.add', ['role' => 'receptionist'])]
                : null,
            'accountant' => $can('staff.add')
                ? ['label' => 'Add Accountant', 'url' => route('admin.staff.add', ['role' => 'accountant'])]
                : null,
            'branch_manager' => $can('branch.add')
                ? ['label' => 'Add Branch Manager', 'url' => route('admin.master.branch.add')]
                : null,
        ]);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page" id="memberUsersPage" data-active-role="{{ $role }}">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-group"></i>
                            Users
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="usersToolbarActions">
                        @if ($canTeacher)
                            <a href="{{ route('admin.export.teachers') }}"
                                class="btn btn-outline-secondary {{ $role === 'teacher' || ($role === 'all' && ! $canStaff) ? '' : 'd-none' }}"
                                data-user-export="teacher">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if ($canStaff)
                            <a href="{{ route('admin.export.staff') }}"
                                class="btn btn-outline-secondary {{ $role !== 'all' && $role !== 'teacher' ? '' : 'd-none' }}"
                                data-user-export="staff">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if ($canTeacher && $canStaff)
                            <div class="dropdown {{ $role === 'all' ? '' : 'd-none' }}" data-user-export="both">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bx bx-download me-1"></i> Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.export.teachers') }}">Teachers</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.export.staff') }}">Staff</a></li>
                                </ul>
                            </div>
                        @endif

                        @foreach ($roleAdds as $key => $link)
                            <a href="{{ $link['url'] }}"
                                class="btn admin-btn-primary {{ $role === $key ? '' : 'd-none' }}"
                                data-user-add="{{ $key }}">
                                <i class="bx bx-plus me-1"></i> {{ $link['label'] }}
                            </a>
                        @endforeach

                        @if ($addLinks !== [])
                            <div class="dropdown {{ $role === 'all' ? '' : 'd-none' }}" data-user-add="all">
                                <button type="button" class="btn admin-btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bx bx-plus me-1"></i> Add User
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($addLinks as $link)
                                        <li><a class="dropdown-item" href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Member</li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.users')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                    :institutes="$institutes"
                    :branches="$branches"
                />
            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="users-role-tabs">
                        <ul class="nav nav-pills master-tabs gap-2 flex-wrap mb-0" role="tablist">
                            @foreach ($tabs as $tab)
                                <li class="nav-item" role="presentation">
                                    <button type="button"
                                        class="nav-link {{ $tab['active'] ? 'active' : '' }}"
                                        data-user-tab="{{ $tab['key'] }}"
                                        data-user-label="{{ $tab['label'] }}"
                                        role="tab"
                                        @if ($tab['active']) aria-current="page" @endif>
                                        {{ $tab['label'] }}
                                        <span class="user-tab-count" data-user-count="{{ $tab['key'] }}">{{ $tab['count'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="admin-list-card-bar">
                        <h5 id="usersListTitle">{{ $role === 'all' ? 'All users' : $roleLabel }}</h5>
                    </div>
                    <table id="memberUsersTable" class="table admin-list-table align-middle mb-0 {{ $users->isEmpty() ? '' : 'datatables-basic' }}" style="min-width: 1100px;">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Sr. No.</th>
                                <th style="width: 72px;">Profile</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th style="width: 110px;">Status</th>
                                <th class="text-center" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $i => $row)
                                @php
                                    $canShow = $row['kind'] === 'teacher' ? $can('teacher.show') : $can('staff.show');
                                    $canEdit = $row['kind'] === 'teacher' ? $can('teacher.edit') : $can('staff.edit');
                                    $canStatus = $row['kind'] === 'teacher' ? $can('teacher.status') : $can('staff.status');
                                    $canDelete = $row['kind'] === 'teacher' ? $can('teacher.delete') : $can('staff.delete');
                                    $canPermissions = $can('roles.assign_permissions');
                                @endphp
                                <tr data-id="{{ $row['kind'] }}-{{ $row['id'] }}" data-user-role="{{ $row['role'] }}">
                                    <td data-role="sr">{{ $i + 1 }}</td>
                                    <td>
                                        <img src="{{ $row['avatar'] }}" alt="" width="40" height="40"
                                            class="rounded-circle"
                                            style="object-fit: cover; border: 2px solid rgba(var(--iv-secondary-rgb), 0.4);">
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $row['name'] }}
                                        @if ($row['code'])
                                            <small class="text-muted d-block">{{ $row['code'] }}</small>
                                        @endif
                                    </td>
                                    <td><span class="stu-method-pill">{{ $row['role_label'] }}</span></td>
                                    <td>{{ $row['branch'] }}</td>
                                    <td>{{ $row['phone'] }}</td>
                                    <td>{{ $row['email'] }}</td>
                                    <td>
                                        @if ($row['active'])
                                            <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                        @else
                                            <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($canShow)
                                            <a href="{{ $row['show_url'] }}" class="admin-action-icon" title="View">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        @endif
                                        @if ($canEdit)
                                            <a href="{{ $row['edit_url'] }}" class="admin-action-icon" title="Edit">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        @endif
                                        @if ($canPermissions && $row['user_id'])
                                            <button type="button"
                                                class="admin-action-icon btn-user-perms"
                                                title="Permissions"
                                                data-id="{{ $row['user_id'] }}"
                                                data-user-name="{{ $row['name'] }}">
                                                <i class="bx bx-key"></i>
                                            </button>
                                        @endif
                                        @if ($canStatus)
                                            <button type="button"
                                                class="admin-action-icon btn-toggle-user"
                                                title="{{ $row['active'] ? 'Deactivate' : 'Activate' }}"
                                                data-url="{{ $row['toggle_url'] }}"
                                                data-active="{{ $row['active'] ? '1' : '0' }}">
                                                <i class="bx {{ $row['active'] ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button"
                                                class="admin-action-icon btn-delete-user"
                                                title="Delete"
                                                data-url="{{ $row['destroy_url'] }}"
                                                data-kind="{{ $row['kind'] }}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- User Permissions --}}
    <div class="modal fade admin-modal" id="userPermissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-key me-1"></i> User Permissions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="permUserId" value="">
                    <div class="role-perm-toolbar">
                        <span class="role-perm-role-badge">
                            <i class="bx bx-user"></i>
                            User: <span id="userPermTitle">User</span>
                        </span>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <input type="search" class="form-control form-control-sm" id="userPermSearch"
                                placeholder="Search module...">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="userPermSelectAll">
                                <label class="form-check-label" for="userPermSelectAll">Select all</label>
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
                                <tbody id="userPermTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Loading…</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="userPermSaveBtn">
                        <i class="bx bx-save me-1"></i> Save permissions
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .uperm-via-role {
            display: inline-block;
            margin-left: 6px;
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--iv-primary);
            background: rgba(var(--iv-primary-rgb), 0.1);
            border-radius: 999px;
            padding: 1px 7px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            var page = document.getElementById('memberUsersPage');
            if (!page) return;

            var activeRole = page.getAttribute('data-active-role') || 'all';
            var emptyText = {
                all: 'No users yet.',
                branch_manager: 'No branch managers yet.',
                teacher: 'No teachers yet.',
                principal: 'No principals yet.',
                receptionist: 'No receptionists yet.',
                accountant: 'No accountants yet.'
            };

            if (window.jQuery && jQuery.fn.dataTable) {
                jQuery.fn.dataTable.ext.search.push(function(settings, data, index) {
                    if (!settings.nTable || settings.nTable.id !== 'memberUsersTable') {
                        return true;
                    }
                    if (activeRole === 'all') {
                        return true;
                    }
                    var row = settings.aoData[index] && settings.aoData[index].nTr;
                    return !!(row && row.getAttribute('data-user-role') === activeRole);
                });
            }

            var url = new URL(window.location.href);
            if (url.searchParams.has('role')) {
                url.searchParams.delete('role');
                var next = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash;
                window.history.replaceState({}, '', next);
            }

            function toggleMarked(attr, visibleKey) {
                document.querySelectorAll('[' + attr + ']').forEach(function(el) {
                    el.classList.toggle('d-none', el.getAttribute(attr) !== visibleKey);
                });
            }

            function syncChrome(label) {
                var title = document.getElementById('usersListTitle');
                if (title) {
                    title.textContent = activeRole === 'all' ? 'All users' : label;
                }

                var exportKey = 'none';
                if (activeRole === 'all') {
                    exportKey = document.querySelector('[data-user-export="both"]') ? 'both' : (document.querySelector('[data-user-export="teacher"]') ? 'teacher' : 'staff');
                } else if (activeRole === 'teacher') {
                    exportKey = 'teacher';
                } else {
                    exportKey = 'staff';
                }
                toggleMarked('data-user-export', exportKey);
                toggleMarked('data-user-add', activeRole);

                if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#memberUsersTable')) {
                    var table = jQuery('#memberUsersTable').DataTable();
                    table.settings()[0].oLanguage.sZeroRecords = emptyText[activeRole] || 'No users yet.';
                    table.draw();
                }
            }

            document.querySelectorAll('[data-user-tab]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (btn.getAttribute('data-user-tab') === activeRole) {
                        return;
                    }
                    activeRole = btn.getAttribute('data-user-tab');
                    document.querySelectorAll('[data-user-tab]').forEach(function(other) {
                        var on = other === btn;
                        other.classList.toggle('active', on);
                        if (on) {
                            other.setAttribute('aria-current', 'page');
                        } else {
                            other.removeAttribute('aria-current');
                        }
                    });
                    syncChrome(btn.getAttribute('data-user-label') || 'Users');
                });
            });

            document.querySelectorAll('.btn-toggle-user').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    try {
                        const data = await AdminAjax.post(btn.dataset.url);
                        AdminAjax.applyStatusToggle(btn, AdminAjax.statusFrom(data) === 'active');
                        await AdminAjax.success(data.message || 'Status updated.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            function bumpCount(role, delta) {
                ['all', role].forEach(function(key) {
                    var el = document.querySelector('[data-user-count="' + key + '"]');
                    if (!el) return;
                    var next = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
                    el.textContent = String(next);
                });
            }

            document.querySelectorAll('.btn-delete-user').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    var kind = btn.dataset.kind === 'teacher' ? 'teacher' : 'user';
                    var ok = await AdminAjax.confirm({
                        title: 'Delete ' + kind + '?',
                        text: 'Their login will be removed.',
                        confirmText: 'Yes, delete'
                    });
                    if (!ok) return;
                    try {
                        var row = btn.closest('tr');
                        var role = row ? row.getAttribute('data-user-role') : '';
                        var data = await AdminAjax.destroy(btn.dataset.url);
                        AdminAjax.removeRow(row);
                        if (role) bumpCount(role, -1);
                        await AdminAjax.success(data.message || 'Deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            if (window.jQuery) {
                jQuery(function() {
                    if (activeRole === 'all') return;
                    var current = document.querySelector('[data-user-tab="' + activeRole + '"]');
                    syncChrome(current ? current.getAttribute('data-user-label') : 'Users');
                });
            }

            // --- User Permissions modal ---
            var permsBase = @json(route('admin.users'));
            var permModalEl = document.getElementById('userPermissionsModal');
            var permModal = (permModalEl && window.bootstrap) ? new bootstrap.Modal(permModalEl) : null;
            var permBody = document.getElementById('userPermTableBody');

            function permChip(perm) {
                var chip = document.createElement('label');
                chip.className = 'role-perm-chip';
                chip.setAttribute('for', 'uperm_' + perm.id);

                var input = document.createElement('input');
                input.className = 'form-check-input perm-action';
                input.type = 'checkbox';
                input.id = 'uperm_' + perm.id;
                input.value = perm.id;
                input.setAttribute('data-perm-id', perm.id);
                input.setAttribute('data-via-role', perm.via_role ? '1' : '0');
                input.checked = !!perm.checked;

                var label = document.createElement('span');
                label.className = 'form-check-label';
                label.textContent = perm.label;
                if (perm.via_role) {
                    var badge = document.createElement('span');
                    badge.className = 'uperm-via-role';
                    badge.textContent = 'via role';
                    label.appendChild(badge);
                }

                chip.appendChild(input);
                chip.appendChild(label);
                return chip;
            }

            function syncModuleAllChecks() {
                permModalEl.querySelectorAll('[data-module-body]').forEach(function(body) {
                    var idx = body.getAttribute('data-module-body');
                    var allChk = permModalEl.querySelector('.perm-module-all[data-module="' + idx + '"]');
                    if (!allChk) return;
                    var actions = body.querySelectorAll('.perm-action');
                    var checked = body.querySelectorAll('.perm-action:checked');
                    allChk.checked = actions.length > 0 && actions.length === checked.length;
                });
            }

            function renderModules(modules) {
                permBody.innerHTML = '';
                if (!modules.length) {
                    permBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No active permissions.</td></tr>';
                    return;
                }
                modules.forEach(function(mod) {
                    var tr = document.createElement('tr');
                    tr.className = 'role-perm-row';
                    tr.setAttribute('data-module-name', mod.label.toLowerCase());

                    var tdName = document.createElement('td');
                    tdName.innerHTML = '<div class="role-perm-module-name"><span class="role-perm-module-icon">' +
                        '<i class="bx bx-grid-alt"></i></span>' + mod.label + '</div>';

                    var tdPerms = document.createElement('td');
                    var checksWrap = document.createElement('div');
                    checksWrap.className = 'role-perm-checks';
                    checksWrap.setAttribute('data-module-body', mod.key);
                    mod.permissions.forEach(function(perm) {
                        checksWrap.appendChild(permChip(perm));
                    });
                    tdPerms.appendChild(checksWrap);

                    var tdAll = document.createElement('td');
                    tdAll.className = 'text-center role-perm-all-cell';
                    tdAll.innerHTML = '<div class="form-check justify-content-center">' +
                        '<input class="form-check-input perm-module-all" type="checkbox" data-module="' + mod.key + '">' +
                        '</div>';

                    tr.appendChild(tdName);
                    tr.appendChild(tdPerms);
                    tr.appendChild(tdAll);
                    permBody.appendChild(tr);
                });

                syncModuleAllChecks();

                permModalEl.querySelectorAll('.perm-module-all').forEach(function(chk) {
                    chk.addEventListener('change', function() {
                        var body = permModalEl.querySelector('[data-module-body="' + chk.getAttribute('data-module') + '"]');
                        if (!body) return;
                        body.querySelectorAll('.perm-action').forEach(function(el) {
                            el.checked = chk.checked;
                        });
                    });
                });

                permModalEl.querySelectorAll('[data-module-body]').forEach(function(body) {
                    body.addEventListener('change', function(e) {
                        if (!e.target.classList.contains('perm-action')) return;
                        syncModuleAllChecks();
                    });
                });
            }

            document.querySelectorAll('.btn-user-perms').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    document.getElementById('permUserId').value = btn.dataset.id;
                    document.getElementById('userPermTitle').textContent = btn.dataset.userName || 'User';
                    document.getElementById('userPermSelectAll').checked = false;
                    document.getElementById('userPermSearch').value = '';
                    permBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Loading…</td></tr>';
                    permModal?.show();
                    try {
                        const data = await AdminAjax.get(permsBase + '/' + btn.dataset.id + '/permissions');
                        renderModules((data.data && data.data.modules) || []);
                    } catch (e) {
                        permBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Failed to load permissions.</td></tr>';
                        await AdminAjax.error(e.message);
                    }
                });
            });

            document.getElementById('userPermSaveBtn')?.addEventListener('click', async function() {
                var id = document.getElementById('permUserId').value;
                var grantIds = [];
                var denyIds = [];
                permModalEl.querySelectorAll('.perm-action').forEach(function(el) {
                    var permId = parseInt(el.getAttribute('data-perm-id'), 10);
                    var viaRole = el.getAttribute('data-via-role') === '1';
                    if (el.checked && !viaRole) grantIds.push(permId);
                    if (!el.checked && viaRole) denyIds.push(permId);
                });
                try {
                    const data = await AdminAjax.post(permsBase + '/' + id + '/permissions', {
                        grant_ids: grantIds,
                        deny_ids: denyIds
                    });
                    permModal?.hide();
                    await AdminAjax.success(data.message || 'Permissions saved.');
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });

            document.getElementById('userPermSelectAll')?.addEventListener('change', function() {
                var checked = this.checked;
                permModalEl.querySelectorAll('.perm-action, .perm-module-all').forEach(function(el) {
                    if (el.closest('.role-perm-row')?.style.display === 'none') return;
                    el.checked = checked;
                });
            });

            document.getElementById('userPermSearch')?.addEventListener('input', function() {
                var q = (this.value || '').toLowerCase().trim();
                permModalEl.querySelectorAll('.role-perm-row').forEach(function(row) {
                    var name = row.getAttribute('data-module-name') || '';
                    row.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        })();
    </script>
@endpush
