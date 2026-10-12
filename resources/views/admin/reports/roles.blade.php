@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y report-page">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                @include('admin.reports._header', [
                    'reportTitle' => 'Roles Report',
                    'reportSubtitle' => 'Permissions and users per role',
                    'reportIcon' => 'bx-shield-quarter',
                    'reportBackUrl' => route('admin.reports'),
                ])

                <div class="admin-table-wrap mt-3">
                    <div class="admin-list-card-bar">
                        <h5>Roles ({{ $rows->count() }})</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Sr.</th>
                                    <th>Role Name</th>
                                    <th>Parent</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row['name'] }}</td>
                                        <td>{{ $row['parent'] }}</td>
                                        <td>{{ $row['permissions_count'] }}</td>
                                        <td>{{ $row['users_count'] }}</td>
                                        <td>
                                            @if ($row['status'] === 'active')
                                                <span class="admin-status-pill is-active">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['created_at'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No roles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
