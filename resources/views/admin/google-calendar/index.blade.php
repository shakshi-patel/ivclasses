@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-calendar"></i>
                    Google Calendar
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">
                            Google Calendar
                        </li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-error-circle me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <div class="admin-detail-block">
                    @if ($connected)
                        <div class="admin-connected-box">
                            <i class="bx bx-check-circle"></i>
                            <div>
                                <div>Connected successfully</div>
                                <div class="small fw-normal" style="color: #2d6a4f;">
                                    Google Calendar is connected. Demo slots and Meet links will use this account.
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="mb-3" style="color: var(--iv-primary);">
                            Google Calendar is not connected. Connect it to enable demo booking.
                        </p>
                        <a href="{{ route('admin.google-calendar.connect') }}" class="btn"
                            style="background: var(--iv-primary); color: #fff;">
                            Connect Google Calendar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
