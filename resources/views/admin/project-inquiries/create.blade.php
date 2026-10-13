@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-plus-circle"></i>
                    Add Project Inquiry
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.project-inquiries.index') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Project Inquiry</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">Add Inquiry
                        </li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-4 mb-0" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.project-inquiries.store') }}" class="mt-4" id="adminDemoForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Full Name *</label>
                            <input type="text" name="contact_person_name" class="form-control"
                                value="{{ old('contact_person_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Institute / Class Name
                                *</label>
                            <input type="text" name="institute_name" class="form-control"
                                value="{{ old('institute_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">City *</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Phone / WhatsApp *</label>
                            <input type="text" name="contact_no" class="form-control" value="{{ old('contact_no') }}"
                                maxlength="15" inputmode="numeric" data-phone-input required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Email *</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Branches</label>
                            <select name="branch_count" class="form-select">
                                <option value="">Select</option>
                                <option value="1" @selected(old('branch_count') == '1')>1</option>
                                <option value="5" @selected(old('branch_count') == '5')>2–5</option>
                                <option value="10" @selected(old('branch_count') == '10')>5+</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Students</label>
                            <select name="student_count" class="form-select">
                                <option value="">Select</option>
                                <option value="100" @selected(old('student_count') == '100')>0–100</option>
                                <option value="300" @selected(old('student_count') == '300')>100–300</option>
                                <option value="500" @selected(old('student_count') == '500')>300+</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Requirement</label>
                            <select name="requirement" class="form-select">
                                <option value="">Select</option>
                                <option value="Web CRM" @selected(old('requirement') == 'Web CRM')>Web CRM</option>
                                <option value="Mobile App" @selected(old('requirement') == 'Mobile App')>Mobile App</option>
                                <option value="Both" @selected(old('requirement') == 'Both')>Both</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Message</label>
                            <textarea name="message" rows="3" class="form-control">{{ old('message') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Demo Date *</label>
                            <input type="date" name="demo_date" id="demoDate" class="form-control"
                                value="{{ old('demo_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Demo Time *</label>
                            <select name="demo_start_time" id="demoTime" class="form-select" required disabled>
                                <option value="">Select date first</option>
                            </select>
                            <div class="text-danger small mt-1" id="demoTimeBookedMsg">{{ $errors->first('demo_start_time') }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.project-inquiries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn" style="background: var(--iv-primary); color: #fff;">
                            Book Demo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const demoDate = document.getElementById('demoDate');
            const demoTime = document.getElementById('demoTime');
            const demoTimeBookedMsg = document.getElementById('demoTimeBookedMsg');
            const bookedMessage = 'This time is already booked for a demo.';
            const form = document.getElementById('adminDemoForm');

            function setBookedMessage(show) {
                if (demoTimeBookedMsg) {
                    demoTimeBookedMsg.textContent = show ? bookedMessage : '';
                }
            }

            function formatTime(time) {
                const [hours, minutes] = time.split(':');
                const date = new Date();
                date.setHours(parseInt(hours), parseInt(minutes), 0, 0);
                return date.toLocaleTimeString('en-IN', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            demoDate.addEventListener('change', function() {
                const date = this.value;
                demoTime.innerHTML = '';
                demoTime.disabled = true;
                setBookedMessage(false);

                if (!date) {
                    demoTime.innerHTML = '<option value="">Select date first</option>';
                    return;
                }

                demoTime.innerHTML = '<option value="">Loading times...</option>';

                fetch("{{ route('book-demo.available-slots') }}?date=" + encodeURIComponent(date), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        demoTime.innerHTML = '';
                        if (!data.success || !data.slots || data.slots.length === 0) {
                            demoTime.innerHTML = '<option value="">No slots available</option>';
                            return;
                        }

                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'Select time';
                        demoTime.appendChild(defaultOption);

                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.start_time;
                            option.textContent = formatTime(slot.start_time) + ' - ' + formatTime(slot
                                .end_time) + (slot.booked ? ' (Booked)' : '');
                            option.dataset.booked = slot.booked ? '1' : '0';
                            demoTime.appendChild(option);
                        });

                        demoTime.disabled = false;
                    })
                    .catch(() => {
                        demoTime.innerHTML = '<option value="">Unable to load slots</option>';
                    });
            });

            demoTime.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                setBookedMessage(selectedOption?.dataset?.booked === '1');
            });

            form.addEventListener('submit', function(e) {
                const selectedOption = demoTime.options[demoTime.selectedIndex];
                if (selectedOption && selectedOption.dataset.booked === '1') {
                    e.preventDefault();
                    setBookedMessage(true);
                }
            });
        });
    </script>
@endpush
