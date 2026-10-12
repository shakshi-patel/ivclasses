@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-message-detail"></i>
                            Project Inquiry
                        </h4>
                    </div>
                    <a href="{{ route('admin.project-inquiries.create') }}" class="btn admin-btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Inquiry
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.project-inquiries.index') }}">Project Inquiry</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Inquiry List</li>
                    </ol>
                </nav>

                <div class="admin-filter-section">
                    <form method="GET" action="{{ route('admin.project-inquiries.index') }}"
                        class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="q" class="form-label fw-semibold">Search</label>
                            <input type="text" name="q" id="q" class="form-control" value="{{ request('q') }}"
                                placeholder="Inquiry code, institute, contact, email">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>
                                        {{ $statusOption->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="demo_status" class="form-label fw-semibold">Demo
                                Status</label>
                            <select name="demo_status" id="demo_status" class="form-select">
                                <option value="">All</option>
                                @foreach ($demoStatusOptions as $demoOption)
                                    <option value="{{ $demoOption->value }}" @selected(request('demo_status') === $demoOption->value)>
                                        {{ $demoOption->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn w-100 admin-btn-primary">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Inquiry List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Inquiry Code</th>
                            <th>Institute</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Demo Date</th>
                            <th>Demo Status</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inquiries as $inquiry)
                            <tr>
                                <td>{{ $inquiries->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $inquiry->inquiry_code }}</td>
                                <td>{{ $inquiry->institute_name }}</td>
                                <td>{{ $inquiry->contact_person_name }}</td>
                                <td>{{ $inquiry->email }}</td>
                                <td>{{ $inquiry->contact_no }}</td>
                                <td>{{ $inquiry->city }}</td>
                                <td>
                                    {{ optional($inquiry->demo_date)->format('d M Y') ?? '--' }}
                                    @if ($inquiry->demo_start_time)
                                        <div class="text-muted small">{{ date('h:i A', strtotime($inquiry->demo_start_time)) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ $inquiry->demo_status?->pillClass() ?? 'is-navy' }}">
                                        {{ $inquiry->demo_status?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ $inquiry->status?->pillClass() ?? 'is-muted' }}">
                                        {{ $inquiry->status?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.project-inquiries.show', $inquiry) }}"
                                        class="admin-action-icon" aria-label="View inquiry">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">No project inquiries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                    <div class="p-3">
                        {{ $inquiries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
