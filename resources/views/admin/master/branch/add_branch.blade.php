@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($branch) && $branch;
        $manager = $branch?->manager;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'plus-circle' }}"></i>
                    {{ $isEdit ? 'Edit Branch' : 'Add Branch' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.master.branch') }}">Branch</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $isEdit ? 'Edit Branch' : 'Add Branch' }}
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

                <div class="admin-detail-block mt-4">
                    <form method="POST"
                        action="{{ $isEdit ? route('admin.master.branch.update', $branch) : route('admin.master.branch.store') }}"
                        data-institute-cascade
                        data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
                        id="branchForm">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            @include('admin.partials.form_institute_field', [
                                'instituteId' => old('institute_id', $branch?->institute_id),
                                'institutes' => $institutes,
                                'label' => 'Classes / Institute',
                            ])

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Branch Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="name"
                                    value="{{ old('name', $branch?->name) }}" placeholder="Branch name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Branch Code</label>
                                <input type="text" class="form-control" name="code"
                                    value="{{ old('code', $branch?->code) }}" placeholder="e.g. MEH-01">
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                                <h6 class="mb-1" style="color: var(--iv-primary);">Branch Manager login</h6>
                                <p class="text-muted small mb-0">
                                    This account signs in only via <strong>Branch portal</strong>
                                    (<code>/branch/login</code> — separate from Principal.
                                </p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Manager name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="manager_name"
                                    value="{{ old('manager_name', $manager?->name ?? $branch?->contact_person) }}"
                                    placeholder="Branch manager full name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Manager email (login) <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" name="manager_email"
                                    value="{{ old('manager_email', $manager?->email ?? $branch?->email) }}"
                                    placeholder="manager@email.com" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Password {{ $isEdit ? '' : '*' }}
                                </label>
                                <input type="password" class="form-control" name="manager_password"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep' : 'Min. 8 characters' }}"
                                    {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Confirm password {{ $isEdit ? '' : '*' }}
                                </label>
                                <input type="password" class="form-control" name="manager_password_confirmation"
                                    placeholder="Repeat password" {{ $isEdit ? '' : 'required' }}
                                    autocomplete="new-password">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Mobile Number</label>
                                <input type="text" class="form-control" name="phone" maxlength="15" inputmode="numeric"
                                    value="{{ old('phone', $manager?->phone ?? $branch?->phone) }}" placeholder="10-digit mobile">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">City</label>
                                <input type="text" class="form-control" name="city"
                                    value="{{ old('city', $branch?->city) }}" placeholder="City">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">State</label>
                                <input type="text" class="form-control" name="state"
                                    value="{{ old('state', $branch?->state) }}" placeholder="State">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Pincode</label>
                                <input type="text" class="form-control" name="pincode"
                                    value="{{ old('pincode', $branch?->pincode) }}" placeholder="Pincode">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="status" required data-no-search>
                                    <option value="active" @selected(old('status', $branch?->status ?? 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $branch?->status) === 'inactive')>Inactive</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Full Address</label>
                                <textarea class="form-control" name="address" rows="3"
                                    placeholder="Full branch address">{{ old('address', $branch?->address) }}</textarea>
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.master.branch') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn admin-btn-primary">
                                    {{ $isEdit ? 'Update Branch' : 'Save Branch' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('branchForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            try {
                await AdminAjax.submitForm(this, {
                    redirectUrl: @json(route('admin.master.branch'))
                });
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        });
    </script>
@endpush
