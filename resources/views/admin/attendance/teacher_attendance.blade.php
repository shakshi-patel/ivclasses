@extends('admin.layouts.layout')

@section('content')
    @php
        $isMarked = $meta['is_marked'] ?? false;
        $authUser = auth()->user();
        $isPlatformUser = (bool) $authUser?->isPlatformUser();
        $canSaveAtt = $isPlatformUser || (bool) $authUser?->can($isMarked ? 'teacher_attendance.edit' : 'teacher_attendance.add');
        $canShowAtt = $isPlatformUser || (bool) $authUser?->can('teacher_attendance.show');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <h4 class="admin-page-title"><i class="bx bx-user-check"></i> Teacher &amp; Staff Attendance</h4>
                    @if ($instituteId && $canSaveAtt)
                        <a href="{{ route('admin.attendance.teacher.mark', ['date' => $date, 'branch_id' => $branchId, 'institute_id' => $instituteId]) }}" class="btn admin-btn-primary">
                            <i class="bx bx-calendar-check me-1"></i> {{ $isMarked ? 'Update Attendance' : 'Mark Attendance' }}
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Attendance</li>
                        <li class="breadcrumb-item active">Teacher Attendance</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.attendance.teacher')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control form-control-sm" name="date" value="{{ $date }}">
                    </div>
                </x-admin.list-filters>

                @if ($isPlatform && ! $instituteId)
                    <div class="alert alert-info mt-3 mb-0">Select an institute to view teacher &amp; staff attendance.</div>
                @endif

                @if ($isMarked)
                    <div class="alert alert-success mt-3 mb-0">
                        Attendance marked for {{ $meta['marked_count'] ?? 0 }}/{{ $meta['total_staff'] ?? 0 }} on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.
                    </div>
                @endif

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Teacher &amp; Staff List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 950px;">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Branch</th>
                                    <th>Batch</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roster as $row)
                                    @php
                                        $viewUrl = null;
                                        $profileType = $row['profile_type'] ?? '';
                                        if ($canShowAtt && ! empty($row['profile_id'])) {
                                            if ($profileType === 'teacher' && ($isPlatformUser || $authUser?->can('teacher.show'))) {
                                                $viewUrl = route('admin.master.teacher.show', $row['profile_id']);
                                            } elseif ($profileType === 'staff' && ($isPlatformUser || $authUser?->can('staff.show'))) {
                                                $viewUrl = route('admin.staff.show', $row['profile_id']);
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td data-role="sr">{{ $loop->iteration }}</td>
                                        <td>{{ $row['branch_name'] }}</td>
                                        <td>{{ $row['batch_name'] }}</td>
                                        <td class="fw-semibold">{{ $row['name'] }}</td>
                                        <td>{{ $row['role'] }}</td>
                                        <td>
                                            @php($att = \App\Enums\AttendanceStatus::tryFromMixed($row['status'] ?? null))
                                            <span class="admin-status-pill {{ $att?->pillClass() ?? 'is-muted' }}">
                                                {{ $att?->label() ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($viewUrl)
                                                <a href="{{ $viewUrl }}" class="admin-action-icon" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">{{ $isPlatform && ! $instituteId ? 'Select an institute to load the roster.' : 'No active teachers or staff found.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection
