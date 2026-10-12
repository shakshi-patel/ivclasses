@extends('website.layouts.app')

@section('content')
    <main class="demo-page">
        <div class="container">
            <div class="demo-layout">
                <aside class="demo-pitch reveal" data-reveal>
                    <p class="eyebrow">Verify email</p>
                    <h1>One quick step left</h1>
                    <p class="sub">
                        Enter the 6-digit OTP to activate your institute trial account.
                        After verification you can sign in to the Web CRM.
                    </p>
                    <ul class="trust-bullets">
                        <li>Code is valid for 15 minutes</li>
                        <li>Sent to your registered email address</li>
                        <li>Keeps your institute login secure</li>
                    </ul>
                </aside>

                <div class="demo-form-wrap reveal" data-reveal data-delay="80">
                    <div class="demo-form-head">
                        <h2>Enter OTP</h2>
                        <p>
                            We've emailed a 6-digit code to
                            <strong>{{ $email }}</strong> — check your inbox (and spam folder).
                        </p>
                    </div>

                    @if (session('success'))
                        <div class="trial-alert" role="status" style="background: rgba(var(--iv-primary-rgb), 0.08); border: 1px solid rgba(var(--iv-primary-rgb), 0.2); color: var(--iv-primary-dark); margin-bottom: 16px; padding: 12px 14px; border-radius: 12px;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="trial-alert trial-alert-error" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register-institute.verify.store') }}" class="demo-form otp-form" novalidate>
                        @csrf

                        <div class="field {{ $errors->has('otp') ? 'has-error' : '' }}">
                            <label for="regOtp">6-digit OTP *</label>
                            <input type="text" id="regOtp" name="otp" inputmode="numeric" pattern="[0-9]*"
                                maxlength="6" autocomplete="one-time-code" required
                                placeholder="••••••" value="{{ old('otp') }}"
                                class="otp-input" />
                            <span class="error">{{ $errors->first('otp') }}</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Verify &amp; activate account
                        </button>
                    </form>

                    <form method="POST" action="{{ route('register-institute.verify.resend') }}" class="otp-resend-form" id="otpResendForm">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-block" id="otpResendBtn" disabled>
                            Resend OTP in 30s
                        </button>
                    </form>

                    <p class="demo-form-note">
                        Wrong email?
                        <a href="{{ route('register-institute') }}">Register again</a>
                        · Already verified?
                        <a href="{{ route('login.institute') }}">Institute sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('otpResendBtn');
            const form = document.getElementById('otpResendForm');
            if (!btn || !form) return;

            const COOLDOWN = 30;
            let remaining = COOLDOWN;
            let timer = null;

            function setLabel(seconds) {
                if (seconds > 0) {
                    btn.disabled = true;
                    btn.textContent = 'Resend OTP in ' + seconds + 's';
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Resend OTP';
                }
            }

            function startCooldown() {
                remaining = COOLDOWN;
                setLabel(remaining);
                if (timer) clearInterval(timer);
                timer = setInterval(function() {
                    remaining -= 1;
                    setLabel(remaining);
                    if (remaining <= 0) {
                        clearInterval(timer);
                        timer = null;
                    }
                }, 1000);
            }

            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.textContent = 'Sending…';
            });

            startCooldown();
        })();
    </script>
@endpush
