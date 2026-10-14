@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($inquiry) && $inquiry;
        $user = auth()->user();
        $isPlatform = $user?->isPlatformUser();
        $latestFollowUp = $isEdit ? $inquiry->followUps->first() : null;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'user-plus' }}"></i>
                    {{ $isEdit ? 'Edit Inquiry' : 'Add Inquiry' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.inquiry') }}">Inquiry</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.inquiry') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Inquiry' : 'Add Inquiry' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.inquiry.update', $inquiry) : route('admin.inquiry.store') }}"
                                enctype="multipart/form-data"
                                data-institute-cascade
                                data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
                                id="inquiryForm">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif

                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', [
                                        'instituteId' => old('institute_id', $inquiry?->institute_id),
                                        'institutes' => $institutes,
                                    ])
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => old('branch_id', $inquiry?->branch_id),
                                        'branches' => $branches,
                                        'required' => false,
                                    ])
                                    <div class="col-md-6">
                                        <label class="form-label">Student Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="student_name" required
                                            value="{{ old('student_name', $inquiry?->student_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="dob"
                                            value="{{ old('dob', optional($inquiry?->dob)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gender <span class="req">*</span></label>
                                        <select class="form-select" name="gender" required data-no-search>
                                            @foreach (['Male', 'Female', 'Other'] as $g)
                                                <option value="{{ $g }}" @selected(old('gender', $inquiry?->gender) === $g)>{{ $g }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Standard <span class="req">*</span></label>
                                        <select class="form-select" name="standard_id" required data-cascade-target="standards"
                                            @if (old('standard_id', $inquiry?->standard_id)) data-keep-value="{{ old('standard_id', $inquiry?->standard_id) }}" @endif>
                                            <option value="">Select standard</option>
                                            @foreach ($standards as $std)
                                                <option value="{{ $std->id }}" @selected(old('standard_id', $inquiry?->standard_id) == $std->id)>{{ $std->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Inquiry Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="inquiry_date" required
                                            value="{{ old('inquiry_date', optional($inquiry?->inquiry_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category <span class="req">*</span></label>
                                        <select class="form-select" name="category" required data-no-search>
                                            @foreach (['General', 'OBC', 'SC', 'ST'] as $cat)
                                                <option value="{{ $cat }}" @selected(old('category', $inquiry?->category) === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Father Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="father_name" required
                                            value="{{ old('father_name', $inquiry?->father_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mother Name</label>
                                        <input type="text" class="form-control" name="mother_name"
                                            value="{{ old('mother_name', $inquiry?->mother_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Father Email <span class="req">*</span></label>
                                        <input type="email" class="form-control" name="father_email" required
                                            value="{{ old('father_email', $inquiry?->email) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact 1 <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="father_phone" required
                                            maxlength="15" inputmode="numeric" data-phone-input
                                            value="{{ old('father_phone', $inquiry?->phone) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact 2</label>
                                        <input type="text" class="form-control" name="father_phone_2"
                                            maxlength="15" inputmode="numeric" data-phone-input
                                            value="{{ old('father_phone_2', $inquiry?->phone_2) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Follow Up Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="follow_up_date" required
                                            value="{{ old('follow_up_date', optional($inquiry?->next_follow_up_at)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Follow Up Remarks <span class="req">*</span></label>
                                        <textarea class="form-control" name="follow_up_remark" rows="3" required>{{ old('follow_up_remark') }}</textarea>
                                        @if ($isEdit)
                                            <div class="form-text">Add a note for this update — it's saved to the follow-up history.</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Form Fees</label>
                                        <input type="number" class="form-control" name="form_fees" min="0" step="0.01"
                                            value="{{ old('form_fees', $inquiry?->form_fees ?? 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-select" name="form_fees_payment_method" data-no-search>
                                            @foreach (['Cash', 'Online', 'UPI', 'Cheque'] as $pm)
                                                <option value="{{ $pm }}" @selected(old('form_fees_payment_method', $inquiry?->form_fees_payment_method ?? 'Cash') === $pm)>{{ $pm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2">{{ old('address', $inquiry?->address) }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Student Photo</label>
                                        <input type="file" class="form-control" name="photo" accept="image/*">
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
        document.getElementById('inquiryForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            try {
                await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.inquiry')) });
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        });
    </script>
@endpush
