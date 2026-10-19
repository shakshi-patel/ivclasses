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
                                maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input required>
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
                            <select name="demo_start_time" id="demoTime" class="form-select no-search-select"
                                data-no-search required disabled>
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
            const slotsUrl = @json(route('book-demo.available-slots'));
            let slotsAbort = null;

            function setBookedMessage(show) {
                if (demoTimeBookedMsg) {
                    demoTimeBookedMsg.textContent = show ? bookedMessage : '';
                }
            }

            function formatTime(time) {
                if (!time) return '';
                const parts = String(time).split(':');
                const hours = parseInt(parts[0], 10);
                const minutes = parseInt(parts[1] || '0', 10);
                if (Number.isNaN(hours)) return String(time);
                const date = new Date();
                date.setHours(hours, minutes, 0, 0);
                return date.toLocaleTimeString('en-IN', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            /** Select2 wraps .form-select globally — tear it down so option rebuilds are visible. */
            function teardownSelect2() {
                if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
                const $el = jQuery(demoTime);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                const $wrap = $el.parent();
                if ($wrap.hasClass('admin-select-wrap')) {
                    $el.unwrap();
                }
            }

            function setTimeOptions(html, enable) {
                teardownSelect2();
                demoTime.innerHTML = html;
                demoTime.disabled = !enable;
                demoTime.value = '';
            }

            function loadSlots(date) {
                if (slotsAbort) slotsAbort.abort();
                setBookedMessage(false);

                if (!date) {
                    setTimeOptions('<option value="">Select date first</option>', false);
                    return;
                }

                setTimeOptions('<option value="">Loading times...</option>', false);
                slotsAbort = new AbortController();

                fetch(slotsUrl + '?date=' + encodeURIComponent(date), {
                        method: 'GET',
                        credentials: 'same-origin',
                        signal: slotsAbort.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (demoDate.value !== date) return;

                        const slots = Array.isArray(data?.slots) ? data.slots : [];
                        if (!data?.success || !slots.length) {
                            setTimeOptions('<option value="">No slots available</option>', false);
                            return;
                        }

                        teardownSelect2();
                        demoTime.innerHTML = '';
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'Select time';
                        demoTime.appendChild(defaultOption);

                        slots.forEach(function(slot) {
                            const start = slot.start_time || '';
                            const end = slot.end_time || '';
                            const booked = !!(slot.booked || slot.available === false);
                            const option = document.createElement('option');
                            option.value = start;
                            option.textContent = formatTime(start) + (end ? ' - ' + formatTime(end) : '') +
                                (booked ? ' (Booked)' : '');
                            option.dataset.booked = booked ? '1' : '0';
                            demoTime.appendChild(option);
                        });

                        demoTime.disabled = false;
                        demoTime.value = '';
                    })
                    .catch(function(err) {
                        if (err && err.name === 'AbortError') return;
                        if (demoDate.value !== date) return;
                        setTimeOptions('<option value="">Unable to load slots</option>', false);
                    });
            }

            demoDate.addEventListener('change', function() {
                loadSlots(this.value);
            });
            demoDate.addEventListener('input', function() {
                loadSlots(this.value);
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

            teardownSelect2();
            if (demoDate.value) {
                loadSlots(demoDate.value);
            }
        });
    </script>
@endpush
