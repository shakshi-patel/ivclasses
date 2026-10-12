@extends('admin.layouts.layout')

@php
    $tab = request()->query('tab', 'personal');
    if (!in_array($tab, ['personal', 'password'], true)) {
        $tab = 'personal';
    }
    $profileUser = auth()->user();
@endphp

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-user-circle"></i>
                    Profile
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Profile
                        </li>
                    </ol>
                </nav>

                <ul class="nav nav-pills gap-2 mt-4 mb-3 admin-settings-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'personal' ? 'active' : '' }}"
                            href="{{ route('admin.profile', ['tab' => 'personal']) }}">
                            <i class="bx bx-user me-1"></i> Personal Information
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'password' ? 'active' : '' }}"
                            href="{{ route('admin.profile', ['tab' => 'password']) }}">
                            <i class="bx bx-lock-alt me-1"></i> Change Password
                        </a>
                    </li>
                </ul>

                @if ($tab === 'personal')
                    <div class="admin-detail-block">
                        <h5 class="mb-1">Personal information</h5>
                        <p class="text-muted mb-4" style="font-size: 0.88rem;">
                            Update your account details and profile photo.
                        </p>

                        <form action="#" method="POST" onsubmit="return false;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Profile photo</label>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}"
                                            class="rounded-circle border"
                                            style="width: 72px; height: 72px; object-fit: cover;">
                                        <div>
                                            <input type="file" class="form-control" accept="image/*"
                                                style="max-width: 280px;">
                                            <div class="form-text">JPG or PNG, max 2 MB.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full name</label>
                                    <input type="text" class="form-control" value="{{ $profileUser->name }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" value="{{ $profileUser->email }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <input type="text" class="form-control" value="{{ $profileUser->phone }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Role</label>
                                    <input type="text" class="form-control" value="{{ $profileUser->typeLabel() }}" readonly>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn px-4 admin-btn-primary">
                                    Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($tab === 'password')
                    <div class="admin-detail-block">
                        <h5 class="mb-1">Change password</h5>
                        <p class="text-muted mb-4" style="font-size: 0.88rem;">
                            Choose a strong password you don’t use elsewhere.
                        </p>

                        <form action="#" method="POST" onsubmit="return false;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Current password</label>
                                    <input type="password" class="form-control" placeholder="Enter current password">
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New password</label>
                                    <input type="password" class="form-control" placeholder="Enter new password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm new password</label>
                                    <input type="password" class="form-control" placeholder="Re-enter new password">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn px-4 admin-btn-primary">
                                    Update password
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
