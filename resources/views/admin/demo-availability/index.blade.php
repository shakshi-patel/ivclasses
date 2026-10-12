@extends('admin.layouts.layout')

@section('content')
    @php
        $dayMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-time-five"></i>
                    Demo Availability
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.demo-availability.index') }}">Demo Availability</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Availability List</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert" aria-live="polite">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mb-3" role="alert" aria-live="assertive">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bx bx-error-circle me-2 fs-5"></i>
                            <strong>Please fix the following errors:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-filter-section">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bx bx-calendar-plus" style="font-size: 1.2rem; color: var(--iv-primary);"></i>
                        <h5 class="mb-0 admin-filter-section-title">Add Availability</h5>
                    </div>

                    <form action="{{ route('admin.demo-availability.store') }}" method="POST"
                        class="row g-3 align-items-end">
                        @csrf

                        <div class="col-md-2">
                            <label for="day_of_week" class="form-label fw-semibold">Day of
                                Week</label>
                            <select name="day_of_week" id="day_of_week" class="form-select" required>
                                @foreach ($dayMap as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="start_time" class="form-label fw-semibold">Start
                                Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="end_time" class="form-label fw-semibold">End
                                Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="slot_duration_minutes" class="form-label fw-semibold">Demo Duration</label>
                            <input type="number" name="slot_duration_minutes" id="slot_duration_minutes"
                                class="form-control" min="15" step="15" value="60" required>
                        </div>

                        <div class="col-md-2">
                            <label for="buffer_minutes" class="form-label fw-semibold">Buffer
                                Time</label>
                            <input type="number" name="buffer_minutes" id="buffer_minutes" class="form-control" min="0"
                                step="5" value="0" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold d-block admin-form-label">Active</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                    checked>
                                <label class="form-check-label" for="is_active">Enabled</label>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-md-end">
                            <button type="submit" class="btn px-4 py-2 admin-btn-primary">
                                <i class="bx bx-plus me-1"></i>
                                Add Availability
                            </button>
                        </div>
                    </form>
                </div>

                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Availability List</h5>
                    </div>
                    <div class="table-responsive">
                <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration</th>
                            <th>Buffer</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($availabilities as $availability)
                            <tr>
                                <td class="fw-semibold">{{ $dayMap[$availability->day_of_week] ?? 'Unknown' }}</td>
                                <td>{{ $availability->start_time ? date('h:i A', strtotime($availability->start_time)) : '--' }}</td>
                                <td>{{ $availability->end_time ? date('h:i A', strtotime($availability->end_time)) : '--' }}</td>
                                <td>{{ $availability->slot_duration_minutes ?? 0 }} mins</td>
                                <td>{{ $availability->buffer_minutes ?? 0 }} mins</td>
                                <td>
                                    @if ($availability->is_active)
                                        <span class="admin-status-pill is-active">Active</span>
                                    @else
                                        <span class="admin-status-pill is-muted">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="admin-action-icon" data-bs-toggle="modal"
                                        data-bs-target="#editAvailabilityModal{{ $availability->id }}"
                                        aria-label="Edit availability">
                                        <i class="bx bx-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.demo-availability.destroy', $availability) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this availability slot?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-action-icon" aria-label="Delete availability">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($availabilities as $availability)
            <div class="modal fade admin-modal" id="editAvailabilityModal{{ $availability->id }}" tabindex="-1"
                aria-labelledby="editAvailabilityModalLabel{{ $availability->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('admin.demo-availability.update', $availability) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title"
                                    id="editAvailabilityModalLabel{{ $availability->id }}">
                                    Edit Availability
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="edit_day_of_week_{{ $availability->id }}"
                                            class="form-label fw-semibold">Day of Week</label>
                                        <select name="day_of_week"
                                            id="edit_day_of_week_{{ $availability->id }}"
                                            class="form-select" required>
                                            @foreach ($dayMap as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ $availability->day_of_week == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="edit_is_active_{{ $availability->id }}"
                                            class="form-label fw-semibold">Status</label>
                                        <select name="is_active"
                                            id="edit_is_active_{{ $availability->id }}"
                                            class="form-select" required>
                                            <option value="1"
                                                {{ $availability->is_active ? 'selected' : '' }}>Active
                                            </option>
                                            <option value="0"
                                                {{ !$availability->is_active ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="edit_start_time_{{ $availability->id }}"
                                            class="form-label fw-semibold">Start Time</label>
                                        <input type="time" name="start_time"
                                            id="edit_start_time_{{ $availability->id }}"
                                            class="form-control"
                                            value="{{ $availability->start_time }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="edit_end_time_{{ $availability->id }}"
                                            class="form-label fw-semibold">End Time</label>
                                        <input type="time" name="end_time"
                                            id="edit_end_time_{{ $availability->id }}"
                                            class="form-control"
                                            value="{{ $availability->end_time }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label
                                            for="edit_slot_duration_minutes_{{ $availability->id }}"
                                            class="form-label fw-semibold">Demo Duration</label>
                                        <input type="number" name="slot_duration_minutes"
                                            id="edit_slot_duration_minutes_{{ $availability->id }}"
                                            class="form-control" min="15" step="15"
                                            value="{{ $availability->slot_duration_minutes }}"
                                            required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="edit_buffer_minutes_{{ $availability->id }}"
                                            class="form-label fw-semibold">Buffer Time</label>
                                        <input type="number" name="buffer_minutes"
                                            id="edit_buffer_minutes_{{ $availability->id }}"
                                            class="form-control" min="0" step="5"
                                            value="{{ $availability->buffer_minutes }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn admin-btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
