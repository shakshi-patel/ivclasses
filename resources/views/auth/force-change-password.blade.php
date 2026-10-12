<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set your password · {{ config('app.name', 'IV Classes') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/iv-tokens.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        body {
            font-family: "Outfit", system-ui, sans-serif;
            color: var(--iv-text);
            min-height: 100vh;
            overflow-x: hidden;
            background: var(--iv-bg);
        }

        .iv-stage {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(var(--iv-primary-rgb), 0.16), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 10%, rgba(var(--iv-secondary-rgb), 0.2), transparent 50%),
                radial-gradient(ellipse 60% 50% at 70% 90%, rgba(var(--iv-primary-dark-rgb), 0.12), transparent 55%),
                linear-gradient(160deg, #f7f3ea 0%, #eef6f3 42%, #f8f1e0 100%);
        }

        .iv-stage-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(var(--iv-primary-rgb), 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--iv-primary-rgb), 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, #000 20%, transparent 75%);
            animation: ivGridDrift 28s linear infinite;
        }

        .iv-float-icon {
            position: absolute;
            width: 44px;
            height: 44px;
            opacity: 0.55;
            color: var(--iv-primary);
            filter: drop-shadow(0 8px 16px rgba(var(--iv-primary-dark-rgb), 0.12));
            animation: ivBob var(--dur, 9s) ease-in-out infinite;
            animation-delay: var(--delay, 0s);
        }
        .iv-float-icon.is-gold { color: var(--iv-secondary); opacity: 0.7; }
        .iv-float-icon svg { width: 100%; height: 100%; display: block; }
        .iv-float-icon.i1 { top: 8%; left: 6%; --dur: 8s; width: 38px; height: 38px; }
        .iv-float-icon.i2 { top: 18%; right: 8%; --dur: 10s; --delay: -2s; width: 48px; height: 48px; }
        .iv-float-icon.i3 { bottom: 16%; left: 10%; --dur: 11s; --delay: -4s; }
        .iv-float-icon.i4 { bottom: 22%; right: 12%; --dur: 9s; --delay: -1s; width: 36px; height: 36px; }

        .iv-shell {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .iv-login {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            min-height: 640px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow:
                0 30px 80px rgba(var(--iv-primary-dark-rgb), 0.18),
                0 0 0 1px rgba(var(--iv-primary-rgb), 0.08);
            animation: ivCardIn 0.75s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .iv-login-visual {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2.25rem;
            color: #f4faf8;
            background: linear-gradient(155deg, var(--iv-primary-dark) 0%, var(--iv-primary) 52%, #0a6b58 100%);
            overflow: hidden;
        }

        .iv-illus {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .iv-illus-glow {
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            top: -60px;
            right: -40px;
            background: radial-gradient(circle, rgba(var(--iv-secondary-rgb), 0.45), transparent 68%);
            animation: ivPulseGlow 6s ease-in-out infinite;
        }

        .iv-illus-glow-2 {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            bottom: 18%;
            left: -50px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12), transparent 70%);
            animation: ivPulseGlow 8s ease-in-out infinite reverse;
        }

        .iv-illus-art {
            position: absolute;
            left: 50%;
            top: 14%;
            transform: translateX(-50%);
            width: min(280px, 82%);
            height: auto;
        }

        .iv-illus-art .lock-body { animation: ivBob 7s ease-in-out infinite; transform-origin: center bottom; }
        .iv-illus-art .shackle { animation: ivCapTilt 5.5s ease-in-out infinite; transform-origin: 140px 70px; }
        .iv-illus-art .orbit-ring {
            fill: none;
            stroke: rgba(var(--iv-secondary-rgb), 0.45);
            stroke-width: 1.5;
            stroke-dasharray: 6 8;
            animation: ivOrbitSpin 18s linear infinite;
            transform-origin: 140px 140px;
        }
        .iv-illus-art .spark {
            fill: var(--iv-secondary);
            animation: ivSpark 2.8s ease-in-out infinite;
        }

        .iv-login-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(180deg, transparent 28%, rgba(var(--iv-primary-dark-rgb), 0.55) 68%, rgba(var(--iv-primary-dark-rgb), 0.92) 100%);
        }

        .iv-visual-content {
            position: relative;
            z-index: 2;
            animation: ivRise 0.9s ease both;
        }

        .iv-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--iv-secondary-light);
            margin-bottom: 0.75rem;
        }

        .iv-kicker span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--iv-secondary);
            box-shadow: 0 0 0 0 rgba(var(--iv-secondary-rgb), 0.7);
            animation: ivPing 1.8s ease-out infinite;
        }

        .iv-visual-content h1 {
            font-family: "Fraunces", Georgia, serif;
            font-size: clamp(1.75rem, 2.8vw, 2.35rem);
            line-height: 1.12;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.65rem;
        }

        .iv-visual-content p {
            color: rgba(244, 250, 248, 0.86);
            font-size: 0.92rem;
            line-height: 1.6;
            max-width: 30ch;
        }

        .iv-tips {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            margin-top: 1.35rem;
        }

        .iv-tip {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.8rem;
            color: rgba(244, 250, 248, 0.88);
        }

        .iv-tip i {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(var(--iv-secondary-rgb), 0.25);
            color: var(--iv-secondary-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-style: normal;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .iv-login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.4rem 2.2rem;
            background: linear-gradient(180deg, #fff 0%, #fbfaf7 100%);
        }

        .iv-card { width: 100%; max-width: 380px; }

        .iv-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.35rem;
            width: 100%;
        }

        .iv-logo img {
            height: 110px;
            width: auto;
            max-width: 100%;
        }

        .iv-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(var(--iv-secondary-rgb), 0.16);
            color: #9a6f18;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.85rem;
        }

        .iv-badge svg {
            width: 14px;
            height: 14px;
        }

        .iv-card h2 {
            font-family: "Fraunces", Georgia, serif;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
            color: var(--iv-primary-dark);
        }

        .iv-card > .iv-lead {
            color: var(--iv-text-secondary);
            font-size: 0.92rem;
            margin-bottom: 1.45rem;
            line-height: 1.5;
        }

        .iv-field { margin-bottom: 1.05rem; }

        .iv-field label {
            display: block;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--iv-primary);
            margin-bottom: 0.4rem;
        }

        .iv-input-wrap { position: relative; }

        .iv-input-wrap > svg.iv-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #7a8a84;
            pointer-events: none;
        }

        .iv-input-wrap input {
            width: 100%;
            border: 1.5px solid rgba(var(--iv-primary-rgb), 0.16);
            background: #fff;
            border-radius: 0.85rem;
            padding: 0.9rem 2.85rem 0.9rem 2.6rem;
            font: inherit;
            font-size: 0.95rem;
            color: var(--iv-text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .iv-input-wrap input:focus {
            border-color: var(--iv-primary);
            box-shadow: 0 0 0 4px rgba(var(--iv-primary-rgb), 0.14);
        }

        .iv-toggle-eye {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 0.65rem;
            background: transparent;
            color: #7a8a84;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s, background 0.15s;
        }

        .iv-toggle-eye:hover {
            color: var(--iv-primary);
            background: rgba(var(--iv-primary-rgb), 0.08);
        }

        .iv-toggle-eye svg {
            width: 18px;
            height: 18px;
        }

        .iv-toggle-eye .eye-off { display: none; }
        .iv-toggle-eye.is-shown .eye-on { display: none; }
        .iv-toggle-eye.is-shown .eye-off { display: block; }

        .iv-hint {
            margin: -0.35rem 0 1.1rem;
            font-size: 0.78rem;
            color: var(--iv-text-secondary);
            line-height: 1.45;
        }

        .iv-submit {
            width: 100%;
            border: 0;
            border-radius: 0.95rem;
            padding: 0.98rem 1.2rem;
            font: inherit;
            font-size: 0.96rem;
            font-weight: 750;
            color: #fff;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--iv-primary) 0%, var(--iv-primary-dark) 100%);
            box-shadow: 0 14px 28px rgba(var(--iv-primary-rgb), 0.3);
            transition: transform 0.18s, box-shadow 0.18s, filter 0.18s;
            min-height: 52px;
        }

        .iv-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow: 0 18px 32px rgba(var(--iv-primary-rgb), 0.36);
        }

        .iv-submit:disabled { cursor: wait; }
        .iv-submit.is-loading {
            background: linear-gradient(135deg, var(--iv-primary-dark) 0%, var(--iv-primary) 55%, #0a6b58 100%);
            background-size: 200% 200%;
            animation: ivBtnShimmer 1.4s ease infinite;
        }
        .iv-submit .iv-btn-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }
        .iv-submit .iv-btn-loader {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
        }
        .iv-submit.is-loading .iv-btn-label { display: none; }
        .iv-submit.is-loading .iv-btn-loader { display: inline-flex; }

        .iv-edu-loader-cap {
            width: 22px;
            height: 22px;
            color: var(--iv-secondary-light);
            animation: ivCapSpin 1.2s ease-in-out infinite;
        }

        .iv-errors {
            list-style: none;
            margin: 0.4rem 0 0;
            color: var(--iv-error);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .iv-alert {
            margin-bottom: 1rem;
            padding: 0.8rem 0.95rem;
            border-radius: 0.85rem;
            background: rgba(var(--iv-error-rgb), 0.08);
            border: 1px solid rgba(var(--iv-error-rgb), 0.22);
            color: var(--iv-error);
            font-size: 0.84rem;
            font-weight: 600;
        }

        .iv-alert ul {
            margin: 0.25rem 0 0;
            padding-left: 1.1rem;
            font-weight: 500;
        }

        .iv-footer-link {
            display: block;
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.84rem;
            color: var(--iv-text-secondary);
            text-decoration: none;
            font-weight: 600;
        }

        .iv-footer-link:hover { color: var(--iv-primary); }
        .iv-footer-link span { color: var(--iv-primary); }

        @keyframes ivBob {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-14px) rotate(2deg); }
        }
        @keyframes ivCapTilt {
            0%, 100% { transform: rotate(-4deg); }
            50% { transform: rotate(5deg); }
        }
        @keyframes ivOrbitSpin { to { transform: rotate(360deg); } }
        @keyframes ivSpark {
            0%, 100% { opacity: 0.35; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1.15); }
        }
        @keyframes ivPulseGlow {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.08); }
        }
        @keyframes ivPing {
            0% { box-shadow: 0 0 0 0 rgba(var(--iv-secondary-rgb), 0.7); }
            100% { box-shadow: 0 0 0 10px rgba(var(--iv-secondary-rgb), 0); }
        }
        @keyframes ivRise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes ivCardIn {
            from { opacity: 0; transform: translateY(18px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes ivGridDrift {
            from { background-position: 0 0; }
            to { background-position: 48px 48px; }
        }
        @keyframes ivCapSpin {
            0%, 100% { transform: translateY(0) rotate(-8deg); }
            50% { transform: translateY(-4px) rotate(8deg); }
        }
        @keyframes ivBtnShimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @media (max-width: 860px) {
            .iv-login {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .iv-login-visual {
                min-height: 220px;
                padding: 1.35rem 1.4rem;
            }
            .iv-illus-art {
                top: 8%;
                width: 180px;
                opacity: 0.85;
            }
            .iv-visual-content h1 { font-size: 1.4rem; }
            .iv-visual-content > p,
            .iv-tips { display: none; }
            .iv-login-panel { padding: 1.55rem 1.25rem 1.75rem; }
            .iv-logo img { height: 88px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .iv-float-icon,
            .iv-stage-grid,
            .iv-illus-art .lock-body,
            .iv-illus-art .shackle,
            .iv-illus-art .orbit-ring,
            .iv-illus-art .spark,
            .iv-illus-glow,
            .iv-illus-glow-2,
            .iv-kicker span,
            .iv-login,
            .iv-visual-content,
            .iv-submit.is-loading,
            .iv-edu-loader-cap {
                animation: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="iv-stage" aria-hidden="true">
        <div class="iv-stage-grid"></div>
        <div class="iv-float-icon i1">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a5 5 0 0 0-5 5v3H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5zm-3 8V6a3 3 0 1 1 6 0v3H9z"/></svg>
        </div>
        <div class="iv-float-icon i2 is-gold">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 9.5L4.5 8 12 4.5 19.5 8 12 11.5zM4 10.2v5.3L12 20l8-4.5v-5.3l-8 4-8-4z"/></svg>
        </div>
        <div class="iv-float-icon i3 is-gold">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1l9 4v6c0 5.25-3.5 9.74-9 11-5.5-1.26-9-5.75-9-11V5l9-4zm0 2.18L5 6.09v4.91c0 4.14 2.68 7.68 7 8.82 4.32-1.14 7-4.68 7-8.82V6.09l-7-2.91z"/></svg>
        </div>
        <div class="iv-float-icon i4">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3zm1 2.07V12h6.93A7.002 7.002 0 0 0 13 5.07z"/></svg>
        </div>
    </div>

    <div class="iv-shell">
        <div class="iv-login">
            <aside class="iv-login-visual">
                <div class="iv-illus" aria-hidden="true">
                    <span class="iv-illus-glow"></span>
                    <span class="iv-illus-glow-2"></span>
                    <svg class="iv-illus-art" viewBox="0 0 280 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle class="orbit-ring" cx="140" cy="140" r="100"/>
                        <circle class="orbit-ring" cx="140" cy="140" r="72" style="animation-duration:12s;animation-direction:reverse;opacity:0.5"/>
                        <circle class="spark" cx="78" cy="78" r="3"/>
                        <circle class="spark" cx="210" cy="96" r="2.5" style="animation-delay:-0.8s"/>
                        <circle class="spark" cx="188" cy="58" r="2" style="animation-delay:-1.5s"/>

                        <g class="shackle">
                            <path d="M98 108 V78 a42 42 0 0 1 84 0 v30" stroke="#D9A441" stroke-width="14" fill="none" stroke-linecap="round"/>
                            <path d="M98 108 V78 a42 42 0 0 1 84 0 v30" stroke="#F6E8C3" stroke-width="5" fill="none" stroke-linecap="round" opacity="0.5"/>
                        </g>

                        <g class="lock-body">
                            <rect x="78" y="108" width="124" height="108" rx="18" fill="#0F5C4D"/>
                            <rect x="86" y="116" width="108" height="92" rx="12" fill="#08483D"/>
                            <circle cx="140" cy="158" r="16" fill="#D9A441"/>
                            <circle cx="140" cy="158" r="8" fill="#F6E8C3"/>
                            <rect x="134" y="168" width="12" height="28" rx="4" fill="#D9A441"/>
                            <text x="140" y="228" text-anchor="middle" fill="#F6E8C3" font-size="11" font-family="Outfit, sans-serif" font-weight="600" letter-spacing="1.2">SECURE ACCESS</text>
                        </g>
                    </svg>
                </div>

                <div class="iv-visual-content">
                    <p class="iv-kicker"><span></span> First login</p>
                    <h1>Protect your<br>institute desk.</h1>
                    <p>Replace the temporary password from your welcome email with one only you know.</p>
                    <div class="iv-tips">
                        <div class="iv-tip"><i>✓</i> At least 8 characters</div>
                        <div class="iv-tip"><i>✓</i> Mix letters &amp; numbers</div>
                        <div class="iv-tip"><i>✓</i> Don’t reuse the email password</div>
                    </div>
                </div>
            </aside>

            <main class="iv-login-panel">
                <div class="iv-card">
                    <a href="{{ route('home') }}" class="iv-logo">
                        <img src="{{ asset('website-assets/images/logo.png') }}" alt="IV Classes">
                    </a>

                    <div class="iv-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 1a5 5 0 0 0-5 5v3H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5zm-3 8V6a3 3 0 1 1 6 0v3H9z"/>
                        </svg>
                        Temporary password expired
                    </div>

                    <h2>Set a new password</h2>
                    <p class="iv-lead">Your account used a one-time password. Choose a new one to continue into IV Classes.</p>

                    @if ($errors->any())
                        <div class="iv-alert" role="alert">
                            Please fix the following:
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.force.update') }}" id="ivForcePwdForm">
                        @csrf

                        <div class="iv-field">
                            <label for="password">New password</label>
                            <div class="iv-input-wrap">
                                <svg class="iv-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                                </svg>
                                <input id="password" type="password" name="password" required autofocus autocomplete="new-password" placeholder="Create a strong password" minlength="8">
                                <button type="button" class="iv-toggle-eye" data-target="password" aria-label="Show password">
                                    <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                                </button>
                            </div>
                            @error('password')
                                <ul class="iv-errors"><li>{{ $message }}</li></ul>
                            @enderror
                        </div>

                        <div class="iv-field">
                            <label for="password_confirmation">Confirm password</label>
                            <div class="iv-input-wrap">
                                <svg class="iv-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M9 12l2 2 4-4"></path>
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                                </svg>
                                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter new password" minlength="8">
                                <button type="button" class="iv-toggle-eye" data-target="password_confirmation" aria-label="Show password">
                                    <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                                </button>
                            </div>
                        </div>

                        <p class="iv-hint">Use at least 8 characters. You’ll use this password for every future login.</p>

                        <button type="submit" class="iv-submit" id="ivForcePwdBtn">
                            <span class="iv-btn-label">Save &amp; continue</span>
                            <span class="iv-btn-loader" aria-live="polite">
                                <svg class="iv-edu-loader-cap" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                    <path d="M5 13.18V17c0 1.66 3.13 3 7 3s7-1.34 7-3v-3.82l-7 3.82-7-3.82z" opacity="0.85"/>
                                </svg>
                                Saving…
                            </span>
                        </button>
                    </form>

                    @if (Route::has('password.request'))
                        <a class="iv-footer-link" href="{{ route('password.request') }}">
                            Prefer a reset link? <span>Forgot password</span>
                        </a>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            document.querySelectorAll('.iv-toggle-eye').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.getAttribute('data-target'));
                    if (!input) return;
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.classList.toggle('is-shown', show);
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });

            var form = document.getElementById('ivForcePwdForm');
            var btn = document.getElementById('ivForcePwdBtn');
            if (!form || !btn) return;
            form.addEventListener('submit', function () {
                if (btn.classList.contains('is-loading')) return;
                btn.classList.add('is-loading');
                btn.disabled = true;
            });
        })();
    </script>
</body>
</html>
