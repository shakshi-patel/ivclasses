@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-cog"></i>
                    Settings
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Settings
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

                @if ($errors->any())
                    <div class="alert alert-danger mt-4 mb-0" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <ul class="nav nav-pills gap-2 mt-4 mb-3 admin-settings-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'email' ? 'active' : '' }}"
                            href="{{ route('admin.settings.index', ['tab' => 'email']) }}">
                            <i class="bx bx-envelope me-1"></i> Email (SMTP)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'google' ? 'active' : '' }}"
                            href="{{ route('admin.settings.index', ['tab' => 'google']) }}">
                            <i class="bx bxl-google me-1"></i> Google
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'payment' ? 'active' : '' }}"
                            href="{{ route('admin.settings.index', ['tab' => 'payment']) }}">
                            <i class="bx bx-credit-card me-1"></i> Payment Method
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'website' ? 'active' : '' }}"
                            href="{{ route('admin.settings.index', ['tab' => 'website']) }}">
                            <i class="bx bx-globe me-1"></i> Website Settings
                        </a>
                    </li>
                </ul>

                @if ($tab === 'email')
                    <form method="POST" action="{{ route('admin.settings.email.update') }}">
                        @csrf
                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-envelope"></i> SMTP mail
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <p class="text-muted mb-3" style="font-size: 0.88rem;">
                                    Saved encrypted in admin settings (not in .env). Leave password blank to keep the current value.
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Host</label>
                                        <input type="text" name="mail_host" class="form-control"
                                            value="{{ old('mail_host', $settings->get('mail.host')) }}"
                                            placeholder="smtp.gmail.com">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Port</label>
                                        <input type="number" name="mail_port" class="form-control"
                                            value="{{ old('mail_port', $settings->get('mail.port')) }}"
                                            placeholder="587">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Encryption</label>
                                        <select name="mail_encryption" class="form-select">
                                            @php $enc = old('mail_encryption', $settings->get('mail.encryption', 'tls')); @endphp
                                            <option value="tls" @selected($enc === 'tls')>TLS</option>
                                            <option value="ssl" @selected($enc === 'ssl')>SSL</option>
                                            <option value="" @selected($enc === null || $enc === '')>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Username</label>
                                        <input type="text" name="mail_username" class="form-control"
                                            value="{{ old('mail_username', $settings->get('mail.username')) }}"
                                            autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Password</label>
                                        <input type="password" name="mail_password" class="form-control" value=""
                                            placeholder="{{ $settings->secretIsSet('mail.password') ? '•••••••• (saved — leave blank to keep)' : 'App password / SMTP password' }}"
                                            autocomplete="new-password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">From address</label>
                                        <input type="email" name="mail_from_address" class="form-control"
                                            value="{{ old('mail_from_address', $settings->get('mail.from_address')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">From name</label>
                                        <input type="text" name="mail_from_name" class="form-control"
                                            value="{{ old('mail_from_name', $settings->get('mail.from_name')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Admin notification email</label>
                                        <input type="email" name="demo_notification_email" class="form-control"
                                            value="{{ old('demo_notification_email', $settings->get('demo.notification_email')) }}"
                                            placeholder="Where demo Confirm / Decline emails go">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn px-4 admin-btn-primary">
                                Save email settings
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.settings.email.test') }}" class="mt-4">
                        @csrf
                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-send"></i> Send test email
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <p class="text-muted mb-3" style="font-size: 0.88rem;">Uses the saved SMTP settings above.</p>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Send to</label>
                                        <input type="email" name="test_email" class="form-control" required
                                            value="{{ old('test_email', $settings->get('demo.notification_email') ?: $settings->get('mail.from_address')) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            <i class="bx bx-send me-1"></i> Send test
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif

                @if ($tab === 'google')
                    <form method="POST" action="{{ route('admin.settings.google.update') }}">
                        @csrf
                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bxl-google"></i> Google OAuth (Calendar + Meet)
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <p class="text-muted mb-3" style="font-size: 0.88rem;">
                                    Create the OAuth Client in Google Cloud Console, then paste Client ID / Secret here.
                                    Redirect URI is fixed by the app — copy it into Console (Authorized redirect URIs).
                                </p>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Redirect URI (read-only)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="googleRedirectUri" readonly
                                            value="{{ $redirectUri }}">
                                        <button type="button" class="btn btn-outline-secondary" id="copyRedirectUri">
                                            <i class="bx bx-copy"></i> Copy
                                        </button>
                                    </div>
                                    <div class="form-text">Paste this exact URI in Google Console. Do not edit here.</div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Client ID</label>
                                        <input type="text" name="google_client_id" class="form-control"
                                            value="{{ old('google_client_id', $settings->get('google.client_id')) }}"
                                            autocomplete="off">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Client Secret</label>
                                        <input type="password" name="google_client_secret" class="form-control" value=""
                                            placeholder="{{ $settings->secretIsSet('google.client_secret') ? '•••••••• (saved — leave blank to keep)' : 'Paste from Google Console' }}"
                                            autocomplete="new-password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Calendar ID</label>
                                        <input type="text" name="google_calendar_id" class="form-control"
                                            value="{{ old('google_calendar_id', $settings->get('google.calendar_id', 'primary')) }}"
                                            placeholder="primary">
                                        <div class="form-text">Usually <code>primary</code> for the connected Google account.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn px-4 admin-btn-primary">
                                Save Google settings
                            </button>
                        </div>
                    </form>

                    <div class="payment-gateway-card mt-4">
                        <div class="payment-gateway-head">
                            <h6 class="gw-title">
                                <i class="bx bx-link"></i> Connection
                            </h6>
                        </div>
                        <div class="payment-gateway-body">
                            @if ($googleConnected)
                                <div class="admin-connected-box mb-3">
                                    <i class="bx bx-check-circle"></i>
                                    <div>
                                        <div>Connected</div>
                                        <div class="small fw-normal" style="color: #2d6a4f;">
                                            Demo booking can create Meet events with this account.
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.google-calendar.connect') }}" class="btn btn-outline-primary">
                                        Reconnect
                                    </a>
                                    <form method="POST" action="{{ route('admin.settings.google.disconnect') }}"
                                        onsubmit="return confirm('Disconnect Google Calendar?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger">Disconnect</button>
                                    </form>
                                </div>
                            @else
                                <p class="mb-3 admin-text-primary">
                                    Not connected. Save Client ID / Secret first, then Connect.
                                </p>
                                <a href="{{ route('admin.google-calendar.connect') }}" class="btn admin-btn-primary">
                                    <i class="bx bxl-google me-1"></i> Connect Google Calendar
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($tab === 'payment')
                    <form action="#" method="POST" onsubmit="return false;">
                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bxl-paypal"></i> PayPal
                                </h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="paypalActive" checked>
                                    <label class="form-check-label" for="paypalActive">Active</label>
                                </div>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Client ID</label>
                                        <input type="text" class="form-control" value="AbCdEfGhIjKlMnOpQrStUvWxYz1234567890">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Client Secret</label>
                                        <input type="password" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">API Base URL</label>
                                        <input type="text" class="form-control" value="https://api-m.paypal.com">
                                        <div class="form-text">Sandbox: https://api-m.sandbox.paypal.com · Live: https://api-m.paypal.com</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">GST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="18">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SGST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="9">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">CGST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="9">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-rupee"></i> Razorpay
                                </h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="razorpayActive" checked>
                                    <label class="form-check-label" for="razorpayActive">Active</label>
                                </div>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Key ID</label>
                                        <input type="text" class="form-control" value="rzp_live_XXXXXXXXXXXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Key Secret</label>
                                        <input type="password" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">GST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="18">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SGST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="9">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">CGST (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="9">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn px-4 admin-btn-primary">
                                Save payment settings
                            </button>
                        </div>
                    </form>
                @endif

                @if ($tab === 'website')
                    <form method="POST" action="{{ route('admin.settings.website.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-image-alt"></i> Logos
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Header logo</label>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <img src="{{ $settings->get('website.logo_header') ? asset('storage/'.$settings->get('website.logo_header')) : asset('admin-assets/img/logo.png') }}"
                                                alt="Header logo" class="ws-logo-preview">
                                            <div class="flex-grow-1" style="min-width: 180px;">
                                                <input type="file" class="form-control" name="header_logo" accept="image/*">
                                                <div class="form-text mb-0">PNG or SVG recommended.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Footer logo</label>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <img src="{{ $settings->get('website.logo_footer') ? asset('storage/'.$settings->get('website.logo_footer')) : asset('admin-assets/img/logo.png') }}"
                                                alt="Footer logo" class="ws-logo-preview">
                                            <div class="flex-grow-1" style="min-width: 180px;">
                                                <input type="file" class="form-control" name="footer_logo" accept="image/*">
                                                <div class="form-text mb-0">PNG or SVG recommended.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-phone"></i> Contact
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Contact email</label>
                                        <input type="email" class="form-control" name="contact_email"
                                            value="{{ old('contact_email', $settings->get('website.contact_email', 'hello@ivclasses.com')) }}"
                                            placeholder="hello@example.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" name="address"
                                            value="{{ old('address', $settings->get('website.address', '123 Education Lane, Ahmedabad, Gujarat 380001')) }}"
                                            placeholder="Full address">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-search-alt"></i> SEO / Meta
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Meta title</label>
                                        <input type="text" class="form-control" name="meta_title"
                                            value="{{ old('meta_title', $settings->get('website.meta_title', 'Class Flow — Smart Classroom Management')) }}"
                                            placeholder="Page title for search engines">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Meta description</label>
                                        <textarea class="form-control" name="meta_description" rows="2"
                                            placeholder="Short description for search engines">{{ old('meta_description', $settings->get('website.meta_description', 'Manage classes, fees, attendance and more with Class Flow.')) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-dock-bottom"></i> Footer
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Footer description</label>
                                        <textarea class="form-control" name="footer_description" rows="3"
                                            placeholder="Short about text shown in the website footer">{{ old('footer_description', $settings->get('website.footer_description', 'Class Flow helps coaching institutes run demos, batches, fees and day-to-day operations from one place.')) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-card">
                            <div class="payment-gateway-head">
                                <h6 class="gw-title">
                                    <i class="bx bx-share-alt"></i> Social links
                                </h6>
                            </div>
                            <div class="payment-gateway-body">
                                <div class="row g-3">
                                    @foreach ([
                                        ['Facebook', 'bxl-facebook', 'facebook', 'https://facebook.com/ivclasses', 'https://facebook.com/...'],
                                        ['Instagram', 'bxl-instagram', 'instagram', 'https://instagram.com/ivclasses', 'https://instagram.com/...'],
                                        ['Twitter / X', 'bxl-twitter', 'twitter', 'https://x.com/ivclasses', 'https://x.com/...'],
                                        ['LinkedIn', 'bxl-linkedin', 'linkedin', 'https://linkedin.com/company/ivclasses', 'https://linkedin.com/...'],
                                        ['YouTube', 'bxl-youtube', 'youtube', 'https://youtube.com/@ivclasses', 'https://youtube.com/...'],
                                        ['WhatsApp', 'bxl-whatsapp', 'whatsapp', 'https://wa.me/919876543210', 'https://wa.me/91...'],
                                    ] as $i => $row)
                                        <div class="col-md-6">
                                            <label class="form-label">{{ $row[0] }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bx {{ $row[1] }}"></i></span>
                                                <input type="text" class="form-control" name="social_{{ $row[2] }}"
                                                    value="{{ old('social_'.$row[2], $settings->get('website.social.'.$row[2], $row[3])) }}"
                                                    placeholder="{{ $row[4] }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn px-4 admin-btn-primary">
                                Save website settings
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('copyRedirectUri')?.addEventListener('click', async function() {
            const input = document.getElementById('googleRedirectUri');
            if (!input) return;
            try {
                await navigator.clipboard.writeText(input.value);
                this.innerHTML = '<i class="bx bx-check"></i> Copied';
                setTimeout(() => {
                    this.innerHTML = '<i class="bx bx-copy"></i> Copy';
                }, 1600);
            } catch (e) {
                input.select();
                document.execCommand('copy');
            }
        });
    </script>
@endpush
