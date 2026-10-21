@extends('admin.layouts.layout')

@php
    $tab = $tab ?? request()->query('tab', 'personal');
    if (! in_array($tab, ['personal', 'password'], true)) {
        $tab = 'personal';
    }
    $profileUser = $profileUser ?? auth()->user()->fresh();
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
                            Loaded live from the <strong>users</strong> table (same name as Good morning).
                            Last saved {{ $profileUser->updated_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: '—' }}.
                        </p>

                        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Profile photo</label>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}"
                                            class="rounded-circle border"
                                            style="width: 72px; height: 72px; object-fit: cover;">
                                        <div>
                                            <input type="file" name="avatar" class="form-control" accept="image/*"
                                                style="max-width: 280px;">
                                            <div class="form-text">JPG or PNG, max 2 MB.</div>
                                            @error('avatar')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="120"
                                        autocomplete="off"
                                        value="{{ old('name', $profileUser->name) }}">
                                    @error('name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" value="{{ $profileUser->email }}" readonly>
                                    <div class="form-text">Login email cannot be changed here.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <input type="text" name="phone" class="form-control" maxlength="20"
                                        autocomplete="off"
                                        value="{{ old('phone', $profileUser->phone) }}">
                                    @error('phone')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Role</label>
                                    <input type="text" class="form-control" value="{{ $profileUser->typeLabel() }}" readonly>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn px-4 admin-btn-primary">
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

                        <form action="{{ route('admin.profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Current password</label>
                                    <input type="password" name="current_password" class="form-control"
                                        placeholder="Enter current password" required>
                                    @error('current_password')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New password</label>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Enter new password" required>
                                    @error('password')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm new password</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        placeholder="Re-enter new password" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn px-4 admin-btn-primary">
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
