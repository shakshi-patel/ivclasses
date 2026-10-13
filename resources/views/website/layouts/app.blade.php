<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('brand.name') }} — Coaching Institute CRM & Mobile App</title>
    <meta name="description"
        content="All-in-one class management CRM for coaching institutes — Web CRM + Mobile App. Book a free demo." />
        <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('admin-assets/img/favicon_io/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/iv-tokens.css') }}" />
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css" />
    <link rel="stylesheet" href="{{ asset('website-assets/css/style.css') }}" />
    @stack('styles')
</head>

<body>
    @php
        $websiteSettings = app(\App\Services\AppSettingsService::class);
        $headerLogoPath = $websiteSettings->get('website.logo_header');
        $footerLogoPath = $websiteSettings->get('website.logo_footer');
        $headerLogoUrl = $headerLogoPath ? asset('storage/'.$headerLogoPath) : asset('website-assets/images/logo.png');
        $footerLogoUrl = $footerLogoPath ? asset('storage/'.$footerLogoPath) : asset('website-assets/images/logo.png');
    @endphp
    <header class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="{{ route('home') }}" class="logo"><img src="{{ $headerLogoUrl }}"
                    alt="{{ config('brand.name') }}" class="logo-img" /></a>
            <nav class="nav-links" aria-label="Primary">
                <a href="{{ route('home') }}" class="active">Home</a>
                <a href="{{ route('about') }}">About</a>
                <div class="nav-item has-drop">
                    <button type="button" class="drop-toggle" aria-haspopup="true">
                        Features
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>
                    <div class="dropdown">
                        <a href="{{ route('features') }}">All Features</a>
                        <a href="{{ route('features') }}#web-crm">Web CRM</a>
                        <a href="{{ route('features') }}#mobile-app">Mobile App</a>
                    </div>
                </div>
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('register-institute') }}" class="btn btn-ghost btn-sm">Start Trial</a>
                <a href="{{ route('book-demo') }}" class="btn btn-primary btn-sm">Book Demo</a>
            </nav>
            <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="mobile-drawer" id="mobileDrawer" hidden>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('about') }}">About</a>
            <a href="{{ route('features') }}">Features</a>
            <a href="{{ route('features') }}#web-crm" class="sub">Web CRM</a>
            <a href="{{ route('features') }}#mobile-app" class="sub">Mobile App</a>
            <a href="{{ route('pricing') }}">Pricing</a>
            <a href="{{ route('register-institute') }}" class="btn btn-ghost">Start Trial</a>
            <a href="{{ route('book-demo') }}" class="btn btn-primary">Book Demo</a>
        </div>
    </header>

    @yield('content')
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="logo"><img src="{{ $footerLogoUrl }}"
                        alt="{{ config('brand.name') }}" class="logo-img" /></a>
                <p>All-in-one class management CRM for coaching institutes — Web CRM + Mobile App.</p>
            </div>
            <div class="footer-col">
                <h4>Product</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('pricing') }}">Pricing</a>
            </div>
            <div class="footer-col">
                <h4>Features</h4>
                <a href="{{ route('features') }}#web-crm">Web CRM</a>
                <a href="{{ route('features') }}#mobile-app">Mobile App</a>
                <a href="{{ route('book-demo') }}">Book Demo</a>
                <a href="{{ route('register-institute') }}">Start Trial</a>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <a href="mailto:{{ config('brand.email') }}">{{ config('brand.email') }}</a>
                <a href="tel:{{ config('brand.phone_tel') }}">{{ config('brand.phone') }}</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <span data-year></span> {{ config('brand.name') }}. All rights reserved.</p>
        </div>
    </footer>
    <script src="{{ asset('website-assets/js/main.js') }}"></script>
    <script src="{{ asset('website-assets/js/phone-input-guard.js') }}"></script>
    @stack('scripts')
</body>

</html>
