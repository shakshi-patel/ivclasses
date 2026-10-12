@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($staffMember) && $staffMember;
        $user = auth()->user();
        $isPlatform = $user?->isPlatformUser();
        $selectedBranchId = old('primary_branch_id', $staffMember?->primaryBranch()?->id);
        $selectedRoleId = old('role_id', $staffRoleId ?? null);
        $listRole = $listRole ?? null;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'user-plus' }}"></i>
                    {{ $isEdit ? 'Edit Staff' : 'Add Staff' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.users', !empty($listRole) ? ['role' => $listRole] : []) }}">Users</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $isEdit ? 'Edit' : 'Add' }}
                        </li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.users', !empty($listRole) ? ['role' => $listRole] : []) }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Staff' : 'Add Staff' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.staff.update', $staffMember) : route('admin.staff.store') }}"
                                enctype="multipart/form-data"
                                data-institute-cascade
                                data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
                                id="staffForm">
                                @csrf
                                @if ($isEdit)
                                    @method('PUT')
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Staff Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name', $staffMember?->user?->name) }}"
                                            placeholder="Enter staff name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact No.</label>
                                        <input type="text" class="form-control" name="phone" maxlength="15" inputmode="numeric"
                                            value="{{ old('phone', $staffMember?->user?->phone) }}"
                                            placeholder="Enter contact number">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address (login) <span class="req">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $staffMember?->user?->email) }}"
                                            placeholder="Enter email address" required>
                                    </div>
                                    @include('admin.partials.form_institute_field', [
                                        'instituteId' => old('institute_id', $staffMember?->institute_id),
                                        'institutes' => $institutes,
                                        'label' => 'Classes / Institute',
                                    ])
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => $selectedBranchId,
                                        'branches' => $branches,
                                        'name' => 'primary_branch_id',
                                        'label' => 'Branch',
                                    ])
                                    <div class="col-md-6">
                                        <label class="form-label">Role <span class="req">*</span></label>
                                        <select class="form-select" name="role_id" required
                                            data-cascade-target="roles"
                                            data-placeholder="Select role"
                                            @if ($selectedRoleId) data-keep-value="{{ $selectedRoleId }}" @endif>
                                            <option value="">Select role</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}" data-slug="{{ $role->name }}" @selected($selectedRoleId == $role->id)>
                                                    {{ \Illuminate\Support\Str::headline($role->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Principal / Accountant / Receptionist — Roles page thi permissions set karo. Branch Manager Branch add page par create thay.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Designation</label>
                                        <input type="text" class="form-control" name="designation"
                                            value="{{ old('designation', $staffMember?->designation) }}"
                                            placeholder="e.g. Accountant">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city"
                                            value="{{ old('city', $staffMember?->city) }}" placeholder="Enter city">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state"
                                            value="{{ old('state', $staffMember?->state) }}" placeholder="Enter state">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" class="form-control" name="pincode"
                                            value="{{ old('pincode', $staffMember?->pincode) }}" placeholder="Enter pincode">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Password {{ $isEdit ? '' : '*' }}</label>
                                        <input type="password" class="form-control" name="password"
                                            placeholder="{{ $isEdit ? 'Leave blank to keep' : 'Min. 8 characters' }}"
                                            {{ $isEdit ? '' : 'required' }}>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Confirm Password {{ $isEdit ? '' : '*' }}</label>
                                        <input type="password" class="form-control" name="password_confirmation"
                                            placeholder="Repeat password" {{ $isEdit ? '' : 'required' }}>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status <span class="req">*</span></label>
                                        <select class="form-select" name="status" required data-no-search>
                                            <option value="active" @selected(old('status', $staffMember?->status ?? 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected(old('status', $staffMember?->status) === 'inactive')>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Login Time</label>
                                        <input type="time" class="form-control" name="expected_login_time"
                                            value="{{ old('expected_login_time', $staffMember?->expected_login_time ? substr((string) $staffMember->expected_login_time, 0, 5) : '09:00') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Grace Time (Minutes)</label>
                                        <input type="number" class="form-control" name="grace_minutes" min="0" max="120"
                                            value="{{ old('grace_minutes', $staffMember?->grace_minutes ?? 10) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" name="avatar" accept="image/*">
                                        <div class="form-text">JPG/PNG, max 2 MB.</div>
                                        @if ($isEdit && $staffMember?->user?->avatar_path)
                                            <div class="mt-2 d-flex align-items-center gap-2">
                                                <img src="{{ asset('storage/'.$staffMember->user->avatar_path) }}"
                                                    alt="Current photo"
                                                    class="rounded"
                                                    style="width: 48px; height: 48px; object-fit: cover;">
                                                <span class="text-muted small">Current image (upload to replace)</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"
                                            placeholder="Enter full address">{{ old('address', $staffMember?->address) }}</textarea>
                                    </div>
                                </div>

                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> {{ $isEdit ? 'Update' : 'Save' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('staffForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            try {
                const slug = this.querySelector('[name="role_id"]')?.selectedOptions?.[0]?.dataset.slug || '';
                const known = ['branch_manager', 'teacher', 'principal', 'receptionist', 'accountant'];
                const redirectUrl = known.includes(slug)
                    ? @json(route('admin.users')) + '?role=' + encodeURIComponent(slug)
                    : @json(route('admin.users'));
                await AdminAjax.submitForm(this, { redirectUrl });
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        });
    </script>
@endpush
