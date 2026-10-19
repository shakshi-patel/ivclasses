<!DOCTYPE html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="{{ asset('admin-assets') }}/"
    data-template="vertical-menu-template-no-customizer" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>{{ $brandName ?? config('brand.name') }} - Dashboard</title>

    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="app-base-path" content="{{ rtrim(request()->getBasePath(), '/') }}" />
    <meta name="admin-lookups-url" content="{{ \App\Support\AdminUrl::lookups() }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('admin-assets/img/favicon_io/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/fonts/flag-icons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/css/rtl/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/css/rtl/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('admin-assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('admin-assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('admin-assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/shepherd/shepherd.css') }}" />

    <!-- Row Group CSS -->
    <link rel="stylesheet"
        href="{{ asset('admin-assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/dropzone/dropzone.css') }}" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/iv-tokens.css') }}" />
    @php
        $__ivInstitute = auth()->check() && ! auth()->user()->isPlatformUser()
            ? auth()->user()->institute
            : null;
    @endphp
    @if ($__ivInstitute)
        <style>
            :root {
                @foreach ($__ivInstitute->themeCssVariables() as $cssVar => $value)
                    {{ $cssVar }}: {{ $value }};
                @endforeach
            }
        </style>
    @endif
    <style>
        @keyframes cardReveal {
            0% {
                opacity: 0;
                transform: translateY(16px) scale(0.97);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes softGlow {

            0%,
            100% {
                box-shadow: 0 0.2rem 0.75rem rgba(44, 42, 74, 0.12);
            }

            50% {
                box-shadow: 0 0.35rem 1rem rgba(236, 18, 121, 0.18);
            }
        }

        @keyframes badgePulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        @keyframes iconFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-3px);
            }
        }

        @keyframes gradientSpin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes ripplePop {
            0% {
                transform: scale(0.25);
                opacity: 0.45;
            }

            100% {
                transform: scale(1.35);
                opacity: 0;
            }
        }

        @keyframes iconOrbit {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-4px) rotate(4deg);
            }
        }

        @keyframes statCardGlow {

            0%,
            100% {
                box-shadow: 0 0.65rem 1.5rem rgba(44, 42, 74, 0.1);
            }

            50% {
                box-shadow: 0 0.95rem 1.9rem rgba(184, 146, 126, 0.18);
            }
        }

        @keyframes statHighlightSweep {
            0% {
                transform: translateX(-160%) rotate(18deg);
            }

            100% {
                transform: translateX(260%) rotate(18deg);
            }
        }

        @keyframes statIconBob {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-3px);
            }
        }

        .compact-cards .card {
            border-radius: 0.75rem;
            border: 1px solid rgba(91, 42, 140, 0.16);
            background: linear-gradient(180deg, var(--iv-surface) 0%, var(--iv-bg) 100%);
            box-shadow: 0 0.2rem 0.75rem rgba(44, 42, 74, 0.12);
            position: relative;
            overflow: hidden;
            isolation: isolate;
            animation:
                cardReveal 0.45s ease both,
                softGlow 4.2s ease-in-out infinite;
        }

        .compact-cards .card::before {
            content: '';
            position: absolute;
            inset: -55%;
            background: conic-gradient(from 0deg,
                    rgba(242, 229, 220, 0),
                    rgba(242, 229, 220, 0.92),
                    rgba(205, 178, 161, 0.72),
                    rgba(242, 229, 220, 0));
            animation: gradientSpin 6.5s linear infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .compact-cards .card::after {
            content: '';
            position: absolute;
            top: -140%;
            left: -35%;
            width: 55%;
            height: 300%;
            background: linear-gradient(110deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.45) 48%,
                    rgba(255, 255, 255, 0) 100%);
            transform: rotate(16deg);
            transition: left 0.55s ease;
            pointer-events: none;
        }

        .compact-cards .metric-click-card .card-body::after {
            content: '';
            position: absolute;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            right: -22px;
            top: -28px;
            background: radial-gradient(circle, rgba(242, 229, 220, 0.78), rgba(205, 178, 161, 0));
            opacity: 0;
            pointer-events: none;
        }

        .compact-cards .card-body {
            padding: 0.72rem;
        }

        .compact-cards .metric-card .card-body {
            position: relative;
            padding-top: 1.45rem;
            padding-bottom: 0.42rem;
        }

        .compact-cards .metric-card .card {
            overflow: hidden;
        }

        .compact-cards .metric-value-badge {
            position: absolute;
            top: 0.52rem;
            right: 0.52rem;
            transform: none;
            z-index: 5;
            font-size: 0.9rem;
            font-weight: 700;
            width: 2.15rem;
            height: 2.15rem;
            min-width: 2.15rem;
            min-height: 2.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            text-align: center;
            padding: 0;
            border-radius: 50%;
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000;
            box-shadow:
                0 0.35rem 0.85rem rgba(0, 0, 0, 0.45),
                0 0 0 3px rgba(255, 255, 255, 0.9);
        }

        .compact-cards .metric-card .card-title {
            margin-bottom: 0.25rem !important;
        }

        .compact-cards .metric-card .card-body .card-title.d-flex {
            margin-bottom: 0.55rem !important;
        }

        .compact-cards .metric-click-card {
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .compact-cards .metric-click-card:hover {
            transform: translateY(-6px) rotateX(2deg) rotateY(-2deg);
            box-shadow: 0 0.55rem 1.2rem rgba(91, 42, 140, 0.24);
            border-top-color: #000000 !important;
            border-top-width: 2px;
        }

        .compact-cards .metric-click-card:hover::before {
            opacity: 1;
        }

        .compact-cards .metric-click-card:hover .card-body::after {
            opacity: 1;
            animation: ripplePop 0.75s ease-out;
        }

        .compact-cards .metric-click-card:hover::after {
            left: 120%;
        }

        .compact-cards .metric-click-card:active {
            transform: translateY(-1px) scale(0.99);
        }

        .compact-cards .metric-icon {
            width: 3.4rem;
            height: 3.4rem;
            animation:
                iconFloat 2.8s ease-in-out infinite,
                iconOrbit 3.8s ease-in-out infinite;
        }

        .compact-cards .avatar.metric-icon {
            width: 3.55rem !important;
            height: 3.55rem !important;
        }

        .compact-cards .metric-icon.avatar {
            background: transparent !important;
            box-shadow: none !important;
        }

        .compact-cards .metric-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: none !important;
            mix-blend-mode: normal !important;
            opacity: 1 !important;
        }

        .compact-cards .metric-title {
            font-weight: 1000;
            color: var(--gf-charcoal);
            font-size: 15px;
        }

        .compact-cards .metric-click-card .card-body {
            cursor: pointer;
        }

        .compact-cards .lower-metric-card .card {
            border-radius: 0.75rem;
        }

        .compact-cards .lower-metric-card .card-body {
            position: relative;
            padding-top: 1.7rem;
            padding-bottom: 0.6rem;
        }

        .compact-cards .lower-metric-card .card-title {
            position: absolute;
            top: 0.65rem;
            right: 0.65rem;
            margin: 0 !important;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.36rem 0.58rem;
            border-radius: 999px;
            background: rgba(242, 229, 220, 0.9);
            color: var(--gf-charcoal);
        }

        .compact-cards .lower-metric-card .metric-title {
            font-weight: 600;
            color: var(--gf-charcoal);
            font-size: 0.88rem;
        }

        .compact-cards .lower-metric-card .metric-click-card {
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .compact-cards .lower-metric-card .metric-click-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.35rem 1rem rgba(236, 18, 121, 0.2);
            border-top-color: #000000 !important;
            border-top-width: 3px;
        }

        .compact-cards .lower-metric-card .metric-click-card:active {
            transform: translateY(-1px) scale(0.99);
        }

        .compact-cards .lower-metric-card .metric-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        /* force all dashboard cards to exactly same size */
        .compact-cards .metric-card .card {
            height: 144px;
            min-height: 144px;
        }

        .compact-cards .metric-card .card-body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .compact-cards .metric-title {
            line-height: 1.2;
            min-height: 1.8rem;
            display: -webkit-box;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        /* strict non-destructive: hide extra lower-card internals without deleting markup */
        .compact-cards .lower-metric-card .metric-link>div:not(.card-body) {
            display: none !important;
        }

        .compact-cards .lower-metric-card .card-body> :not(.avatar):not(.metric-title):not(.card-title) {
            display: none !important;
        }

        .compact-cards .lower-metric-card .card-title {
            background: rgba(242, 229, 220, 0.92);
            animation: badgePulse 2.8s ease-in-out infinite;
        }

        .compact-cards .metric-value-badge {
            animation: badgePulse 2.8s ease-in-out infinite;
        }

        .compact-cards .col-xl-2:nth-child(1) .card {
            animation-delay: 0.04s, 0s;
        }

        .compact-cards .col-xl-2:nth-child(2) .card {
            animation-delay: 0.08s, 0.12s;
        }

        .compact-cards .col-xl-2:nth-child(3) .card {
            animation-delay: 0.12s, 0.2s;
        }

        .compact-cards .col-xl-2:nth-child(4) .card {
            animation-delay: 0.16s, 0.3s;
        }

        .compact-cards .col-xl-2:nth-child(5) .card {
            animation-delay: 0.2s, 0.4s;
        }

        .compact-cards .col-xl-2:nth-child(6) .card {
            animation-delay: 0.24s, 0.5s;
        }

        .compact-cards .col-xl-2:nth-child(7) .card {
            animation-delay: 0.28s, 0.6s;
        }

        .compact-cards .col-xl-2:nth-child(8) .card {
            animation-delay: 0.32s, 0.7s;
        }

        .compact-cards .col-xl-2:nth-child(9) .card {
            animation-delay: 0.36s, 0.8s;
        }

        .compact-cards .col-xl-2:nth-child(10) .card {
            animation-delay: 0.4s, 0.9s;
        }

        .compact-cards .col-xl-2:nth-child(11) .card {
            animation-delay: 0.44s, 1s;
        }

        .compact-cards .col-xl-2:nth-child(12) .card {
            animation-delay: 0.48s, 1.1s;
        }

        /* Show full sidebar menu labels instead of truncating with ellipsis */
        #layout-menu .menu-inner .menu-link .text-truncate {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            line-height: 1.25;
            display: block;
        }

        #layout-menu .menu-inner .menu-link {
            height: auto !important;
            min-height: 2.7rem;
            align-items: flex-start;
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
        }

        #layout-menu .menu-inner .menu-link .menu-icon,
        #layout-menu .menu-inner .menu-link .menu-toggle-icon {
            margin-top: 0.1rem;
        }

        #layout-menu .app-brand {
            position: relative;
            z-index: 5;
            background: #ffffff;
            overflow: visible !important;
            opacity: 1;
            filter: none !important;
            backdrop-filter: none !important;
            /* padding-top: 0.85rem;
            padding-bottom: 0.85rem; */
        }

        #layout-menu .app-brand-link,
        #layout-menu .app-brand-logo {
            opacity: 1 !important;
            filter: none !important;
            backdrop-filter: none !important;
        }

        #layout-menu .app-brand-logo img {
            height: auto;
            opacity: 1 !important;
            filter: none !important;
            transform: none !important;
            image-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }

        #layout-menu .app-brand-logo.is-custom img.app-brand-logo-full {
            width: 181px;
            height: auto;
            object-fit: contain;
        }

        #layout-menu .app-brand-logo.is-custom img.app-brand-logo-mini {
            width: 61px;
            height: 36px;
            object-fit: contain;
        }

        /* Expanded (motu) → full logo.png only */
        #layout-menu .app-brand-logo-full {
            display: block !important;
            width: 181px;
        }

        #layout-menu .app-brand-logo-mini {
            display: none !important;
            width: 61px;
            height: 36px;
            margin-inline: auto;
        }

        /* Collapsed (nanu) → logo2 only */
        html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand {
            overflow: hidden !important;
            justify-content: center;
            padding-inline: 0.5rem;
        }

        html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand-link {
            margin: 0 auto;
        }

        html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand-logo-full {
            display: none !important;
        }

        html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand-logo-mini {
            display: block !important;
        }

        #layout-menu .layout-menu-toggle i {
            color: var(--iv-primary-dark);
        }

        #layout-menu .menu-inner-shadow {
            display: none !important;
        }

        #layout-menu .menu-item.active.open>.menu-link,
        #layout-menu .menu-item.active>.menu-link {
            background: var(--iv-primary-dark) !important;
            color: var(--iv-secondary) !important;
            border-radius: 0.85rem;
            /* box-shadow: 0 0.55rem 1.2rem rgba(44, 42, 74, 0.18); */
        }

        #layout-menu .menu-item.active.open>.menu-link .menu-icon,
        #layout-menu .menu-item.active>.menu-link .menu-icon,
        #layout-menu .menu-item.active.open>.menu-link .menu-toggle-icon,
        #layout-menu .menu-item.active>.menu-link .menu-toggle-icon {
            color: var(--iv-secondary);
        }

        #layout-menu .menu-item.active.open>.menu-link .text-truncate,
        #layout-menu .menu-item.active>.menu-link .text-truncate {
            color: var(--iv-secondary) !important;
            font-weight: 700;
        }

        #layout-menu .menu-item.open>.menu-link.menu-toggle {
            color: var(--color-bg-dark);
        }

        #layout-menu .menu-item.open>.menu-link.menu-toggle .menu-icon,
        #layout-menu .menu-item.open>.menu-link.menu-toggle .menu-toggle-icon {
            color: var(--iv-secondary) !important;
        }

        #layout-menu .menu-sub .menu-item.active>.menu-link {
            background: #ffffff !important;
            color: var(--iv-primary-dark) !important;
            border-radius: 0.8rem;
            margin-inline: 0.25rem;
        }

        #layout-menu .menu-sub .menu-item.active>.menu-link .menu-icon,
        #layout-menu .menu-sub .menu-item.active>.menu-link .menu-toggle-icon,
        #layout-menu .menu-sub .menu-item.active>.menu-link .text-truncate {
            color: #384551 !important;
            font-weight: 500;
        }

        .admin-hero-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(91, 42, 140, 0.12);
            color: var(--gf-charcoal);
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.9), transparent 26%),
                linear-gradient(135deg, var(--iv-surface) 0%, var(--iv-bg) 58%, var(--iv-primary-light) 100%);
            box-shadow: 0 1rem 2rem rgba(44, 42, 74, 0.12);
        }

        .admin-hero-card::after {
            content: '';
            position: absolute;
            inset: auto -4rem -4rem auto;
            width: 15rem;
            height: 15rem;
            border-radius: 50%;
            /* background: rgba(184, 146, 126, 0.14); */
        }

        .admin-hero-card .chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(236, 18, 121, 0.08);
            color: var(--gf-charcoal);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .modal-header-black {
            background-color: var(--color-bg-dark);
            color: #ffffff;
        }

        .modal-header-black .btn-close {
            background-color: #ffffff;
            opacity: 1;
        }

        .admin-stat-card {
            border: 1px solid rgba(91, 42, 140, 0.1);
            border-radius: 1rem;
            background: linear-gradient(180deg, var(--iv-surface) 0%, var(--iv-bg) 100%);
            box-shadow: 0 0.65rem 1.5rem rgba(44, 42, 74, 0.1);
            position: relative;
            overflow: hidden;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
            animation: statCardGlow 4s ease-in-out infinite;
        }

        .admin-stat-card::before {
            content: '';
            position: absolute;
            inset: -35% auto auto -30%;
            width: 70%;
            height: 180%;
            background: linear-gradient(120deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(224, 199, 184, 0.16) 45%,
                    rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
            animation: statHighlightSweep 5.8s linear infinite;
        }

        .admin-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.9rem 1.8rem rgba(236, 18, 121, 0.16);
        }

        .admin-stat-card .stat-icon {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            animation: statIconBob 2.8s ease-in-out infinite;
        }

        .admin-stat-card .stat-label {
            color: var(--color-text-secondary);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .admin-stat-card .stat-value {
            color: var(--gf-ink);
            font-size: 1.45rem;
            font-weight: 700;
            line-height: 1;
        }

        .admin-stat-card .stat-trend {
            font-size: 0.74rem;
            font-weight: 600;
        }

        .admin-stat-card .card-body {
            padding: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .admin-stat-card .stat-meta {
            color: var(--color-text-secondary);
            font-size: 0.73rem;
        }

        .dashboard-panel {
            border: 1px solid rgba(44, 42, 74, 0.12);
            border-radius: 1.1rem;
            box-shadow: 0 0.85rem 2rem rgba(91, 42, 140, 0.1);
        }

        .dashboard-panel .card-header {
            background: transparent;
            border-bottom: 0;
            padding-bottom: 0;
        }

        .dashboard-subtitle {
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }

        .admin-page-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0 0 0.4rem;
            color: var(--iv-primary);
            font-weight: 700;
            font-size: 1.45rem;
        }

        .admin-page-title i {
            font-size: 1.35rem;
            color: var(--iv-primary);
        }

        .admin-main-card {
            background: #fff;
            border: 1px solid rgba(44, 42, 74, 0.1);
            border-radius: 1rem;
            box-shadow: 0 0.35rem 1.1rem rgba(44, 42, 74, 0.06);
        }

        /* List pages: toolbar (title/filters) + separate table card */
        .admin-list-page .admin-list-toolbar+.admin-list-table-card {
            margin-top: 1rem;
        }

        .admin-list-page .admin-list-toolbar .admin-filter-section {
            margin-bottom: 0;
        }

        .admin-list-page .admin-list-table-card .admin-table-wrap {
            margin-top: 0;
        }

        .admin-list-page .admin-list-summary {
            margin-top: 1.25rem;
        }

        .admin-main-card-inner {
            padding: 1.5rem 1.5rem 1.5rem;
        }

        .admin-main-card .breadcrumb {
            margin-bottom: 0;
        }

        .admin-filter-section {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            padding: 0;
            border: 0;
        }

        .admin-filter-section .form-label {
            color: var(--iv-primary);
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .admin-filter-section .form-select,
        .admin-filter-section .form-control {
            border-color: rgba(var(--iv-primary-rgb), 0.18);
            font-size: 0.875rem;
        }

        .admin-filter-section .form-select:focus,
        .admin-filter-section .form-control:focus {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--iv-primary-rgb), 0.12);
        }

        /* Common searchable Select2 */
        .admin-select-wrap {
            width: 100%;
        }

        .admin-select-wrap .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.18);
            border-radius: 0.375rem;
            background-color: #fff;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 0.75rem;
            padding-right: 2rem;
            color: #697a8d;
            font-size: 0.9375rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 6px;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--iv-primary-rgb), 0.12);
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.18);
            border-radius: 0.375rem;
            padding: 2px 6px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--iv-primary-rgb), 0.12);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: rgba(var(--iv-primary-rgb), 0.1);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            color: var(--iv-primary);
            border-radius: 0.25rem;
        }

        .select2-dropdown {
            border-color: rgba(var(--iv-primary-rgb), 0.22);
            border-radius: 0.375rem;
            overflow: hidden;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            border-radius: 0.375rem;
            padding: 0.4rem 0.65rem;
            outline: none;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 0.15rem rgba(var(--iv-primary-rgb), 0.12);
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background: var(--iv-primary);
            color: #fff;
        }

        .select2-container--default .select2-results__option[aria-selected='true'] {
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary);
        }

        .form-select-sm+.select2-container .select2-selection--single,
        .admin-select-wrap:has(.form-select-sm) .select2-selection--single {
            height: 31px;
        }

        .form-select-sm+.select2-container .select2-selection--single .select2-selection__rendered,
        .admin-select-wrap:has(.form-select-sm) .select2-selection__rendered {
            line-height: 29px;
            font-size: 0.8125rem;
            padding-left: 0.6rem;
        }

        .form-select-sm+.select2-container .select2-selection--single .select2-selection__arrow,
        .admin-select-wrap:has(.form-select-sm) .select2-selection__arrow {
            height: 29px;
        }

        .bg-menu-theme .menu-header::before,
        .bg-menu-theme .menu-header:before,
        .menu-inner>.menu-header::before {
            background-color: var(--iv-primary-dark) !important;
        }

        body.modal-open .shepherd-modal-overlay-container,
        body.modal-open .shepherd-element {
            display: none !important;
        }

        .iv-shepherd.shepherd-element {
            max-width: 460px;
            border: 0 !important;
            border-radius: 16px !important;
            background: #fff !important;
            overflow: visible !important;
            box-shadow: 0 20px 50px rgba(15, 40, 35, 0.18) !important;
        }

        .iv-shepherd .shepherd-content {
            border-radius: 16px !important;
            overflow: visible !important;
            background: #fff !important;
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-header {
            background: var(--iv-primary) !important;
            border: 0 !important;
            padding: 14px 18px !important;
            position: relative;
            border-radius: 16px 16px 0 0;
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-header .shepherd-title {
            color: #fff !important;
            font-weight: 750 !important;
            font-size: 1rem !important;
            line-height: 1.35;
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-header .shepherd-cancel-icon {
            position: absolute !important;
            top: -10px !important;
            right: -10px !important;
            z-index: 6;
            width: 28px !important;
            height: 28px !important;
            margin: 0 !important;
            padding: 0 !important;
            color: transparent !important;
            font-size: 0 !important;
            line-height: 28px !important;
            background-color: #fff !important;
            border-radius: 7px !important;
            opacity: 1 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F5C4D'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") !important;
            background-size: 11px !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            box-shadow: 0 4px 14px rgba(15, 40, 35, 0.2);
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-header .shepherd-cancel-icon:hover {
            color: transparent !important;
            transform: scale(1.06);
            box-shadow: 0 6px 16px rgba(15, 40, 35, 0.24);
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-text {
            color: #3d4a46 !important;
            background: #fff !important;
            font-size: 0.92rem !important;
            line-height: 1.55;
            padding: 18px 20px 8px !important;
        }

        .iv-shepherd.shepherd-element .shepherd-content .shepherd-footer {
            display: flex !important;
            align-items: center;
            gap: 0.45rem;
            background: #fff !important;
            border-top: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            padding: 12px 18px 18px !important;
            border-radius: 0 0 16px 16px;
        }

        .iv-shepherd .shepherd-footer .shepherd-button:first-child {
            margin-right: auto !important;
        }

        .iv-shepherd .shepherd-arrow:before {
            background: #fff !important;
            border-color: #fff !important;
        }

        .iv-shepherd[data-popper-placement^="bottom"] .shepherd-arrow:before {
            background: var(--iv-primary) !important;
            border-color: var(--iv-primary) !important;
        }

        .iv-shepherd .iv-tour-btn {
            border-radius: 8px !important;
            font-weight: 700 !important;
            padding: 0.4rem 0.85rem !important;
            box-shadow: none !important;
        }

        .iv-shepherd .iv-tour-btn--ghost {
            background: #fff !important;
            color: var(--iv-primary) !important;
            border: 1px solid var(--iv-primary) !important;
        }

        .iv-shepherd .iv-tour-btn--ghost:hover,
        .iv-shepherd .iv-tour-btn--ghost:focus {
            background: var(--iv-primary-light) !important;
            color: var(--iv-primary-dark) !important;
        }

        .iv-shepherd .iv-tour-btn--next {
            background: var(--iv-primary) !important;
            color: var(--iv-secondary) !important;
            border: 1px solid var(--iv-primary) !important;
        }

        .iv-shepherd .iv-tour-btn--next:hover,
        .iv-shepherd .iv-tour-btn--next:focus {
            background: var(--iv-primary-dark) !important;
            color: var(--iv-secondary) !important;
            border-color: var(--iv-primary-dark) !important;
        }

        .iv-tour-progress {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.7rem;
        }

        .iv-tour-progress span {
            font-size: 0.68rem;
            font-weight: 750;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--iv-primary);
            white-space: nowrap;
        }

        .iv-tour-progress__track {
            flex: 1;
            height: 4px;
            border-radius: 99px;
            background: var(--iv-primary-light);
            overflow: hidden;
        }

        .iv-tour-progress__track i {
            display: block;
            height: 100%;
            border-radius: 99px;
            background: var(--iv-primary);
        }

        .iv-tour-step p {
            margin: 0 0 0.65rem;
            color: #3d4a46;
        }

        .iv-tour-frame {
            margin: 0 0 0.75rem;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            background: #0f1c19;
        }

        .iv-tour-media {
            display: block;
            width: 100%;
            max-height: 280px;
            object-fit: contain;
            object-position: center;
            background: #0f1c19;
        }

        .iv-tour-embed {
            position: relative;
            padding-top: 56.25%;
            background: #0f1c19;
        }

        .iv-tour-embed iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .iv-tour-caption {
            font-size: 0.78rem;
            color: #6b7a76;
            margin: -0.35rem 0 0.65rem;
        }

        .iv-tour-cta {
            margin-top: 0.35rem;
        }

        .iv-onboarding-bar {
            position: relative;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            margin: 1rem 1.5rem 0;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            background: var(--iv-primary-dark);
            color: #fff;
            line-height: 1.45;
        }

        .iv-onboarding-bar span {
            flex: 1 1 16rem;
            min-width: 0;
        }

        .iv-onboarding-bar a {
            flex: 0 0 auto;
            color: var(--iv-primary-dark);
            background: var(--iv-secondary);
            border-radius: 0.55rem;
            padding: 0.4rem 0.85rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .shepherd-element .shepherd-button.btn-primary,
        .iv-shepherd .iv-tour-cta {
            background-color: var(--iv-primary) !important;
            border-color: var(--iv-primary) !important;
            color: var(--iv-secondary) !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
        }

        .shepherd-element .shepherd-button.btn-primary:hover,
        .shepherd-element .shepherd-button.btn-primary:focus,
        .iv-shepherd .iv-tour-cta:hover {
            background-color: var(--iv-primary-dark) !important;
            border-color: var(--iv-primary-dark) !important;
            color: var(--iv-secondary) !important;
        }

        .admin-btn-primary {
            background: var(--iv-primary) !important;
            color: var(--iv-secondary) !important;
            border: none;
            font-weight: 600;
        }

        .admin-btn-primary:hover,
        .admin-btn-primary:focus,
        .admin-btn-primary:active {
            background: var(--iv-primary-dark) !important;
            color: var(--iv-secondary) !important;
            box-shadow: 0 4px 12px rgba(var(--iv-primary-rgb), 0.22);
        }

        /* SweetAlert add / update / delete — theme colors, not template purple on hover */
        .swal2-popup .swal2-confirm,
        .swal2-popup .swal2-confirm.btn-primary {
            background-color: var(--iv-primary) !important;
            border-color: var(--iv-primary) !important;
            color: #fff !important;
            box-shadow: none !important;
        }

        .swal2-popup .swal2-confirm:hover,
        .swal2-popup .swal2-confirm:focus,
        .swal2-popup .swal2-confirm:active,
        .swal2-popup .swal2-confirm.btn-primary:hover,
        .swal2-popup .swal2-confirm.btn-primary:focus,
        .swal2-popup .swal2-confirm.btn-primary:active {
            background-color: var(--iv-primary-dark) !important;
            border-color: var(--iv-primary-dark) !important;
            color: #fff !important;
        }

        .swal2-popup .swal2-cancel,
        .swal2-popup .swal2-deny {
            background-color: #fff !important;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.35) !important;
            color: var(--iv-primary) !important;
            box-shadow: none !important;
        }

        .swal2-popup .swal2-cancel:hover,
        .swal2-popup .swal2-cancel:focus,
        .swal2-popup .swal2-cancel:active,
        .swal2-popup .swal2-deny:hover,
        .swal2-popup .swal2-deny:focus,
        .swal2-popup .swal2-deny:active {
            background-color: var(--iv-primary-light) !important;
            border-color: var(--iv-primary) !important;
            color: var(--iv-primary-dark) !important;
        }

        .todo-carry-btn {
            background: #0aa2c0 !important;
            color: #fff !important;
            border: none;
            font-weight: 600;
        }

        .todo-carry-btn:hover,
        .todo-carry-btn:focus {
            background: #0889a3 !important;
            color: #fff !important;
        }

        .todo-task-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }

        .todo-task-fields {
            flex: 1 1 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .todo-task-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .todo-row-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            line-height: 1;
        }

        .todo-row-btn.is-remove {
            background: #dc3545;
        }

        .todo-row-btn.is-remove:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .todo-row-btn.is-add {
            background: #1f9d62;
        }

        .todo-row-btn.is-add:hover {
            background: #188a54;
        }

        @media (max-width: 767.98px) {
            .todo-task-fields {
                grid-template-columns: 1fr;
            }
        }

        .inq-view-hero {
            text-align: center;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.1);
        }

        .inq-view-hero .inq-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--iv-primary-dark);
            margin-bottom: 6px;
        }

        .inq-view-hero .inq-date,
        .inq-view-hero .inq-phone {
            font-size: 0.86rem;
            color: var(--iv-text-secondary);
        }

        .inq-view-meta {
            display: grid;
            gap: 10px;
        }

        .inq-view-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.04);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            font-size: 0.86rem;
        }

        .inq-view-row .k {
            color: var(--iv-text-secondary);
            flex-shrink: 0;
        }

        .inq-view-row .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
        }

        .inq-view-remarks {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.06);
            border-left: 3px solid var(--iv-primary);
            font-size: 0.86rem;
        }

        .inq-view-remarks strong {
            color: var(--iv-primary-dark);
            display: block;
            margin-bottom: 4px;
        }

        .ie-view-section {
            margin-bottom: 16px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(var(--iv-primary-rgb), 0.03);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.1);
        }

        .ie-view-section:last-child {
            margin-bottom: 0;
        }

        .ie-view-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            font-size: 0.92rem;
            font-weight: 750;
            color: var(--iv-primary-dark);
        }

        .ie-view-section-title i {
            color: var(--iv-primary);
            font-size: 1.1rem;
        }

        .ie-view-grid {
            display: grid;
            gap: 8px;
        }

        .ie-view-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.86rem;
        }

        .ie-view-item .k {
            color: var(--iv-text-secondary);
            flex-shrink: 0;
        }

        .ie-view-item .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
        }

        .ie-view-item.is-amount .v {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1f9d62;
        }

        .ie-view-remarks-box {
            margin-top: 4px;
            padding: 10px 12px;
            border-radius: 8px;
            background: rgba(var(--iv-primary-rgb), 0.05);
            border-left: 3px solid var(--iv-primary);
            font-size: 0.86rem;
            color: var(--iv-text-secondary);
        }

        .notice-view-top {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.1);
        }

        .notice-view-thumb {
            flex-shrink: 0;
            width: 88px;
            height: 88px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            background: rgba(var(--iv-primary-rgb), 0.05);
        }

        .notice-view-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .notice-view-thumb-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--iv-primary);
            font-size: 1.75rem;
        }

        .notice-view-head {
            min-width: 0;
            flex: 1;
        }

        .notice-view-title {
            font-size: 1.02rem;
            font-weight: 800;
            color: var(--iv-primary-dark);
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .notice-view-date {
            font-size: 0.84rem;
            color: var(--iv-text-secondary);
            margin-bottom: 4px;
        }

        .notice-view-sub {
            font-size: 0.8rem;
            color: var(--iv-primary);
            font-weight: 600;
        }

        .admin-form-label {
            color: var(--iv-primary);
            font-weight: 600;
        }

        .admin-detail-block .form-label.fw-semibold,
        .modal-body .form-label.fw-semibold {
            color: var(--iv-primary);
            font-weight: 600;
        }

        .admin-filter-section-title {
            color: var(--iv-primary);
            font-weight: 700;
        }

        .admin-settings-tabs .nav-link {
            color: var(--iv-primary);
            font-weight: 600;
        }

        .admin-settings-tabs .nav-link.active {
            background: var(--iv-primary) !important;
            color: var(--iv-secondary) !important;
        }

        .admin-modal .modal-dialog,
        .admin-modal.modal-dialog-scrollable {
            overflow: visible !important;
        }

        .admin-modal .modal-content,
        .admin-modal .modal-dialog-scrollable .modal-content {
            border: 0;
            border-radius: 16px;
            overflow: visible !important;
            box-shadow: 0 20px 50px rgba(15, 40, 35, 0.18);
            position: relative;
        }

        .admin-modal .modal-dialog-scrollable .modal-body {
            overflow-y: auto;
        }

        .admin-modal .modal-header {
            background: var(--iv-primary);
            border: 0;
            padding: 14px 18px;
            position: relative;
            border-radius: 16px 16px 0 0;
        }

        .admin-modal .modal-title {
            color: #fff;
            font-weight: 750;
            font-size: 1rem;
        }

        .admin-modal .btn-close,
        .admin-modal .modal-header .btn-close,
        html:not([dir='rtl']) .admin-modal .btn-close,
        html:not([dir='rtl']) .admin-modal .modal-header .btn-close {
            position: absolute !important;
            top: -10px !important;
            right: -10px !important;
            left: auto !important;
            margin: 0 !important;
            transform: none !important;
            z-index: 6;
            width: 28px;
            height: 28px;
            padding: 0;
            background-color: #fff;
            border-radius: 7px;
            opacity: 1;
            filter: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F5C4D'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e");
            background-size: 11px;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 4px 14px rgba(15, 40, 35, 0.2);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .admin-modal .btn-close:hover,
        .admin-modal .btn-close:focus,
        .admin-modal .btn-close:active,
        html:not([dir='rtl']) .admin-modal .btn-close:hover,
        html:not([dir='rtl']) .admin-modal .btn-close:focus,
        html:not([dir='rtl']) .admin-modal .btn-close:active {
            background-color: #fff;
            opacity: 1;
            outline: none;
            transform: scale(1.06) !important;
            box-shadow: 0 6px 16px rgba(15, 40, 35, 0.24);
        }

        .admin-modal .modal-body {
            padding: 18px 20px 20px;
            background: #fff;
            border-radius: 0 0 16px 16px;
        }

        .admin-modal .modal-body:has(+ .modal-footer) {
            border-radius: 0;
        }

        .admin-modal .modal-footer {
            border-top: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            padding: 12px 18px 18px;
            background: #fff;
            border-radius: 0 0 16px 16px;
        }

        .admin-breadcrumb {
            font-size: 0.86rem;
            color: var(--color-text-secondary);
        }

        .admin-breadcrumb .breadcrumb-item a {
            color: var(--color-text-secondary);
            text-decoration: none;
        }

        .admin-breadcrumb .breadcrumb-item a:hover {
            color: var(--iv-primary);
        }

        .admin-breadcrumb .breadcrumb-item.active {
            color: var(--iv-primary);
        }

        .admin-breadcrumb .breadcrumb-item+.breadcrumb-item::before {
            color: var(--color-text-secondary);
        }

        .admin-detail-block {
            margin-top: 1.5rem;
            padding: 1.25rem 1.35rem;
            border: 1px solid rgba(44, 42, 74, 0.1);
            border-radius: 0.85rem;
            background: #fbfbfc;
        }

        .admin-detail-block h5 {
            margin: 0 0 1.1rem;
            color: var(--iv-primary);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .admin-detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--iv-text-secondary);
            margin-bottom: 0.25rem;
        }

        .admin-detail-value {
            color: var(--iv-primary);
            font-weight: 600;
        }

        .admin-connected-box {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 1.15rem 1.25rem;
            border-radius: 0.85rem;
            background: rgba(43, 182, 115, 0.1);
            border: 1px solid rgba(43, 182, 115, 0.22);
            color: #1f9d62;
            font-weight: 650;
        }

        .admin-connected-box i {
            font-size: 1.6rem;
        }

        .payment-gateway-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.05);
            margin-bottom: 1.25rem;
        }

        .payment-gateway-card:last-child {
            margin-bottom: 0;
        }

        .payment-gateway-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--iv-primary);
            color: var(--iv-secondary);
            padding: 12px 16px;
        }

        .payment-gateway-head .gw-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 750;
            font-size: 0.98rem;
            margin: 0;
            color: var(--iv-secondary);
        }

        .payment-gateway-head .gw-title i {
            font-size: 1.25rem;
            color: var(--iv-secondary);
        }

        .payment-gateway-head .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            min-height: 0;
            padding-left: 0;
        }

        .payment-gateway-head .form-check-label {
            color: var(--iv-secondary);
            font-weight: 650;
            font-size: 0.84rem;
        }

        .payment-gateway-head .form-check-input {
            width: 2.4em;
            height: 1.25em;
            margin: 0;
            cursor: pointer;
            float: none;
        }

        .payment-gateway-head .form-check-input:checked {
            background-color: #d9a441;
            border-color: #d9a441;
        }

        .payment-gateway-body {
            padding: 16px;
            background: rgba(var(--iv-primary-rgb), 0.025);
        }

        .payment-gateway-body .form-label {
            color: var(--iv-primary);
            font-weight: 600;
        }

        .payment-gateway-body .input-group-text {
            background: rgba(var(--iv-primary-rgb), 0.06);
            border-color: rgba(var(--iv-primary-rgb), 0.18);
            color: var(--iv-primary-dark);
            font-weight: 650;
        }

        .ws-settings-stack .admin-table-wrap+.admin-table-wrap {
            margin-top: 1.25rem;
        }

        .ws-logo-preview {
            height: 42px;
            width: auto;
            max-width: 140px;
            object-fit: contain;
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 8px;
            padding: 4px 8px;
        }

        .ws-social-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(var(--iv-primary-rgb), 0.08);
            color: var(--iv-primary);
            font-size: 1.2rem;
        }

        .admin-table-wrap {
            border-radius: 0.9rem;
            overflow: visible;
            border: 1px solid rgba(44, 42, 74, 0.1);
            background: #fff;
        }

        .admin-list-card-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--iv-primary);
            color: #fff;
            padding: 0.85rem 1.25rem;
            border-radius: 0.9rem 0.9rem 0 0;
        }

        .admin-list-card-bar h5 {
            margin: 0;
            color: var(--iv-secondary);
            font-size: 0.98rem;
            font-weight: 650;
        }

        .admin-list-card-bar .btn {
            background: #fff;
            color: var(--iv-primary);
            font-weight: 650;
            border: 0;
            border-radius: 0.45rem;
            padding: 0.4rem 0.85rem;
        }

        .admin-list-table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table-wrap .table-responsive {
            border-radius: 0 0 0.9rem 0.9rem;
        }

        .admin-list-table thead th {
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--iv-primary-dark);
            background: rgba(var(--iv-primary-rgb), 0.08);
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            padding: 0.85rem 1.15rem;
            white-space: nowrap;
        }

        .admin-list-table tbody td {
            padding: 1rem 1.15rem;
            color: var(--iv-primary);
            border-color: rgba(44, 42, 74, 0.06);
            vertical-align: middle;
        }

        .admin-list-table tbody tr:nth-child(even) {
            background: #f4f5f8;
        }

        .admin-list-table tbody tr:nth-child(odd) {
            background: #fff;
        }

        /* DataTables — match IV admin theme */
        .admin-table-wrap .dataTables_wrapper {
            padding: 0.85rem 1rem 1rem;
        }

        .admin-table-wrap .admin-list-card-bar+.dataTables_wrapper,
        .admin-table-wrap .table-responsive .dataTables_wrapper {
            padding-top: 0.5rem;
        }

        .admin-table-wrap .dataTables_wrapper .head-label:empty {
            display: none;
        }

        .admin-table-wrap .dataTables_wrapper .mt-n6 {
            margin-top: 0 !important;
        }

        .admin-table-wrap .dataTables_length,
        .admin-table-wrap .dataTables_filter,
        .admin-table-wrap .dataTables_info,
        .admin-table-wrap .dataTables_paginate {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .admin-table-wrap .dataTables_wrapper .row {
            margin-left: 0;
            margin-right: 0;
        }

        .admin-table-wrap .dataTables_wrapper>.row:last-child {
            margin-top: 1.15rem;
            padding-top: 0.35rem;
            align-items: center;
            row-gap: 10px;
        }

        .admin-table-wrap .dataTables_wrapper .table-responsive {
            margin-bottom: 0;
        }

        .admin-table-wrap .dataTables_info {
            padding: 6px 0 0;
            margin-right: 12px;
        }

        .admin-table-wrap .dataTables_paginate {
            padding-top: 4px;
        }

        .admin-table-wrap .dataTables_length label,
        .admin-table-wrap .dataTables_filter label,
        .admin-table-wrap .dataTables_info {
            font-size: 0.84rem;
            color: var(--iv-text-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .admin-table-wrap .dataTables_filter {
            text-align: right;
        }

        .admin-table-wrap .dataTables_filter label {
            justify-content: flex-end;
            width: 100%;
        }

        .admin-table-wrap .dataTables_length select {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            border-radius: 0.5rem;
            padding: 0.35rem 1.75rem 0.35rem 0.55rem;
            color: var(--iv-primary-dark);
            background-color: #fff;
            font-size: 0.84rem;
            min-width: 4.5rem;
        }

        .admin-table-wrap .dataTables_filter input {
            border: 1.5px solid rgba(var(--iv-primary-rgb), 0.18);
            border-radius: 0.55rem;
            padding: 0.45rem 0.75rem;
            font-size: 0.86rem;
            min-width: 200px;
            margin-left: 0 !important;
            color: var(--iv-text);
        }

        .admin-table-wrap .dataTables_filter input:focus {
            outline: none;
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 3px rgba(var(--iv-primary-rgb), 0.14);
        }

        .admin-table-wrap .dataTables_paginate {
            text-align: right;
        }

        .admin-table-wrap .dataTables_paginate .pagination {
            margin: 0;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .admin-table-wrap .dataTables_paginate .page-item {
            margin: 0 !important;
        }

        .admin-table-wrap .dataTables_paginate .page-link,
        .admin-table-wrap .dataTables_paginate .paginate_button {
            min-width: 34px;
            height: 34px;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 0.82rem !important;
            font-weight: 600;
            line-height: 1 !important;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16) !important;
            border-radius: 6px !important;
            background: #fff !important;
            color: var(--iv-primary-dark) !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            box-shadow: none !important;
        }

        .admin-table-wrap .dataTables_paginate .page-item.previous .page-link,
        .admin-table-wrap .dataTables_paginate .page-item.next .page-link {
            font-size: 0 !important;
            color: transparent !important;
        }

        .admin-table-wrap .dataTables_paginate .page-item.previous .page-link::before,
        .admin-table-wrap .dataTables_paginate .page-item.next .page-link::before {
            font-size: 18px;
            line-height: 1;
            color: var(--iv-primary-dark);
            font-weight: 700;
        }

        .admin-table-wrap .dataTables_paginate .page-item.previous .page-link::before {
            content: "\2039";
        }

        .admin-table-wrap .dataTables_paginate .page-item.next .page-link::before {
            content: "\203A";
        }

        .admin-table-wrap .dataTables_paginate .page-item.previous .page-link *,
        .admin-table-wrap .dataTables_paginate .page-item.next .page-link * {
            display: none !important;
        }

        .admin-table-wrap .dataTables_paginate .page-item.disabled .page-link::before {
            color: #9aa3ae;
        }

        .admin-table-wrap .dataTables_paginate .page-item.active .page-link,
        .admin-table-wrap .dataTables_paginate .paginate_button.current,
        .admin-table-wrap .dataTables_paginate .paginate_button.current:hover {
            background: var(--iv-primary) !important;
            border-color: var(--iv-primary) !important;
            color: #fff !important;
        }

        .admin-table-wrap .dataTables_paginate .page-link:hover,
        .admin-table-wrap .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
            background: rgba(var(--iv-primary-rgb), 0.08) !important;
            color: var(--iv-primary-dark) !important;
        }

        .admin-table-wrap .dataTables_paginate .page-item.disabled .page-link,
        .admin-table-wrap .dataTables_paginate .paginate_button.disabled {
            opacity: 0.55;
            background: #fff !important;
        }

        .admin-table-wrap .dataTables_length select,
        .admin-table-wrap .dataTables_filter input {
            height: 32px;
            font-size: 0.78rem;
        }

        .admin-table-wrap .dataTables_info {
            font-size: 0.78rem;
        }

        .admin-table-wrap table.dataTable {
            width: 100% !important;
            margin: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }

        .admin-table-wrap table.dataTable thead th {
            border-bottom-width: 1px !important;
        }

        .admin-table-wrap table.dataTable.no-footer {
            border-bottom: 0 !important;
        }

        .admin-table-wrap .dataTables_scrollBody,
        .admin-table-wrap div.dataTables_wrapper div.dataTables_processing {
            border-radius: 0.5rem;
        }

        .admin-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.28rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 650;
        }

        .admin-status-pill.is-active {
            background: rgba(43, 182, 115, 0.14);
            color: #1f9d62;
        }

        .admin-status-pill.is-muted {
            background: rgba(108, 117, 125, 0.12);
            color: #616a75;
        }

        .admin-status-pill.is-navy {
            background: rgba(44, 42, 74, 0.1);
            color: var(--iv-primary);
        }

        .admin-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            color: var(--iv-primary);
            background: transparent;
            border: 0;
            padding: 0;
            text-decoration: none;
            line-height: 1;
            vertical-align: middle;
        }

        .admin-action-icon i,
        .admin-action-icon .bx {
            font-size: 1.2rem;
            line-height: 1;
        }

        .admin-action-icon:hover,
        .admin-action-icon:focus {
            text-decoration: none;
            opacity: 0.85;
        }

        /* Edit → yellow / gold */
        .admin-action-icon .bx-pencil,
        .admin-action-icon .bx-edit,
        .admin-action-icon .bx-edit-alt {
            color: var(--iv-secondary) !important;
        }

        /* View → blue */
        .admin-action-icon .bx-show,
        .admin-action-icon .bx-show-alt,
        .admin-action-icon .bx-eye {
            color: #0d6efd !important;
        }

        .inst-view-hero {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .inst-view-logo {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: rgba(var(--iv-primary-rgb), 0.08);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            font-weight: 750;
            color: var(--iv-primary);
            font-size: 1.1rem;
        }

        .inst-view-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .inst-view-hero-text h5 {
            color: var(--iv-primary-dark);
            font-weight: 750;
        }

        .inst-view-meta {
            color: var(--iv-text-secondary);
            font-size: 0.86rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            align-items: center;
        }

        .inst-trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(var(--iv-secondary-rgb), 0.2);
            color: var(--iv-primary-dark);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
        }

        .inst-trial-badge.is-expired {
            background: rgba(220, 53, 69, 0.12);
            color: #b02a37;
        }

        .inst-trial-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: linear-gradient(135deg, rgba(var(--iv-primary-rgb), 0.08), rgba(var(--iv-secondary-rgb), 0.18));
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 1rem;
            color: var(--iv-primary-dark);
        }

        .inst-trial-banner i {
            font-size: 1.6rem;
            color: var(--iv-primary);
        }

        .inst-trial-banner.is-expired {
            background: rgba(220, 53, 69, 0.08);
            border-color: rgba(220, 53, 69, 0.2);
            color: #842029;
        }

        .inst-trial-banner.is-expired i {
            color: #dc3545;
        }

        .inst-view-section {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 0.9rem;
            background: #fff;
        }

        .inst-view-section:last-child {
            margin-bottom: 0;
        }

        .inst-view-section-head {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            background: var(--iv-primary);
            color: var(--iv-secondary);
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.65rem 0.9rem;
        }

        .inst-view-section-head i {
            font-size: 1.1rem;
        }

        .inst-view-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .inst-view-item {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0.9rem;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            border-right: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            font-size: 0.86rem;
        }

        .inst-view-item:nth-child(2n) {
            border-right: 0;
        }

        .inst-view-item-full {
            grid-column: 1 / -1;
            border-right: 0;
        }

        .inst-view-item .k {
            color: var(--iv-text-secondary);
            flex-shrink: 0;
        }

        .inst-view-item .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
            word-break: break-word;
        }

        @media (max-width: 576px) {
            .inst-view-grid {
                grid-template-columns: 1fr;
            }

            .inst-view-item {
                border-right: 0;
            }
        }

        /* Delete → danger red */
        .admin-action-icon .bx-trash,
        .admin-action-icon .bx-trash-alt {
            color: #dc3545 !important;
        }

        /* Block / deactivate → warning */
        .admin-action-icon .bx-user-x,
        .admin-action-icon .bx-block,
        .admin-action-icon .bx-x {
            color: var(--iv-warning) !important;
        }

        /* Approve → success green */
        .admin-action-icon .bx-check,
        .admin-action-icon .bx-check-circle {
            color: #198754 !important;
        }

        /* Permissions / convert / download / receipt — teal */
        .admin-action-icon .bx-key,
        .admin-action-icon .bx-lock-alt,
        .admin-action-icon .bx-shield-quarter,
        .admin-action-icon .bx-transfer,
        .admin-action-icon .bx-download,
        .admin-action-icon .bx-export,
        .admin-action-icon .bx-receipt,
        .admin-action-icon .bx-printer {
            color: var(--iv-primary) !important;
        }

        .role-table-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(15, 92, 77, 0.15);
        }

        /* Role permissions modal */
        #rolePermissionsModal .modal-dialog,
        #classPermissionsModal .modal-dialog {
            max-width: 960px;
        }

        #rolePermissionsModal .modal-body,
        #classPermissionsModal .modal-body {
            background: rgba(var(--iv-primary-rgb), 0.02);
            padding: 1rem 1.15rem 1.2rem;
        }

        .role-perm-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            background: #fff;
        }

        .role-perm-toolbar .role-perm-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary);
            font-weight: 750;
            font-size: 0.88rem;
        }

        .role-perm-toolbar .role-perm-role-badge i {
            font-size: 1.1rem;
        }

        .role-perm-toolbar .form-control {
            max-width: 240px;
            border-color: rgba(var(--iv-primary-rgb), 0.2);
        }

        .role-perm-toolbar .form-check-label {
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.86rem;
        }

        .role-perm-table-wrap {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.05);
        }

        .role-perm-table-wrap .admin-list-card-bar {
            border-radius: 0;
        }

        .role-perm-table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .role-perm-table thead th {
            background: rgba(var(--iv-primary-rgb), 0.06);
            color: var(--iv-primary);
            font-size: 0.78rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            white-space: nowrap;
            vertical-align: middle;
        }

        .role-perm-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            vertical-align: middle;
            color: var(--iv-primary-dark);
        }

        .role-perm-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .role-perm-table tbody tr:hover td {
            background: rgba(var(--iv-primary-rgb), 0.025);
        }

        .role-perm-module-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 750;
            font-size: 0.92rem;
            color: var(--iv-primary-dark);
        }

        .role-perm-module-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary);
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .role-perm-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 10px;
            align-items: center;
        }

        .role-perm-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            background: rgba(var(--iv-primary-rgb), 0.03);
            min-width: 88px;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .role-perm-chip:hover {
            border-color: rgba(var(--iv-primary-rgb), 0.35);
            background: rgba(var(--iv-primary-rgb), 0.06);
        }

        .role-perm-chip .form-check-input {
            margin: 0;
            float: none;
            cursor: pointer;
        }

        .role-perm-chip .form-check-input:checked {
            background-color: var(--iv-primary);
            border-color: var(--iv-primary);
        }

        .role-perm-chip .form-check-label {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 650;
            color: var(--iv-primary-dark);
            cursor: pointer;
        }

        .role-perm-all-cell .form-check {
            margin: 0;
            min-height: 0;
            padding-left: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .role-perm-all-cell .form-check-input {
            margin: 0;
            float: none;
        }

        .role-perm-all-cell .form-check-label {
            font-weight: 700;
            color: var(--iv-primary);
            font-size: 0.82rem;
        }

        .role-assignable-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            padding: 12px 14px;
            border: 1px solid rgba(15, 92, 77, 0.15);
            border-radius: 10px;
            background: rgba(15, 92, 77, 0.03);
        }

        /* Master — teacher view / attendance design helpers */
        .master-att-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            margin-top: 14px;
        }

        .master-att-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 650;
            color: var(--iv-text-secondary);
        }

        .master-att-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .master-att-dot.is-present {
            background: #3da87a;
        }

        .master-att-dot.is-absent {
            background: #e07068;
        }

        .master-att-dot.is-late {
            background: #e0b04a;
        }

        .master-att-dot.is-leave {
            background: #9b6bd4;
        }

        .master-att-dot.is-holiday {
            background: #6b8fd4;
        }

        .master-att-cal {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
        }

        .master-att-cal .dow {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--iv-text-secondary);
            padding: 4px 0;
        }

        .master-att-day {
            min-height: 40px;
            border-radius: 10px;
            border: 1px solid var(--iv-border);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--iv-text);
        }

        .master-att-day.is-empty {
            background: transparent;
            border-color: transparent;
        }

        .master-att-day.is-present {
            background: rgba(61, 168, 122, 0.28);
            border-color: rgba(61, 168, 122, 0.55);
            color: #1f6b4a;
        }

        .master-att-day.is-absent {
            background: rgba(224, 112, 104, 0.28);
            border-color: rgba(224, 112, 104, 0.55);
            color: #a33a36;
        }

        .master-att-day.is-late {
            background: rgba(224, 176, 74, 0.42);
            border-color: rgba(201, 136, 26, 0.65);
            color: #7a5a12;
        }

        .master-att-day.is-leave {
            background: rgba(155, 107, 212, 0.28);
            border-color: rgba(155, 107, 212, 0.55);
            color: #5b3a94;
        }

        .master-att-day.is-holiday {
            background: rgba(107, 143, 212, 0.3);
            border-color: rgba(61, 90, 158, 0.5);
            color: #2f4d8a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .master-att-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .master-att-nav .btn-att-nav {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            background: #fff;
            color: var(--iv-primary-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .master-att-nav .btn-att-nav:hover {
            background: rgba(var(--iv-primary-rgb), 0.08);
        }

        .master-att-nav #attMonthLabel {
            min-width: 130px;
            text-align: center;
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin: 0;
            font-size: 0.95rem;
        }

        .master-batch-card,
        .master-login-card {
            border: 1px solid var(--iv-border);
            border-radius: 14px;
            background: #fff;
            padding: 14px 16px;
            box-shadow: 0 6px 16px rgba(var(--iv-primary-rgb), 0.06);
            height: 100%;
        }

        .master-batch-card strong,
        .master-login-card strong {
            display: block;
            color: var(--iv-primary-dark);
            font-size: 0.95rem;
        }

        .master-login-card .meta {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            margin-top: 4px;
        }

        .master-profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid rgba(var(--iv-secondary-rgb), 0.45);
            background: var(--iv-primary-light);
        }

        .master-tabs .nav-link {
            color: var(--iv-primary);
            font-weight: 650;
            border: 0;
        }

        .master-tabs .nav-link.active {
            background: var(--iv-primary) !important;
            color: #fff !important;
        }

        .users-role-tabs {
            padding: 0.9rem 1rem 0.75rem;
            background: #fff;
            border-radius: 0.9rem 0.9rem 0 0;
            border-bottom: 1px solid rgba(44, 42, 74, 0.08);
        }

        .users-role-tabs button.nav-link {
            cursor: pointer;
        }

        .users-role-tabs + .admin-list-card-bar {
            border-radius: 0;
        }

        .master-tabs .nav-link .user-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            margin-left: 0.35rem;
            padding: 0 0.4rem;
            border-radius: 999px;
            font-size: 0.72rem;
            line-height: 1.35rem;
            background: rgba(var(--iv-primary-rgb), 0.12);
        }

        .master-tabs .nav-link.active .user-tab-count {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        .master-teacher-view-card {
            border: 1px solid var(--iv-border);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(var(--iv-primary-rgb), 0.06);
        }

        .master-teacher-profile-col {
            padding: 22px 18px;
            background: linear-gradient(180deg, rgba(var(--iv-primary-rgb), 0.08), #fff 55%);
            border-right: 1px solid var(--iv-border);
        }

        .master-teacher-tabs-col {
            padding: 18px 18px 22px;
        }

        .master-profile-rows {
            display: grid;
            gap: 8px;
        }

        .master-profile-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            font-size: 0.82rem;
        }

        .master-profile-row span {
            color: var(--iv-text-secondary);
        }

        .master-profile-row strong {
            color: var(--iv-primary-dark);
            text-align: right;
            font-weight: 700;
        }

        .master-batch-card-sm {
            padding: 12px 14px;
        }

        .master-batch-time {
            margin-top: 8px;
            font-size: 0.84rem;
            font-weight: 750;
            color: var(--iv-primary);
        }

        .master-day-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary-dark);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .master-punch-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .master-punch-row span {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(var(--iv-secondary-rgb), 0.2);
            color: #8a6418;
            font-size: 0.75rem;
            font-weight: 750;
        }

        .master-punch-row.is-muted span {
            background: rgba(108, 117, 125, 0.12);
            color: #616a75;
        }

        @media (max-width: 991.98px) {
            .master-teacher-profile-col {
                border-right: 0;
                border-bottom: 1px solid var(--iv-border);
            }
        }

        .master-info-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--iv-primary), var(--iv-primary-dark));
            color: #fff;
            margin-top: 1rem;
        }

        .master-info-bar strong {
            font-size: 1.05rem;
        }

        .master-info-bar span {
            opacity: 0.9;
            font-size: 0.86rem;
        }

        .master-teacher-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--iv-border);
            background: #fff;
            height: 100%;
        }

        .master-teacher-chip.master-teacher-chip--stack {
            flex-direction: column;
            align-items: stretch;
        }

        /* Do not use theme `.avatar` here — it expects a wrapper, so img alone breaks layout */
        .master-teacher-chip-img,
        .master-teacher-chip .avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--iv-primary-light);
            display: block;
            flex-shrink: 0;
        }

        .master-teacher-chip .t-name {
            font-weight: 750;
            color: var(--iv-primary-dark);
            font-size: 0.88rem;
            line-height: 1.2;
        }

        .master-teacher-chip .t-meta {
            font-size: 0.75rem;
            color: var(--iv-text-secondary);
        }

        .master-student-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            background: #fff;
            padding: 14px 16px 12px;
            height: 100%;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.07);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .master-student-card .s-top {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .master-student-card .s-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.35rem;
        }

        .master-student-card .s-info {
            flex: 1;
            min-width: 0;
        }

        .master-student-card .s-name {
            font-weight: 750;
            color: #1a1a1a;
            font-size: 0.92rem;
            line-height: 1.25;
            margin: 0;
        }

        .master-student-card .s-id {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            margin-top: 2px;
        }

        .master-student-card .s-roll {
            flex-shrink: 0;
            background: var(--iv-secondary);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 6px;
            white-space: nowrap;
            line-height: 1.2;
        }

        .master-student-card .s-father {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.84rem;
            color: var(--iv-text);
        }

        .master-student-card .s-father span:last-child {
            font-weight: 600;
            color: var(--iv-primary-dark);
        }

        .master-student-card .s-contact {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: rgba(var(--iv-primary-rgb), 0.1);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--iv-primary-dark);
        }

        .master-student-card .s-contact .s-call {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--iv-primary);
            color: #fff;
            text-decoration: none;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(var(--iv-primary-rgb), 0.28);
        }

        .master-student-card .s-contact .s-call i {
            color: #fff;
            font-size: 1rem;
            line-height: 1;
        }

        .master-student-card .s-contact .s-call:hover {
            background: var(--iv-primary-dark);
            color: #fff;
        }

        .btn-export-students {
            background: var(--iv-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 650;
            padding: 8px 14px;
            font-size: 0.84rem;
        }

        .btn-export-students:hover {
            background: var(--iv-primary-dark);
            color: #fff;
        }

        /* Add Student form */
        .master-stu-form-wrap {
            position: relative;
            margin-top: 1.25rem;
        }

        .master-stu-form-wrap .stu-top-back {
            position: absolute;
            right: 0;
            top: -2.75rem;
        }

        .master-stu-form-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.18);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 20px rgba(15, 40, 35, 0.06);
        }

        .master-stu-form-card .stu-form-head {
            background: var(--iv-primary);
            color: #fff;
            padding: 12px 18px;
            font-weight: 750;
            font-size: 1.05rem;
        }

        .master-stu-form-card .stu-form-body {
            padding: 1.25rem 1.35rem 1.1rem;
        }

        .master-stu-form-card .form-label {
            color: var(--iv-primary-dark);
            font-weight: 650;
            font-size: 0.84rem;
            margin-bottom: 0.35rem;
        }

        .master-stu-form-card .req {
            color: #d64545;
        }

        .master-stu-form-card .form-control,
        .master-stu-form-card .form-select {
            border-radius: 8px;
            border-color: rgba(var(--iv-primary-rgb), 0.2);
        }

        .master-stu-form-card .form-control:focus,
        .master-stu-form-card .form-select:focus {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--iv-primary-rgb), 0.15);
        }

        .master-stu-photo {
            border: 2px dashed rgba(var(--iv-primary-rgb), 0.35);
            border-radius: 12px;
            background: rgba(var(--iv-primary-rgb), 0.04);
            min-height: 220px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .master-stu-photo .photo-placeholder {
            color: rgba(var(--iv-primary-rgb), 0.45);
            font-size: 3.2rem;
            line-height: 1;
        }

        .master-stu-photo .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            position: absolute;
            inset: 0;
        }

        .master-stu-photo.has-preview .photo-placeholder {
            display: none;
        }

        .master-stu-photo.has-preview .photo-preview {
            display: block;
        }

        .master-stu-photo .photo-upload-btn {
            position: absolute;
            right: 12px;
            bottom: 12px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: var(--iv-primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(var(--iv-primary-rgb), 0.35);
            cursor: pointer;
        }

        .master-stu-photo .photo-upload-btn:hover {
            background: var(--iv-primary-dark);
        }

        .master-stu-hint {
            font-size: 0.75rem;
            color: #1f9d62;
            margin-top: 4px;
        }

        .master-stu-batch-box {
            background: rgba(var(--iv-primary-rgb), 0.05);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .master-stu-batch-add {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            background: var(--iv-secondary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .master-stu-batch-add:hover {
            filter: brightness(0.95);
            color: #fff;
        }

        .master-stu-parent-box {
            background: rgba(var(--iv-primary-rgb), 0.04);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 1rem;
        }

        .master-stu-parent-box .parent-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .master-stu-parent-box .parent-title::before {
            content: '';
            width: 4px;
            height: 18px;
            border-radius: 2px;
            background: var(--iv-primary);
        }

        .master-stu-parent-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 0;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            font-size: 0.88rem;
        }

        .master-stu-parent-row:last-child {
            border-bottom: 0;
        }

        .master-stu-parent-row .k {
            color: var(--iv-text-secondary);
        }

        .master-stu-parent-row .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
        }

        .master-stu-form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1.15rem;
        }

        .master-stu-form-actions .btn-stu-primary {
            background: var(--iv-primary);
            color: #fff;
            border: none;
        }

        .master-stu-form-actions .btn-stu-primary:hover {
            background: var(--iv-primary-dark);
            color: #fff;
        }

        /* Inquiry → Convert Student */
        .inq-convert-summary {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.05);
        }

        .inq-convert-summary-head {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            padding: 14px 18px;
            background: var(--iv-primary);
            color: #fff;
        }

        .inq-convert-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            flex-shrink: 0;
        }

        .inq-convert-summary-head h5 {
            margin: 0;
            color: #fff;
            font-weight: 750;
            font-size: 1.05rem;
        }

        .inq-convert-summary-head p {
            margin: 2px 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.82rem;
        }

        .inq-convert-summary-head .admin-status-pill {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .inq-convert-summary-body {
            padding: 16px 18px;
            background: rgba(var(--iv-primary-rgb), 0.03);
        }

        .inq-convert-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            background: #fff;
            height: 100%;
        }

        .inq-convert-meta .lbl {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--iv-text-secondary);
            font-weight: 650;
        }

        .inq-convert-meta .val {
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.92rem;
            word-break: break-word;
        }

        .inq-convert-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--iv-primary);
            font-weight: 750;
            font-size: 0.95rem;
        }

        .inq-convert-section-title i {
            font-size: 1.15rem;
        }

        .inq-batch-strip {
            padding: 14px 14px 12px;
            border: 1px dashed rgba(var(--iv-primary-rgb), 0.28);
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.035);
            margin-bottom: 12px;
        }

        .inq-batch-strip:last-child {
            margin-bottom: 0;
        }

        .inq-add-batch-btn,
        .inq-remove-batch-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
        }

        .inq-add-batch-btn {
            background: var(--iv-primary);
            color: #fff;
        }

        .inq-add-batch-btn:hover {
            background: var(--iv-primary-dark);
            color: #fff;
        }

        .inq-remove-batch-btn {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
        }

        .inq-remove-batch-btn:hover {
            background: #dc3545;
            color: #fff;
        }

        .inq-collect-check .form-check-label {
            color: var(--iv-primary-dark);
            font-weight: 650;
        }

        .inq-parent-box {
            margin-top: 1.35rem;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .inq-parent-box-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 16px;
            background: rgba(var(--iv-primary-rgb), 0.08);
            color: var(--iv-primary);
            font-weight: 750;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.12);
        }

        .inq-parent-box-body {
            padding: 14px 16px;
        }

        .inq-parent-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.035);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            height: 100%;
        }

        .inq-parent-item .k {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--iv-text-secondary);
            font-weight: 650;
        }

        .inq-parent-item .v {
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.92rem;
        }

        .master-stu-step {
            display: none;
        }

        .master-stu-step.is-active {
            display: block;
        }

        /* Student view */
        .stu-view-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .stu-view-tabs .nav-link {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            background: #fff;
            color: var(--iv-primary-dark);
            border-radius: 10px;
            padding: 0.45rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 650;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .stu-view-tabs .nav-link i {
            font-size: 1rem;
        }

        .stu-view-tabs .nav-link.active {
            background: var(--iv-primary);
            border-color: var(--iv-primary);
            color: #fff;
        }

        .stu-batch-note {
            font-size: 0.82rem;
            color: var(--iv-text-secondary);
            margin-bottom: 6px;
        }

        .stu-batch-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.08);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.88rem;
            margin-bottom: 1rem;
        }

        .stu-fee-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 14px;
            padding: 14px 16px;
            height: 100%;
            box-shadow: 0 4px 14px rgba(15, 40, 35, 0.05);
        }

        .stu-fee-stat .ico {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stu-fee-stat .ico.is-total {
            background: rgba(var(--iv-primary-rgb), 0.12);
            color: var(--iv-primary);
        }

        .stu-fee-stat .ico.is-recv {
            background: rgba(46, 139, 104, 0.14);
            color: #1f9d62;
        }

        .stu-fee-stat .ico.is-rem {
            background: rgba(var(--iv-secondary-rgb), 0.22);
            color: #9a7020;
        }

        .stu-fee-stat .lbl {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
        }

        .stu-fee-stat .val {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--iv-primary-dark);
            line-height: 1.2;
        }

        .ie-summary-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 14px;
            padding: 18px 16px;
            height: 100%;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.06);
            position: relative;
            overflow: hidden;
        }

        .ie-summary-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .ie-summary-card.is-income::before {
            background: #1f9d62;
        }

        .ie-summary-card.is-expense::before {
            background: #c0392b;
        }

        .ie-summary-card.is-balance::before {
            background: var(--iv-primary);
        }

        .ie-summary-card .ico {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            flex-shrink: 0;
        }

        .ie-summary-card.is-income .ico {
            background: rgba(31, 157, 98, 0.12);
            color: #1f9d62;
        }

        .ie-summary-card.is-expense .ico {
            background: rgba(192, 57, 43, 0.12);
            color: #c0392b;
        }

        .ie-summary-card.is-balance .ico {
            background: rgba(var(--iv-primary-rgb), 0.12);
            color: var(--iv-primary);
        }

        .ie-summary-card .lbl {
            font-size: 0.8rem;
            color: var(--iv-text-secondary);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .ie-summary-card .val {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .ie-summary-card.is-income .val {
            color: #1f9d62;
        }

        .ie-summary-card.is-expense .val {
            color: #c0392b;
        }

        .ie-summary-card.is-balance .val {
            color: var(--iv-primary);
        }

        .ie-modal-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--iv-primary);
            font-weight: 650;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            background: #fff;
        }

        .ie-modal-tabs .nav-link i {
            font-size: 1.1rem;
        }

        .ie-modal-tabs .nav-link.active {
            background: var(--iv-primary) !important;
            color: var(--iv-secondary) !important;
            border-color: var(--iv-primary);
        }

        .stu-fee-history-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 14px;
            background: #fff;
            padding: 14px 16px;
            box-shadow: 0 4px 12px rgba(15, 40, 35, 0.04);
        }

        .stu-fee-history-card .fh-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .stu-fee-history-card .fh-title {
            font-weight: 750;
            color: var(--iv-primary-dark);
        }

        .stu-fee-history-card .fh-amt {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--iv-primary);
            margin: 4px 0;
        }

        .stu-fee-history-card .fh-date {
            font-size: 0.8rem;
            color: var(--iv-text-secondary);
        }

        .stu-fee-history-card .fh-remarks {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.05);
            border-left: 3px solid var(--iv-primary);
            font-size: 0.84rem;
            color: var(--iv-text-secondary);
        }

        .stu-method-pill {
            display: inline-flex;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(var(--iv-primary-rgb), 0.12);
            color: var(--iv-primary-dark);
        }

        .stu-gst-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 40, 35, 0.06);
        }

        .stu-gst-card .gst-head {
            background: var(--iv-primary);
            color: #fff;
            padding: 12px 16px;
            font-weight: 750;
            font-size: 1rem;
        }

        .stu-gst-card .gst-body {
            padding: 16px;
        }

        .stu-gst-card .gst-party h6 {
            font-size: 0.86rem;
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin-bottom: 8px;
        }

        .stu-gst-card .gst-party p {
            margin: 0 0 4px;
            font-size: 0.84rem;
            color: var(--iv-text);
            line-height: 1.45;
        }

        .stu-gst-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 0.88rem;
        }

        .stu-gst-table th,
        .stu-gst-table td {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            padding: 10px 12px;
        }

        .stu-gst-table th {
            background: rgba(var(--iv-primary-rgb), 0.07);
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stu-gst-table .amt {
            text-align: right;
            white-space: nowrap;
        }

        .stu-gst-table .is-total {
            background: rgba(var(--iv-primary-rgb), 0.06);
            font-weight: 800;
            color: var(--iv-primary-dark);
        }

        .stu-gst-table input {
            width: 120px;
            max-width: 100%;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.25);
            border-radius: 8px;
            padding: 6px 10px;
            text-align: right;
            font-weight: 650;
        }

        .stu-gst-foot {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
        }

        .stu-gst-foot .note {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            max-width: 420px;
        }

        .stu-result-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 14px;
            background: #fff;
            padding: 14px 16px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(15, 40, 35, 0.04);
            border-top: 3px solid var(--iv-secondary);
        }

        .stu-result-card .r-title {
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin-bottom: 4px;
        }

        .stu-result-card .r-meta {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            margin-bottom: 10px;
        }

        .stu-result-card .r-score {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 8px;
        }

        .stu-result-card .r-score strong {
            font-size: 1.35rem;
            color: var(--iv-primary);
            font-weight: 800;
        }

        .stu-result-card .r-grade {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(var(--iv-secondary-rgb), 0.22);
            color: #8a6418;
            font-size: 0.75rem;
            font-weight: 750;
        }

        .result-sheet-card,
        .result-preview-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 40, 35, 0.06);
        }

        .result-sheet-head,
        .result-preview-head {
            background: linear-gradient(135deg, var(--iv-primary), #1b6f5f);
            color: #fff;
            padding: 12px 16px;
            font-weight: 750;
            font-size: 0.98rem;
        }

        .result-sheet-body,
        .result-preview-body {
            padding: 16px;
        }

        .result-sheet-meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .result-meta-box {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            background: rgba(var(--iv-primary-rgb), 0.04);
            padding: 10px 12px;
        }

        .result-meta-box .lbl {
            display: block;
            font-size: 0.72rem;
            color: var(--iv-text-secondary);
            margin-bottom: 4px;
        }

        .result-meta-box .val {
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.86rem;
        }

        .result-filter-note {
            margin-top: 8px;
            font-size: 0.76rem;
            color: var(--iv-text-secondary);
        }

        .result-section-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 750;
            margin-bottom: 10px;
        }

        .result-section-chip.is-present {
            background: rgba(31, 157, 98, 0.12);
            color: #1f9d62;
        }

        .result-section-chip.is-absent {
            background: rgba(192, 57, 43, 0.10);
            color: #c0392b;
        }

        .result-entry-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .result-entry-table th,
        .result-entry-table td {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            padding: 8px 10px;
            vertical-align: middle;
        }

        .result-entry-table th {
            background: rgba(var(--iv-primary-rgb), 0.06);
            color: var(--iv-primary-dark);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 750;
        }

        .result-entry-table td input,
        .result-entry-table td select {
            min-width: 100%;
            width: 100%;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.2);
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 0.78rem;
            background: #fff;
        }

        .result-entry-table td input[readonly] {
            background: rgba(var(--iv-primary-rgb), 0.04);
        }

        .result-entry-table .text-muted {
            font-size: 0.76rem;
        }

        .result-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 16px;
            margin-bottom: 14px;
            font-size: 0.82rem;
        }

        .result-preview-grid strong {
            color: var(--iv-primary-dark);
            font-weight: 750;
        }

        .result-preview-table-wrap {
            max-height: 340px;
            overflow: auto;
            border-radius: 12px;
        }

        @media (max-width: 991.98px) {

            .result-sheet-meta,
            .result-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {

            .result-sheet-meta,
            .result-preview-grid {
                grid-template-columns: 1fr;
            }
        }

        .stu-assign-batch-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            background: #fff;
            padding: 12px 14px;
            height: 100%;
            box-shadow: 0 3px 10px rgba(15, 40, 35, 0.04);
        }

        .stu-assign-batch-card strong {
            display: block;
            color: var(--iv-primary-dark);
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .stu-assign-batch-card .meta {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            line-height: 1.45;
        }

        /* Fees module */
        .fees-add-wrap {
            position: relative;
            margin-top: 1rem;
        }

        .fees-add-wrap .fees-top-back {
            position: absolute;
            right: 0;
            top: -2.75rem;
        }

        .fees-panel {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 20px rgba(15, 40, 35, 0.06);
            height: 100%;
        }

        .fees-panel-head {
            background: var(--iv-primary);
            color: #fff;
            padding: 12px 16px;
            font-weight: 750;
            font-size: 1rem;
        }

        .fees-panel-body {
            padding: 16px;
        }

        .fees-summary-box {
            background: rgba(var(--iv-primary-rgb), 0.04);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            padding: 14px;
            margin-top: 14px;
        }

        .fees-mini-stat {
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            border-radius: 10px;
            padding: 10px 12px;
            text-align: center;
            height: 100%;
        }

        .fees-mini-stat .lbl {
            font-size: 0.72rem;
            color: var(--iv-text-secondary);
            margin-bottom: 4px;
        }

        .fees-mini-stat .val {
            font-size: 1.05rem;
            font-weight: 800;
        }

        .fees-mini-stat .val.is-total {
            color: var(--iv-primary);
        }

        .fees-mini-stat .val.is-recv {
            color: #1f9d62;
        }

        .fees-mini-stat .val.is-rem {
            color: #c0392b;
        }

        .fees-sub-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 750;
            color: var(--iv-primary-dark);
            font-size: 0.9rem;
            margin: 14px 0 10px;
        }

        .fees-sub-title i {
            color: var(--iv-primary);
            font-size: 1.1rem;
        }

        .fees-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            font-size: 0.84rem;
        }

        .fees-detail-grid .k {
            color: var(--iv-text-secondary);
        }

        .fees-detail-grid .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
        }

        .fees-history-item {
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.84rem;
            color: var(--iv-text);
        }

        .fees-pay-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 14px;
            background: #fff;
            padding: 16px;
            box-shadow: 0 6px 20px rgba(15, 40, 35, 0.06);
            height: 100%;
        }

        .fees-pay-card .pay-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin-bottom: 14px;
            font-size: 0.95rem;
        }

        .fees-mode-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }

        .fees-mode-btn {
            flex: 1;
            border: 1.5px solid rgba(var(--iv-primary-rgb), 0.25);
            background: #fff;
            color: var(--iv-primary-dark);
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 0.84rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .fees-mode-btn.is-active {
            background: var(--iv-primary);
            border-color: var(--iv-primary);
            color: #fff;
        }

        .fees-online-only {
            display: none;
        }

        .fees-online-only.is-visible {
            display: block;
        }

        .fees-student-info .s-name {
            font-weight: 750;
            color: var(--iv-primary-dark);
        }

        .fees-student-info .s-meta {
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
        }

        .fees-view-hero {
            text-align: center;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.1);
        }

        .fees-view-hero .fee-type {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--iv-primary-dark);
            margin-bottom: 8px;
        }

        .fees-view-hero .fee-amt {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--iv-primary);
            line-height: 1.2;
            margin: 10px 0 6px;
        }

        .fees-view-hero .fee-date {
            font-size: 0.86rem;
            color: var(--iv-text-secondary);
        }

        .fees-view-meta {
            display: grid;
            gap: 10px;
        }

        .fees-view-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.04);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            font-size: 0.86rem;
        }

        .fees-view-row .k {
            color: var(--iv-text-secondary);
            flex-shrink: 0;
        }

        .fees-view-row .v {
            color: var(--iv-primary-dark);
            font-weight: 650;
            text-align: right;
        }

        .fees-view-note {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(var(--iv-primary-rgb), 0.05);
            border-left: 3px solid var(--iv-primary);
            font-size: 0.86rem;
        }

        .fees-view-note strong {
            color: var(--iv-primary-dark);
            display: block;
            margin-bottom: 4px;
        }

        .fees-view-note span {
            color: var(--iv-text-secondary);
        }

        .fees-fu-export-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .fees-fu-status-tabs {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .fees-fu-status-tabs .fu-tab {
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: transparent;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 650;
            padding: 0.35rem 0.75rem;
            border-radius: 0.45rem;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .fees-fu-status-tabs .fu-tab.is-active,
        .fees-fu-status-tabs .fu-tab:hover {
            background: #fff;
            color: var(--iv-primary);
        }

        .admin-modal .fu-form-note {
            font-size: 0.82rem;
            color: var(--iv-text-secondary);
            margin-top: 6px;
        }

        .admin-modal .fu-history-title {
            margin: 18px 0 10px;
            font-size: 0.95rem;
            font-weight: 750;
            color: var(--iv-primary-dark);
        }

        .admin-modal .fu-history-table {
            margin: 0;
            font-size: 0.84rem;
        }

        .admin-modal .fu-history-table thead th {
            background: rgba(var(--iv-primary-rgb), 0.08);
            color: var(--iv-primary-dark);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            white-space: nowrap;
        }

        .admin-modal .fu-history-table tbody td {
            padding: 0.65rem 0.75rem;
            color: var(--iv-primary);
            border-color: rgba(44, 42, 74, 0.08);
            vertical-align: middle;
        }

        .master-exam-card,
        .master-hw-card,
        .master-notice-card {
            position: relative;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            background: #fff;
            padding: 10px 12px;
            height: 100%;
            border-top: 2px solid var(--iv-primary);
            box-shadow: 0 3px 10px rgba(15, 40, 35, 0.05);
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        .master-hw-card {
            padding-right: 44px;
            overflow: hidden;
        }

        .master-hw-card:hover {
            transform: translateY(-2px);
            border-color: rgba(var(--iv-primary-rgb), 0.28);
            box-shadow: 0 8px 18px rgba(15, 40, 35, 0.09);
        }

        .master-hw-attachment-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: var(--iv-primary);
            background: rgba(var(--iv-primary-rgb), 0.1);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(15, 40, 35, 0.08);
        }

        .master-hw-attachment-badge:hover,
        .master-hw-attachment-badge:focus-visible {
            color: #fff;
            background: var(--iv-primary);
        }

        .master-hw-attachment-badge i {
            font-size: 1rem;
        }

        .master-hw-due {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 6px;
            width: fit-content;
            max-width: 100%;
            margin-top: 8px;
            padding: 5px 8px;
            border-radius: 7px;
            font-size: 0.7rem;
            line-height: 1.2;
            border: 1px solid transparent;
        }

        .master-hw-due i {
            font-size: 0.92rem;
        }

        .master-hw-due b {
            font-weight: 750;
        }

        .master-hw-due.is-overdue {
            color: #a33b2d;
            background: #fff0ed;
            border-color: #f4c2ba;
        }

        .master-hw-due.is-today {
            color: #8a5b00;
            background: #fff7dd;
            border-color: #f0d58a;
        }

        .master-hw-due.is-soon {
            color: #176b63;
            background: #e9f8f3;
            border-color: #b8e2d7;
        }

        .master-hw-due.is-upcoming,
        .master-hw-due.is-no-date {
            color: var(--iv-primary-dark);
            background: rgba(var(--iv-primary-rgb), 0.07);
            border-color: rgba(var(--iv-primary-rgb), 0.12);
        }

        .person-data-panel {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(15, 40, 35, 0.04);
        }

        .person-data-panel__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            background: linear-gradient(135deg, rgba(var(--iv-primary-rgb), 0.08), rgba(var(--iv-secondary-rgb), 0.08));
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.1);
        }

        .person-data-panel__title {
            color: var(--iv-primary-dark);
            font-weight: 750;
            font-size: 0.95rem;
        }

        .person-data-panel__meta {
            margin-top: 0.15rem;
            color: var(--iv-text-secondary);
            font-size: 0.78rem;
        }

        .person-data-panel__table .admin-list-table thead th {
            white-space: nowrap;
        }

        .person-data-panel__table .admin-list-table tbody td {
            vertical-align: middle;
            font-size: 0.86rem;
        }

        .person-data-panel__pager {
            display: flex;
            justify-content: flex-end;
            padding: 0.75rem 1rem;
            border-top: 1px solid rgba(var(--iv-primary-rgb), 0.08);
            background: rgba(var(--iv-primary-rgb), 0.02);
        }

        .person-data-panel__pager .pagination {
            margin: 0;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .person-data-panel__pager .page-link {
            min-width: 34px;
            height: 34px;
            padding: 0 0.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            border-radius: 6px;
            color: var(--iv-primary-dark);
            background: #fff;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .person-data-panel__pager .page-item.active .page-link {
            background: var(--iv-primary);
            border-color: var(--iv-primary);
            color: #fff;
        }

        .person-data-panel__pager .page-item.disabled .page-link {
            opacity: 0.45;
        }

        .person-ip {
            font-size: 0.78rem;
            color: var(--iv-primary-dark);
            background: rgba(var(--iv-primary-rgb), 0.06);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
        }

        .person-task-cell {
            max-width: 360px;
            white-space: pre-line;
            line-height: 1.4;
            color: var(--iv-primary-dark);
        }

        .master-exam-card strong,
        .master-hw-card strong,
        .master-notice-card strong {
            color: var(--iv-primary-dark);
            font-size: 0.86rem;
            font-weight: 750;
            display: block;
            line-height: 1.3;
        }

        .master-notice-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .master-notice-card__head strong {
            min-width: 0;
        }

        .master-notice-card__date {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            padding: 4px 7px;
            border-radius: 6px;
            background: rgba(var(--iv-primary-rgb), 0.08);
            color: var(--iv-primary);
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .master-notice-card__meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 9px;
            margin-top: 10px;
            padding-bottom: 9px;
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-text-secondary);
            font-size: 0.7rem;
            line-height: 1.4;
        }

        .master-notice-card__tag {
            padding: 3px 7px;
            border-radius: 999px;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary-dark);
            font-weight: 700;
        }

        .master-notice-card__description {
            margin-top: 10px;
        }

        .master-notice-card__label {
            display: block;
            margin-bottom: 4px;
            color: var(--iv-primary-dark);
            font-size: 0.68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .master-exam-card .c-date,
        .master-hw-card .c-date,
        .master-notice-card .c-date {
            font-size: 0.72rem;
            color: var(--iv-text-secondary);
            margin-top: 3px;
        }

        .master-exam-card .c-meta {
            font-size: 0.75rem;
            color: var(--iv-primary-dark);
            margin-top: 6px;
        }

        .master-exam-card .c-body,
        .master-hw-card .c-body,
        .master-notice-card .c-body {
            font-size: 0.76rem;
            color: var(--iv-text-secondary);
            line-height: 1.4;
        }

        .notice-description-cell {
            min-width: 260px;
            max-width: 460px;
            color: var(--iv-text-secondary) !important;
            line-height: 1.5;
        }

        .notice-date-cell {
            min-width: 110px;
            white-space: nowrap;
            font-weight: 650;
        }

        .master-roll-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }

        .master-roll-chip {
            display: flex;
            align-items: stretch;
            border: 1px solid var(--iv-border);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            user-select: none;
        }

        .master-roll-chip:hover {
            border-color: rgba(var(--iv-primary-rgb), 0.35);
        }

        .master-roll-chip.is-absent {
            border-color: rgba(var(--iv-error-rgb), 0.45);
            box-shadow: 0 0 0 2px rgba(var(--iv-error-rgb), 0.12);
        }

        .master-roll-chip.is-late {
            border-color: rgba(var(--iv-warning-rgb), 0.55);
            box-shadow: 0 0 0 2px rgba(var(--iv-warning-rgb), 0.14);
        }

        .master-roll-no {
            min-width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--iv-primary);
            color: #fff;
            font-weight: 800;
            font-size: 0.82rem;
            padding: 0 8px;
        }

        .master-roll-chip.is-absent .master-roll-no {
            background: var(--iv-error);
        }

        .master-roll-chip.is-late .master-roll-no {
            background: var(--iv-warning);
            color: #3d2e08;
        }

        .master-roll-name {
            padding: 8px 10px;
            font-size: 0.82rem;
            font-weight: 650;
            color: var(--iv-text);
            display: flex;
            align-items: center;
        }

        .master-att-stat {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 750;
            font-size: 0.9rem;
            color: #fff;
        }

        .master-att-stat.is-present {
            background: var(--iv-success);
        }

        .master-att-stat.is-absent {
            background: var(--iv-error);
        }

        .master-att-stat.is-late {
            background: var(--iv-warning);
            color: #3d2e08;
        }

        .master-att-report-wrap {
            overflow-x: auto;
        }

        .master-att-report {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 920px;
            width: 100%;
            font-size: 0.75rem;
        }

        .master-att-report th,
        .master-att-report td {
            border: 1px solid var(--iv-border);
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
            background: #fff;
        }

        .master-att-report thead th {
            background: rgba(var(--iv-primary-rgb), 0.06);
            color: var(--iv-primary-dark);
            font-weight: 800;
        }

        .master-att-report .name-col {
            text-align: left;
            min-width: 180px;
            padding-left: 10px;
            font-weight: 650;
            color: var(--iv-text);
            position: sticky;
            left: 0;
            z-index: 1;
            background: #fff;
        }

        .master-att-report .day-head {
            width: 28px;
            line-height: 1.05;
        }

        .master-att-report .is-sun {
            background: #6b7280;
            color: #fff;
            font-weight: 800;
        }

        .master-att-report .day-head.is-holiday {
            background: #6b8fd4;
            color: #fff;
            font-weight: 800;
        }

        .master-att-report .day-head.is-sun.is-holiday {
            background: linear-gradient(135deg, #6b7280 50%, #6b8fd4 50%);
        }

        .master-att-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 3px 7px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            color: #fff;
        }

        .master-att-badge.is-a {
            background: var(--iv-error);
        }

        .master-att-badge.is-p {
            background: var(--iv-success);
        }

        .master-att-badge.is-lp {
            background: var(--iv-warning);
            color: #3d2e08;
        }

        .master-att-badge.is-h {
            background: #6b8fd4;
            color: #fff;
        }

        .master-att-badge.is-lv {
            background: #9b6bd4;
            color: #fff;
        }

        .master-att-badge.is-s {
            background: #7a8584;
            color: #fff;
        }

        .report-stat-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            padding: 12px 14px;
            background: #fff;
            height: 100%;
        }

        .report-stat-label {
            font-size: 0.75rem;
            color: var(--iv-text-secondary);
            margin-bottom: 4px;
        }

        .report-stat-value {
            font-size: 1.35rem;
            font-weight: 750;
            color: var(--iv-primary-dark);
            line-height: 1.1;
        }

        .report-detail-box {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 12px;
            padding: 14px 16px;
            background: #fff;
            height: 100%;
        }

        .report-detail-title {
            font-weight: 750;
            color: var(--iv-primary-dark);
            margin-bottom: 10px;
            font-size: 0.92rem;
        }

        .report-table-scroll {
            overflow-x: auto;
        }

        @media print {

            .layout-menu,
            .layout-navbar,
            .layout-overlay,
            .drag-target,
            .no-print,
            .admin-breadcrumb,
            #moduleHelpHowBtn,
            .module-help-how-btn {
                display: none !important;
            }

            .layout-page,
            .content-wrapper,
            .container-xxl,
            .admin-main-card,
            .admin-main-card-inner {
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
                background: #fff !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .report-print-head {
                display: block !important;
            }

            .master-att-report-wrap {
                overflow: visible !important;
            }
        }

        .overview-kpi {
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, var(--color-bg-elevated) 0%, var(--color-bg-base) 100%);
            border: 1px solid rgba(91, 42, 140, 0.08);
        }

        .overview-kpi small {
            display: block;
            color: var(--color-text-secondary);
            margin-bottom: 0.25rem;
        }

        .overview-kpi strong {
            color: var(--gf-ink);
            font-size: 1.15rem;
        }

        .overview-project-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .overview-project-item {
            padding: 0.85rem 0.95rem;
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(91, 42, 140, 0.08);
        }

        .overview-project-item span {
            display: block;
            color: var(--color-text-secondary);
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .overview-project-item strong {
            color: var(--gf-ink);
            font-size: 1rem;
        }

        .signal-list {
            display: grid;
            gap: 0.9rem;
        }

        .signal-item {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }

        .signal-item .signal-dot {
            width: 0.8rem;
            height: 0.8rem;
            border-radius: 50%;
            margin-top: 0.35rem;
            flex-shrink: 0;
        }

        .ops-table thead th {
            font-size: 0.76rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--color-text-secondary);
            border-bottom-width: 1px;
        }

        .ops-table tbody td {
            vertical-align: middle;
        }

        .queue-item,
        .inventory-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0;
        }

        .queue-item+.queue-item,
        .inventory-item+.inventory-item {
            border-top: 1px dashed rgba(236, 18, 121, 0.28);
        }

        .queue-meta,
        .inventory-meta {
            color: var(--color-text-secondary);
            font-size: 0.86rem;
        }

        .mini-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.32rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .field-team-card {
            background: linear-gradient(180deg, #fffaf6 0%, #ffffff 100%);
        }

        @media (max-width: 767.98px) {
            .admin-hero-card .card-body {
                padding: 1.25rem;
            }

            .admin-stat-card .stat-value {
                font-size: 1.25rem;
            }

            .overview-project-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <!-- Helpers -->
    <script src="{{ asset('admin-assets/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('admin-assets/js/config.js') }}?v={{ filemtime(public_path('admin-assets/js/config.js')) }}"></script>
    @stack('styles')


</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo d-flex justify-content-center h-auto">
                    <a href="{{ route('dashboard') }}" class="app-brand-link">
                        @if (!empty($brandInstitute) && !empty($brandHasCustomLogo))
                            <span class="app-brand-logo demo is-custom">
                                <img class="app-brand-logo-full" src="{{ $brandLogoFull }}" alt="{{ $brandName }}"
                                    width="148" />
                                <img class="app-brand-logo-mini" src="{{ $brandLogoMini }}"
                                    alt="{{ $brandName }}" />
                            </span>
                        @elseif (!empty($brandInstitute))
                            <span class="app-brand-logo demo is-custom">
                                <span class="app-brand-text fw-bold text-body app-brand-logo-full"
                                    style="width: 181px; line-height: 1.2; font-size: 1.05rem;">
                                    {{ $brandName }}
                                </span>
                                <span class="app-brand-text fw-bold text-body app-brand-logo-mini"
                                    style="font-size: 0.85rem; text-align: center;">
                                    {{ \Illuminate\Support\Str::of($brandName)->substr(0, 2)->upper() }}
                                </span>
                            </span>
                        @else
                            <span class="app-brand-logo demo">
                                <img class="app-brand-logo-full" src="{{ asset('admin-assets/img/logo.png') }}"
                                    alt="{{ config('brand.name') }}" width="148" />
                                <img class="app-brand-logo-mini" src="{{ asset('admin-assets/img/logo2.png') }}"
                                    alt="{{ config('brand.name') }}" />
                            </span>
                        @endif
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                @include('admin.partials.sidebar-menu')
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="bx bx-menu bx-md"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item navbar-search-wrapper mb-0">
                                <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                                    <i class="bx bx-search bx-md"></i>
                                    <span class="d-none d-md-inline-block text-muted fw-normal ms-4">Search
                                        (Ctrl+/)</span>
                                </a>
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User -->
                            @auth
                                @php
                                    $navUser = auth()->user();
                                    $navAvatar = $navUser->avatarUrl();
                                    $navName = $navUser->name;
                                    $navLabel = $navUser->typeLabel();
                                @endphp
                                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                        data-bs-toggle="dropdown">
                                        <div class="avatar">
                                            <img src="{{ $navAvatar }}" alt="{{ $navName }}"
                                                class="w-px-40 h-auto rounded-circle" />
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar">
                                                            <img src="{{ $navAvatar }}" alt="{{ $navName }}"
                                                                class="w-px-40 h-auto rounded-circle" />
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">{{ $navName }}</h6>
                                                        <small class="text-muted">{{ $navLabel }}</small>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider my-1"></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                                <i class="bx bx-user-circle bx-md me-3"></i><span>Profile</span>
                                            </a>
                                        </li>
                                        @if (auth()->user()?->isPlatformUser())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                                    <i class="bx bx-cog bx-md me-3"></i><span>Settings</span>
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <div class="dropdown-divider my-1"></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <i class="bx bx-power-off bx-md me-3"></i><span>Log Out</span>
                                            </a>
                                            <form id="logout-form" method="POST" action="{{ route('logout') }}"
                                                class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @endauth
                            <!--/ User -->
                        </ul>
                    </div>

                    <!-- Search Small Screens -->
                    <div class="navbar-search-wrapper search-input-wrapper d-none">
                        <input type="text" class="form-control search-input container-xxl border-0"
                            placeholder="Search..." aria-label="Search..." />
                        <i class="bx bx-x bx-md search-toggler cursor-pointer"></i>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">

                    @yield('content')

                    @include('admin.partials.module_help_wizard')

                    @if (session('success'))
                        <div id="admin-flash-success" class="d-none">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div id="admin-flash-error" class="d-none">{{ session('error') }}</div>
                    @endif

                </div>
                <!-- / Layout page -->
            </div>

            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>



            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
            <div class="drag-target"></div>
        </div>
        <!-- / Layout wrapper -->
    </div>

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script src="{{ asset('admin-assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        (function($) {
            function initAdminListTables() {
                if (!$.fn || !$.fn.DataTable) return;

                $('.datatables-basic').each(function() {
                    var table = this;
                    var $table = $(table);
                    if ($.fn.DataTable.isDataTable(table)) return;

                    // Keep DOM structure stable — do not move/remove wrappers.
                    if ($table.find('tbody tr').length === 1 && $table.find('tbody td[colspan]').length) {
                        $table.find('tbody').empty();
                    }

                    var actionIdx = -1;
                    $table.find('thead th').each(function(i) {
                        if (/action/i.test($.trim($(this).text()))) actionIdx = i;
                    });

                    var columnDefs = [];
                    if (actionIdx >= 0) {
                        columnDefs.push({
                            targets: actionIdx,
                            orderable: false,
                            searchable: false
                        });
                    }

                    $table.DataTable({
                        paging: true,
                        searching: true,
                        lengthChange: true,
                        info: true,
                        ordering: true,
                        order: [],
                        autoWidth: false,
                        pageLength: 10,
                        lengthMenu: [7, 10, 25, 50, 75, 100],
                        columnDefs: columnDefs,
                        pagingType: 'simple_numbers',
                        scrollX: true,
                        language: {
                            lengthMenu: 'Show _MENU_ entries',
                            search: 'Search:',
                            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                            emptyTable: 'No data available',
                            zeroRecords: 'No matching records',
                            paginate: {
                                previous: '<i class="bx bx-chevron-left"></i>',
                                next: '<i class="bx bx-chevron-right"></i>'
                            }
                        },
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
                    });
                });
            }

            $(initAdminListTables);
        })(jQuery);
    </script>


    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('admin-assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <!-- Main JS -->
    <script>
        window.adminSearchData = @json(\App\Support\AdminSearchCatalog::payload());
    </script>
    <script src="{{ asset('admin-assets/js/main.js') }}?v={{ filemtime(public_path('admin-assets/js/main.js')) }}"></script>
    <script src="{{ asset('admin-assets/js/tables-datatables-basic.js') }}"></script>
    <!-- Dropzone -->
    <script src="{{ asset('admin-assets/vendor/libs/dropzone/dropzone.js') }}"></script>
    <!-- Form Validation -->
    <script src="{{ asset('admin-assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="{{ asset('admin-assets/js/admin-searchable-select.js') }}"></script>
    <script src="{{ asset('admin-assets/js/phone-input-guard.js') }}"></script>
    <script src="{{ asset('admin-assets/js/admin-institute-cascade.js') }}"></script>
    <script src="{{ asset('admin-assets/js/admin-ajax.js') }}?v={{ filemtime(public_path('admin-assets/js/admin-ajax.js')) }}"></script>
    <script src="{{ asset('admin-assets/vendor/libs/shepherd/shepherd.js') }}"></script>
    <script>
        window.IV_ONBOARDING = {
            stepBase: @json(url('/onboarding/steps')),
            dashboardUrl: @json(route('dashboard')),
        };
    </script>
    <script
        src="{{ asset('admin-assets/js/onboarding.js') }}?v={{ filemtime(public_path('admin-assets/js/onboarding.js')) }}">
    </script>
    <script
        src="{{ asset('admin-assets/js/module-help.js') }}?v={{ filemtime(public_path('admin-assets/js/module-help.js')) }}">
    </script>
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {



            var chartTextColor = '#6b7280';
            var chartBorderColor = 'rgba(44, 42, 74, 0.14)';

            var dailyOverviewChart = document.querySelector('#dailyOverviewChart');
            if (dailyOverviewChart && typeof ApexCharts !== 'undefined') {
                new ApexCharts(dailyOverviewChart, {
                    chart: {
                        height: 330,
                        type: 'line',
                        toolbar: {
                            show: false
                        },
                        parentHeightOffset: 0
                    },
                    series: [{
                            name: 'Caught',
                            type: 'column',
                            data: [42, 51, 49, 62, 74, 88, 96]
                        },
                        {
                            name: 'Operated',
                            type: 'line',
                            data: [28, 36, 34, 47, 59, 71, 82]
                        },
                        {
                            name: 'Released',
                            type: 'line',
                            data: [18, 24, 22, 31, 46, 58, 63]
                        }
                    ],
                    stroke: {
                        width: [0, 3, 3],
                        curve: 'smooth'
                    },
                    colors: ['#0f5c4d', '#d9a441', '#3b82a0'],
                    fill: {
                        opacity: [0.92, 1, 1],
                        gradient: {
                            shade: 'light',
                            type: 'vertical',
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            columnWidth: '38%'
                        }
                    },
                    grid: {
                        borderColor: chartBorderColor,
                        strokeDashArray: 5,
                        padding: {
                            top: -8,
                            left: 0,
                            right: 0,
                            bottom: -8
                        }
                    },
                    xaxis: {
                        categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            style: {
                                colors: chartTextColor,
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: chartTextColor,
                                fontSize: '12px'
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        labels: {
                            colors: chartTextColor
                        }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false
                    }
                }).render();
            }

            var operationMixChart = document.querySelector('#operationMixChart');
            if (operationMixChart && typeof ApexCharts !== 'undefined') {
                new ApexCharts(operationMixChart, {
                    chart: {
                        height: 260,
                        type: 'donut'
                    },
                    labels: ['Catch', 'Sterilization', 'ARV', 'Release', 'Emergency'],
                    series: [34, 26, 18, 14, 8],
                    colors: ['#0f5c4d', '#d9a441', '#3b82a0', '#08483d', '#2e8b68'],
                    stroke: {
                        width: 0
                    },
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            colors: chartTextColor
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,
                                    value: {
                                        show: true,
                                        fontSize: '1.5rem',
                                        fontWeight: 700,
                                        color: '#1f2933'
                                    },
                                    total: {
                                        show: true,
                                        label: 'Today',
                                        color: chartTextColor,
                                        formatter: function() {
                                            return '100%';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }).render();
            }
        });
    </script> --}}
    @stack('scripts')
</body>

</html>
