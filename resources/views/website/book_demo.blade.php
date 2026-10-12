@extends('website.layouts.app')

@section('content')
    <main class="demo-page">
        <div class="container">
            <div class="demo-layout">
                <aside class="demo-pitch reveal" data-reveal>
                    <p class="eyebrow">Book a free demo</p>
                    <h1>See IVClasses live on your institute workflow</h1>
                    <p class="sub">
                        In 20–30 minutes we’ll walk through admissions, fees, attendance, exams and the parent app —
                        so you can decide with clarity.
                    </p>
                    <ul class="trust-bullets">
                        <li>Web CRM for owners &amp; office staff</li>
                        <li>Mobile app for teachers, staff &amp; parents</li>
                        <li>Google Meet link + calendar invite after booking</li>
                    </ul>
                    <div class="demo-pitch-note">
                        <strong>Learn · Grow · Succeed</strong>
                        <span>Built for coaching classes and multi-branch institutes.</span>
                    </div>
                </aside>

                <div class="demo-form-wrap reveal" data-reveal data-delay="80">
                    <div class="demo-steps" aria-label="Form steps">
                        <div class="demo-step is-active" data-step-indicator="1">
                            <span>1</span>
                            <em>Contact</em>
                        </div>
                        <div class="demo-step" data-step-indicator="2">
                            <span>2</span>
                            <em>Institute</em>
                        </div>
                        <div class="demo-step" data-step-indicator="3">
                            <span>3</span>
                            <em>Schedule</em>
                        </div>
                    </div>

                    <form id="demoForm" class="demo-form" method="POST" action="{{ route('book-demo.store') }}"
                        data-success-url="{{ route('book-demo.success') }}" novalidate>
                        @csrf
                        <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />

                        {{-- STEP 1 --}}
                        <div class="demo-panel is-active" data-step-panel="1">
                            <h2>Your contact details</h2>
                            <p class="demo-panel-sub">We’ll use this to send the Meet link and confirmation.</p>

                            <div class="form-row">
                                <div class="field">
                                    <label for="fullName">Full Name *</label>
                                    <input type="text" id="fullName" name="contact_person_name"
                                        value="{{ old('contact_person_name') }}" required autocomplete="name"
                                        placeholder="Your name" />
                                    <span class="error" data-error="contact_person_name">{{ $errors->first('contact_person_name') }}</span>
                                </div>
                                <div class="field">
                                    <label for="institute">Institute / Class Name *</label>
                                    <input type="text" id="institute" name="institute_name"
                                        value="{{ old('institute_name') }}" required placeholder="Institute name" />
                                    <span class="error" data-error="institute_name">{{ $errors->first('institute_name') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field">
                                    <label for="state">State</label>
                                    <input type="text" id="state" name="state" value="{{ old('state') }}"
                                        autocomplete="address-level1" placeholder="State" />
                                    <span class="error" data-error="state">{{ $errors->first('state') }}</span>
                                </div>
                                <div class="field">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" value="{{ old('city') }}" required
                                        autocomplete="address-level2" placeholder="City" />
                                    <span class="error" data-error="city">{{ $errors->first('city') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field">
                                    <label for="phone">Phone / WhatsApp *</label>
                                    <input type="tel" id="phone" name="contact_no" value="{{ old('contact_no') }}"
                                        required autocomplete="tel" maxlength="15" placeholder="+91" />
                                    <span class="error" data-error="contact_no">{{ $errors->first('contact_no') }}</span>
                                </div>
                                <div class="field">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                        autocomplete="email" placeholder="you@email.com" />
                                    <span class="error" data-error="email">{{ $errors->first('email') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field">
                                    <label for="pincode">Pincode</label>
                                    <input type="text" id="pincode" name="pincode" value="{{ old('pincode') }}"
                                        autocomplete="postal-code" placeholder="Pincode" />
                                    <span class="error" data-error="pincode">{{ $errors->first('pincode') }}</span>
                                </div>
                                <div class="field">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" value="{{ old('address') }}"
                                        placeholder="Institute address" />
                                    <span class="error" data-error="address">{{ $errors->first('address') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 2 --}}
                        <div class="demo-panel" data-step-panel="2" hidden>
                            <h2>About your institute</h2>
                            <p class="demo-panel-sub">Helps us tailor the demo to your setup.</p>

                            <div class="form-row">
                                <div class="field">
                                    <label for="branches">Number of Branches</label>
                                    <select id="branches" name="branch_count">
                                        <option value="">Select</option>
                                        <option value="1" @selected(old('branch_count') == '1')>1</option>
                                        <option value="5" @selected(old('branch_count') == '5')>2–5</option>
                                        <option value="10" @selected(old('branch_count') == '10')>5+</option>
                                    </select>
                                    <span class="error" data-error="branch_count">{{ $errors->first('branch_count') }}</span>
                                </div>
                                <div class="field">
                                    <label for="students">Approx Students</label>
                                    <select id="students" name="student_count">
                                        <option value="">Select</option>
                                        <option value="100" @selected(old('student_count') == '100')>0–100</option>
                                        <option value="300" @selected(old('student_count') == '300')>100–300</option>
                                        <option value="500" @selected(old('student_count') == '500')>300+</option>
                                    </select>
                                    <span class="error" data-error="student_count">{{ $errors->first('student_count') }}</span>
                                </div>
                            </div>

                            <div class="field">
                                <label for="interest">Interested in</label>
                                <select id="interest" name="requirement">
                                    <option value="">Select</option>
                                    <option value="Web CRM" @selected(old('requirement') == 'Web CRM')>Web CRM</option>
                                    <option value="Mobile App" @selected(old('requirement') == 'Mobile App')>Mobile App</option>
                                    <option value="Both" @selected(old('requirement') == 'Both')>Both</option>
                                </select>
                                <span class="error" data-error="requirement">{{ $errors->first('requirement') }}</span>
                            </div>

                            <div class="field">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="4"
                                    placeholder="Tell us about your institute (optional)">{{ old('message') }}</textarea>
                                <span class="error" data-error="message">{{ $errors->first('message') }}</span>
                            </div>
                        </div>

                        {{-- STEP 3 --}}
                        <div class="demo-panel" data-step-panel="3" hidden>
                            <h2>Pick a demo slot</h2>
                            <p class="demo-panel-sub">Choose a date — only open times appear as badges below.</p>

                            <div class="demo-calendar" id="demoCalendar" aria-label="Demo date calendar"></div>
                            <input type="hidden" id="demoDate" name="demo_date" value="{{ old('demo_date') }}" required />
                            <span class="error" data-error="demo_date">{{ $errors->first('demo_date') }}</span>

                            <div class="demo-slots-head">
                                <strong id="demoSlotsLabel">Available times</strong>
                                <span id="demoSlotsHint">Select a date first</span>
                            </div>
                            <div class="demo-slots" id="demoSlots"></div>
                            <input type="hidden" id="demoTime" name="demo_start_time" value="{{ old('demo_start_time') }}" required />
                            <input type="hidden" id="demoEndTime" name="demo_end_time" value="{{ old('demo_end_time') }}" />
                            <span class="error" data-error="demo_start_time" id="demoTimeBookedMsg">{{ $errors->first('demo_start_time') }}</span>
                            <p class="error" data-error="demo">{{ $errors->first('demo') }}</p>
                        </div>

                        <div class="demo-nav">
                            <button type="button" class="btn btn-ghost is-hidden" id="demoPrevBtn">Previous</button>
                            <button type="button" class="btn btn-primary" id="demoNextBtn">Next</button>
                            <button type="submit" class="btn btn-primary is-hidden" id="demoSubmitBtn">Request Demo</button>
                        </div>

                        <p class="demo-form-note">
                            By submitting, you agree we may contact you about this demo.
                            See our <a href="{{ route('privacy') }}">Privacy Policy</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div class="demo-loader" id="demoLoader" hidden aria-live="polite" aria-busy="false">
        <div class="demo-loader-chip">
            <span class="demo-loader-book-mini" aria-hidden="true">
                <i></i><i></i>
            </span>
            <span>Booking…</span>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('demoForm');
            if (!form) return;

            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const indicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
            const prevBtn = document.getElementById('demoPrevBtn');
            const nextBtn = document.getElementById('demoNextBtn');
            const submitBtn = document.getElementById('demoSubmitBtn');
            const demoDate = document.getElementById('demoDate');
            const demoTime = document.getElementById('demoTime');
            const demoEndTime = document.getElementById('demoEndTime');
            const calendarEl = document.getElementById('demoCalendar');
            const slotsEl = document.getElementById('demoSlots');
            const slotsHint = document.getElementById('demoSlotsHint');
            const slotsLabel = document.getElementById('demoSlotsLabel');
            const slotsUrl = @json(route('book-demo.available-slots'));
            let step = 1;
            let calYear;
            let calMonth;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            function showStep(n) {
                step = n;
                panels.forEach((panel) => {
                    const active = Number(panel.dataset.stepPanel) === step;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });
                indicators.forEach((item) => {
                    const i = Number(item.dataset.stepIndicator);
                    item.classList.toggle('is-active', i === step);
                    item.classList.toggle('is-done', i < step);
                });
                prevBtn.classList.toggle('is-hidden', step === 1);
                nextBtn.classList.toggle('is-hidden', step === 3);
                submitBtn.classList.toggle('is-hidden', step !== 3);
            }

            function fieldValue(name) {
                const el = form.elements.namedItem(name);
                return el && 'value' in el ? String(el.value).trim() : '';
            }

            function clearStepErrors() {
                form.querySelectorAll('.field').forEach((f) => f.classList.remove('has-error'));
                form.querySelectorAll('.error').forEach((e) => {
                    if (!e.dataset.keep) e.textContent = '';
                });
            }

            function setError(name, message) {
                const err = form.querySelector('[data-error="' + name + '"]');
                if (err) err.textContent = message;
                const input = form.elements.namedItem(name);
                if (input && 'closest' in input) {
                    const field = input.closest('.field');
                    if (field) field.classList.add('has-error');
                }
            }

            function validateStep(n) {
                clearStepErrors();
                let ok = true;
                if (n === 1) {
                    if (!fieldValue('contact_person_name')) {
                        setError('contact_person_name', 'Full name is required');
                        ok = false;
                    }
                    if (!fieldValue('institute_name')) {
                        setError('institute_name', 'Institute name is required');
                        ok = false;
                    }
                    if (!fieldValue('city')) {
                        setError('city', 'City is required');
                        ok = false;
                    }
                    const phone = fieldValue('contact_no');
                    if (!phone) {
                        setError('contact_no', 'Phone / WhatsApp is required');
                        ok = false;
                    } else if (phone.replace(/\D/g, '').length < 10) {
                        setError('contact_no', 'Enter a valid phone number');
                        ok = false;
                    }
                    const email = fieldValue('email');
                    if (!email) {
                        setError('email', 'Email is required');
                        ok = false;
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        setError('email', 'Enter a valid email');
                        ok = false;
                    }
                }
                if (n === 3) {
                    if (!fieldValue('demo_date')) {
                        setError('demo_date', 'Please select a demo date');
                        ok = false;
                    }
                    if (!fieldValue('demo_start_time')) {
                        setError('demo_start_time', 'Please select an available time');
                        ok = false;
                    }
                }
                return ok;
            }

            nextBtn.addEventListener('click', function() {
                if (!validateStep(step)) return;
                showStep(Math.min(3, step + 1));
            });
            prevBtn.addEventListener('click', function() {
                showStep(Math.max(1, step - 1));
            });

            function formatTime(time) {
                const [hours, minutes] = time.split(':');
                const date = new Date();
                date.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0, 0);
                return date.toLocaleTimeString('en-IN', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function ymd(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + day;
            }

            function renderCalendar() {
                if (!calendarEl) return;
                const first = new Date(calYear, calMonth, 1);
                const startPad = (first.getDay() + 6) % 7;
                const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
                const monthLabel = first.toLocaleDateString('en-IN', {
                    month: 'long',
                    year: 'numeric'
                });
                const selected = demoDate.value;

                let html = '<div class="demo-cal-head">' +
                    '<button type="button" class="demo-cal-nav" data-cal="prev" aria-label="Previous month">‹</button>' +
                    '<strong>' + monthLabel + '</strong>' +
                    '<button type="button" class="demo-cal-nav" data-cal="next" aria-label="Next month">›</button>' +
                    '</div>';
                html += '<div class="demo-cal-weekdays"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>';
                html += '<div class="demo-cal-grid">';

                for (let i = 0; i < startPad; i++) {
                    html += '<span class="demo-cal-day is-empty"></span>';
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const dateObj = new Date(calYear, calMonth, day);
                    const key = ymd(dateObj);
                    const disabled = dateObj < today;
                    const isSelected = selected === key;
                    const isToday = ymd(today) === key;
                    html += '<button type="button" class="demo-cal-day' +
                        (disabled ? ' is-disabled' : '') +
                        (isSelected ? ' is-selected' : '') +
                        (isToday ? ' is-today' : '') +
                        '" data-date="' + key + '"' +
                        (disabled ? ' disabled' : '') +
                        '>' + day + '</button>';
                }
                html += '</div>';
                calendarEl.innerHTML = html;
            }

            let slotsAbort = null;

            function loadSlots(date) {
                if (slotsAbort) slotsAbort.abort();
                slotsAbort = new AbortController();

                slotsEl.innerHTML = '';
                demoTime.value = '';
                if (demoEndTime) demoEndTime.value = '';
                slotsHint.textContent = 'Loading open times...';
                slotsLabel.textContent = 'Available times';

                fetch(slotsUrl + '?date=' + encodeURIComponent(date), {
                        method: 'GET',
                        credentials: 'same-origin',
                        signal: slotsAbort.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (demoDate.value !== date) return;
                        slotsEl.innerHTML = '';
                        if (!data.success || !data.slots) {
                            slotsHint.textContent = 'Unable to load slots';
                            return;
                        }

                        const openSlots = data.slots.filter(function(slot) {
                            return !slot.booked;
                        });

                        if (!openSlots.length) {
                            slotsHint.textContent = 'No open times on this date';
                            return;
                        }

                        slotsHint.textContent = openSlots.length + ' open slot' + (openSlots.length > 1 ? 's' : '');
                        openSlots.forEach(function(slot) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'demo-slot-badge';
                            btn.textContent = formatTime(slot.start_time);
                            btn.dataset.start = slot.start_time;
                            btn.dataset.end = slot.end_time;
                            btn.addEventListener('click', function() {
                                slotsEl.querySelectorAll('.demo-slot-badge').forEach(function(el) {
                                    el.classList.remove('is-selected');
                                });
                                btn.classList.add('is-selected');
                                demoTime.value = slot.start_time;
                                if (demoEndTime) demoEndTime.value = slot.end_time;
                                const err = form.querySelector('[data-error="demo_start_time"]');
                                if (err) err.textContent = '';
                            });
                            slotsEl.appendChild(btn);
                        });
                    })
                    .catch(function(err) {
                        if (err && err.name === 'AbortError') return;
                        if (demoDate.value !== date) return;
                        slotsHint.textContent = 'Unable to load slots';
                    });
            }

            calendarEl.addEventListener('click', function(e) {
                const nav = e.target.closest('[data-cal]');
                if (nav) {
                    if (nav.dataset.cal === 'prev') {
                        calMonth -= 1;
                        if (calMonth < 0) {
                            calMonth = 11;
                            calYear -= 1;
                        }
                    } else {
                        calMonth += 1;
                        if (calMonth > 11) {
                            calMonth = 0;
                            calYear += 1;
                        }
                    }
                    renderCalendar();
                    return;
                }

                const dayBtn = e.target.closest('.demo-cal-day[data-date]');
                if (!dayBtn || dayBtn.disabled) return;
                demoDate.value = dayBtn.dataset.date;
                renderCalendar();
                loadSlots(demoDate.value);
            });

            if (demoDate.value) {
                const parts = demoDate.value.split('-');
                calYear = Number(parts[0]);
                calMonth = Number(parts[1]) - 1;
                renderCalendar();
                loadSlots(demoDate.value);
            } else {
                calYear = today.getFullYear();
                calMonth = today.getMonth();
                renderCalendar();
            }

            showStep(1);
        });
    </script>
@endpush
