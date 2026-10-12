@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <h4 class="admin-page-title">
                        <i class="bx bx-calendar-check"></i>
                        Student Attendance
                    </h4>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Attendance</li>
                        <li class="breadcrumb-item active">Student Attendance</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.attendance.student')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-batch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                    :batches="$batches ?? collect()"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Student Attendance List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr. No.</th>
                                    <th>Batch</th>
                                    <th>Branch Name</th>
                                    <th>Time</th>
                                    <th>Total Student</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $authUser = auth()->user();
                                    $isPlatformUser = (bool) $authUser?->isPlatformUser();
                                    $canMarkAtt = $isPlatformUser || $authUser?->canAny(['attendance.add', 'attendance.edit']);
                                    $canShowAtt = $isPlatformUser || ($authUser?->can('attendance.show') && $authUser?->can('batch.show'));
                                @endphp
                                @forelse ($batchList as $batch)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $batch->name }}</td>
                                        <td>{{ $batch->branch?->name ?? '—' }}</td>
                                        <td>{{ $batch->timingLabel() }}</td>
                                        <td>{{ $batch->active_students_count ?? 0 }}</td>
                                        <td class="text-center">
                                            @if ($canMarkAtt)
                                                <a href="{{ route('admin.attendance.student.mark', $batch) }}" class="admin-action-icon" title="Mark Attendance">
                                                    <i class="bx bx-calendar-check"></i>
                                                </a>
                                            @endif
                                            @if ($canShowAtt)
                                                <a href="{{ route('admin.master.batch.show', $batch) }}" class="admin-action-icon" title="View Batch">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            @endif
                                            @if (! $canMarkAtt && ! $canShowAtt)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">No active batches found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection
