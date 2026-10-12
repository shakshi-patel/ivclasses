<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Sign in' }} · {{ config('app.name', 'IV Classes') }}</title>
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

        /* —— Full-page education stage —— */
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
        .iv-float-icon.i5 { top: 42%; left: 3%; --dur: 12s; --delay: -3s; width: 32px; height: 32px; }
        .iv-float-icon.i6 { top: 55%; right: 4%; --dur: 8.5s; --delay: -5s; width: 40px; height: 40px; }

        .iv-chalk-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--iv-secondary);
            opacity: 0.35;
            animation: ivRiseFade var(--dur, 12s) linear infinite;
            animation-delay: var(--delay, 0s);
        }

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

        /* —— Unique illustrated education panel (no CRM photo) —— */
        .iv-login-visual {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2.25rem;
            color: #f4faf8;
            background:
                linear-gradient(155deg, var(--iv-primary-dark) 0%, var(--iv-primary) 52%, #0a6b58 100%);
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
            top: 12%;
            transform: translateX(-50%);
            width: min(320px, 88%);
            height: auto;
        }

        .iv-illus-art .book-stack { animation: ivBob 7s ease-in-out infinite; transform-origin: center bottom; }
        .iv-illus-art .cap { animation: ivCapTilt 5.5s ease-in-out infinite; transform-origin: 160px 70px; }
        .iv-illus-art .orbit-ring {
            fill: none;
            stroke: rgba(var(--iv-secondary-rgb), 0.45);
            stroke-width: 1.5;
            stroke-dasharray: 6 8;
            animation: ivOrbitSpin 18s linear infinite;
            transform-origin: 160px 150px;
        }
        .iv-illus-art .formula {
            font-family: "Fraunces", Georgia, serif;
            fill: rgba(246, 232, 195, 0.75);
            font-size: 13px;
            animation: ivFadePulse 4s ease-in-out infinite;
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
            font-size: clamp(1.75rem, 2.8vw, 2.4rem);
            line-height: 1.12;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.65rem;
        }

        .iv-visual-content p {
            color: rgba(244, 250, 248, 0.86);
            font-size: 0.92rem;
            line-height: 1.6;
            max-width: 28ch;
        }

        .iv-stats {
            display: flex;
            gap: 1.1rem;
            margin-top: 1.35rem;
            flex-wrap: wrap;
        }

        .iv-stat {
            min-width: 72px;
        }

        .iv-stat strong {
            display: block;
            font-family: "Fraunces", Georgia, serif;
            font-size: 1.15rem;
            color: var(--iv-secondary-light);
            font-weight: 700;
        }

        .iv-stat span {
            font-size: 0.72rem;
            opacity: 0.75;
            letter-spacing: 0.02em;
        }

        /* —— Form panel —— */
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
            margin: 0 auto 1.5rem;
            width: 100%;
        }

        .iv-logo img {
            height: 127px;
            width: auto;
            max-width: 100%;
        }

        .iv-card h2 {
            font-family: "Fraunces", Georgia, serif;
            font-size: 1.85rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
            color: var(--iv-primary-dark);
        }

        .iv-card > .iv-lead {
            color: var(--iv-text-secondary);
            font-size: 0.92rem;
            margin-bottom: 1.55rem;
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

        .iv-input-wrap svg {
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
            padding: 0.9rem 1rem 0.9rem 2.6rem;
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

        .iv-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0.25rem 0 1.35rem;
            flex-wrap: wrap;
        }

        .iv-check {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.84rem;
            color: var(--iv-text-secondary);
            cursor: pointer;
        }

        .iv-check input {
            width: 16px;
            height: 16px;
            accent-color: var(--iv-primary);
        }

       
        /* —— Sign in + education loading —— */
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

        .iv-submit:disabled {
            cursor: wait;
            filter: none;
        }

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
            gap: 0.7rem;
        }

        .iv-submit.is-loading .iv-btn-label { display: none; }
        .iv-submit.is-loading .iv-btn-loader { display: inline-flex; }

        .iv-edu-loader {
            width: 28px;
            height: 22px;
            position: relative;
        }

        .iv-edu-loader .page {
            position: absolute;
            inset: 0;
            border-radius: 2px 6px 6px 2px;
            background: var(--iv-secondary-light);
            border: 1.5px solid rgba(var(--iv-secondary-rgb), 0.85);
            transform-origin: left center;
        }

        .iv-edu-loader .page:nth-child(1) {
            animation: ivPageFlip 1.1s ease-in-out infinite;
            z-index: 3;
        }
        .iv-edu-loader .page:nth-child(2) {
            left: 3px;
            background: #fff8e8;
            animation: ivPageFlip 1.1s ease-in-out 0.15s infinite;
            z-index: 2;
        }
        .iv-edu-loader .page:nth-child(3) {
            left: 6px;
            background: #f0e2b8;
            animation: ivPageFlip 1.1s ease-in-out 0.3s infinite;
            z-index: 1;
        }

        .iv-edu-loader-cap {
            width: 22px;
            height: 22px;
            color: var(--iv-secondary-light);
            animation: ivCapSpin 1.2s ease-in-out infinite;
        }

        .iv-edu-loader-text {
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .iv-errors {
            list-style: none;
            margin: 0.4rem 0 0;
            color: var(--iv-error);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .iv-status {
            margin-bottom: 1rem;
            padding: 0.75rem 0.9rem;
            border-radius: 0.75rem;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary);
            font-size: 0.86rem;
            font-weight: 650;
        }

        .iv-home {
            display: inline-block;
            margin-top: 1.25rem;
            font-size: 0.84rem;
            font-weight: 650;
            color: var(--iv-text-secondary);
            text-decoration: none;
        }

        .iv-home:hover { color: var(--iv-primary); }

        /* Keyframes */
        @keyframes ivBob {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-14px) rotate(2deg); }
        }
        @keyframes ivCapTilt {
            0%, 100% { transform: rotate(-4deg); }
            50% { transform: rotate(5deg); }
        }
        @keyframes ivOrbitSpin { to { transform: rotate(360deg); } }
        @keyframes ivFadePulse {
            0%, 100% { opacity: 0.45; }
            50% { opacity: 0.95; }
        }
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
        @keyframes ivRiseFade {
            0% { transform: translateY(40px); opacity: 0; }
            20% { opacity: 0.4; }
            100% { transform: translateY(-80vh); opacity: 0; }
        }
        @keyframes ivPageFlip {
            0%, 100% { transform: perspective(200px) rotateY(0deg); }
            50% { transform: perspective(200px) rotateY(-55deg); }
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
                min-height: 240px;
                padding: 1.35rem 1.4rem;
            }
            .iv-illus-art {
                top: 6%;
                width: 200px;
                opacity: 0.85;
            }
            .iv-visual-content h1 { font-size: 1.4rem; }
            .iv-visual-content p,
            .iv-stats { display: none; }
            .iv-login-panel { padding: 1.55rem 1.25rem 1.75rem; }
            .iv-float-icon.i5,
            .iv-float-icon.i6 { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .iv-float-icon,
            .iv-chalk-dot,
            .iv-stage-grid,
            .iv-illus-art .book-stack,
            .iv-illus-art .cap,
            .iv-illus-art .orbit-ring,
            .iv-illus-art .formula,
            .iv-illus-art .spark,
            .iv-illus-glow,
            .iv-illus-glow-2,
            .iv-kicker span,
            .iv-login,
            .iv-visual-content,
            .iv-submit.is-loading,
            .iv-edu-loader .page,
            .iv-edu-loader-cap {
                animation: none !important;
            }
        }
    </style>
</head>
<body>
    {{-- Animated education backdrop --}}
    <div class="iv-stage" aria-hidden="true">
        <div class="iv-stage-grid"></div>

        <div class="iv-float-icon i1">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20v2H6.5A2.5 2.5 0 0 0 4 21.5V4h2v15.5zM8 4h12v11H8V4z"/></svg>
        </div>
        <div class="iv-float-icon i2 is-gold">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 9.5L4.5 8 12 4.5 19.5 8 12 11.5zM4 10.2v5.3L12 20l8-4.5v-5.3l-8 4-8-4z"/></svg>
        </div>
        <div class="iv-float-icon i3 is-gold">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        </div>
        <div class="iv-float-icon i4">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3zm1 2.07V12h6.93A7.002 7.002 0 0 0 13 5.07z"/></svg>
        </div>
        <div class="iv-float-icon i5">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 4h14a1 1 0 0 1 1 1v2H4V5a1 1 0 0 1 1-1zm-1 5h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9zm3 2v2h8v-2H7z"/></svg>
        </div>
        <div class="iv-float-icon i6 is-gold">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8 2 5 4.5 5 8c0 4.5 4 7.5 6.2 9.1L12 22l.8-4.9C15 15.5 19 12.5 19 8c0-3.5-3-6-7-6zm0 8.5A2.5 2.5 0 1 1 12 5a2.5 2.5 0 0 1 0 5.5z"/></svg>
        </div>

        @foreach ([
            ['12%', '18%', '11s', '0s'],
            ['28%', '70%', '14s', '-3s'],
            ['55%', '25%', '13s', '-6s'],
            ['72%', '80%', '16s', '-2s'],
            ['40%', '55%', '12s', '-8s'],
            ['85%', '40%', '15s', '-4s'],
            ['8%', '60%', '10s', '-1s'],
            ['62%', '12%', '17s', '-7s'],
        ] as $d)
            <span class="iv-chalk-dot" style="left:{{ $d[0] }};bottom:{{ $d[1] }};--dur:{{ $d[2] }};--delay:{{ $d[3] }};"></span>
        @endforeach
    </div>

    <div class="iv-shell">
        <div class="iv-login">
            <aside class="iv-login-visual">
                <div class="iv-illus" aria-hidden="true">
                    <span class="iv-illus-glow"></span>
                    <span class="iv-illus-glow-2"></span>
                    <svg class="iv-illus-art" viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle class="orbit-ring" cx="160" cy="150" r="108"/>
                        <circle class="orbit-ring" cx="160" cy="150" r="78" style="animation-duration:12s;animation-direction:reverse;opacity:0.5"/>

                        {{-- Floating formulas --}}
                        <text class="formula" x="42" y="58">E = mc²</text>
                        <text class="formula" x="230" y="48" style="animation-delay:-1.2s">a² + b² = c²</text>
                        <text class="formula" x="38" y="210" style="animation-delay:-2s">∑ n = 1</text>

                        {{-- Sparks --}}
                        <circle class="spark" cx="96" cy="88" r="3"/>
                        <circle class="spark" cx="248" cy="120" r="2.5" style="animation-delay:-0.8s"/>
                        <circle class="spark" cx="210" cy="70" r="2" style="animation-delay:-1.5s"/>

                        {{-- Graduation cap --}}
                        <g class="cap">
                            <path d="M160 42 L70 84 L160 126 L250 84 Z" fill="#D9A441"/>
                            <path d="M160 42 L250 84 L160 126 L70 84 Z" fill="#F6E8C3" opacity="0.35"/>
                            <rect x="148" y="84" width="10" height="52" rx="2" fill="#08483D"/>
                            <path d="M153 136 Q175 150 198 128" stroke="#D9A441" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <circle cx="198" cy="128" r="5" fill="#D9A441"/>
                        </g>

                        {{-- Book stack --}}
                        <g class="book-stack">
                            <rect x="98" y="168" width="124" height="22" rx="4" fill="#0F5C4D" transform="rotate(-4 160 179)"/>
                            <rect x="104" y="188" width="118" height="20" rx="4" fill="#D9A441" transform="rotate(3 163 198)"/>
                            <rect x="100" y="206" width="126" height="22" rx="4" fill="#08483D"/>
                            <path d="M108 217 H214" stroke="#F6E8C3" stroke-width="1.5" opacity="0.5"/>
                            {{-- Open book on top --}}
                            <path d="M160 148 L108 162 L108 198 L160 182 Z" fill="#FBF8F1" opacity="0.95"/>
                            <path d="M160 148 L212 162 L212 198 L160 182 Z" fill="#F6E8C3"/>
                            <path d="M160 148 L160 182" stroke="#0F5C4D" stroke-width="1.5" opacity="0.4"/>
                            <path d="M118 172 H148 M118 180 H142" stroke="#0F5C4D" stroke-width="1.2" opacity="0.25"/>
                            <path d="M172 172 H202 M172 180 H196" stroke="#0F5C4D" stroke-width="1.2" opacity="0.25"/>
                        </g>

                        {{-- Chalkboard strip --}}
                        <rect x="70" y="238" width="180" height="28" rx="6" fill="rgba(8,72,61,0.55)" stroke="rgba(217,164,65,0.35)"/>
                        <text x="160" y="256" text-anchor="middle" fill="#F6E8C3" font-size="11" font-family="Outfit, sans-serif" font-weight="600" letter-spacing="1.5">CLASS IN SESSION</text>
                    </svg>
                </div>

                <div class="iv-visual-content">
                    <p class="iv-kicker"><span></span> {{ $kicker ?? 'Institute OS' }}</p>
                    <h1>
                        @foreach (($visualTitleLines ?? ['Where coaching', 'becomes clarity.']) as $i => $line)
                            @if ($i > 0)<br>@endif{{ $line }}
                        @endforeach
                    </h1>
                    <p>{{ $visualLead ?? 'One calm workspace for batches, fees, attendance and parent trust.' }}</p>
                    <div class="iv-stats">
                        <div class="iv-stat"><strong>Batches</strong><span>Live control</span></div>
                        <div class="iv-stat"><strong>Fees</strong><span>Clear ledger</span></div>
                        <div class="iv-stat"><strong>Parents</strong><span>In the loop</span></div>
                    </div>
                </div>
            </aside>

            <main class="iv-login-panel">
                <div class="iv-card">
                    <a href="{{ route('home') }}" class="iv-logo">
                        @if (! empty($tenant?->logo_path))
                            <img src="{{ asset('storage/'.$tenant->logo_path) }}" alt="{{ $tenant->name }}">
                        @else
                            <img src="{{ asset('website-assets/images/logo.png') }}" alt="IV Classes">
                        @endif
                    </a>
                    <h2>{{ $heading ?? 'Welcome back' }}</h2>
                    <p class="iv-lead">{{ $lead ?? 'Sign in to run your institute from one desk.' }}</p>

                    @if (! ($tenant ?? null) && ($portal ?? null)?->value !== 'platform')
                        <p class="iv-lead" style="margin-top: -0.75rem; font-size: 0.82rem;">
                            <a href="{{ route('login') }}" style="color: var(--iv-primary); text-decoration: none;">← Choose another portal</a>
                        </p>
                    @endif

                    @if (session('status'))
                        <div class="iv-status">{{ session('status') }}</div>
                    @endif

                    @if (session('success'))
                        <div class="iv-status">{{ session('success') }}</div>
            @endif

                    <form method="POST" action="{{ $formAction ?? route('login.institute.store') }}" id="ivLoginForm">
                        @csrf

                        <div class="iv-field">
                            <label for="email">Email</label>
                            <div class="iv-input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 7l9 6 9-6"></path>
                                </svg>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@institute.com">
                            </div>
                            @error('email')
                                <ul class="iv-errors"><li>{{ $message }}</li></ul>
                            @enderror
                        </div>

                        <div class="iv-field">
                            <label for="password">Password</label>
                            <div class="iv-input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                                </svg>
                                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                            </div>
                            @error('password')
                                <ul class="iv-errors"><li>{{ $message }}</li></ul>
                            @enderror
                        </div>

                        <div class="iv-row">
                            <label for="remember_me" class="iv-check">
                                <input id="remember_me" type="checkbox" name="remember">
                                Remember me
                            </label>
                            <!-- @if (Route::has('password.request'))
                                <a class="iv-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                            @endif -->
                        </div>

                        <button type="submit" class="iv-submit" id="ivSignInBtn">
                            <span class="iv-btn-label">Sign in</span>
                            <span class="iv-btn-loader" aria-live="polite">
                                <!-- <span class="iv-edu-loader" aria-hidden="true">
                                    <span class="page"></span>
                                    <span class="page"></span>
                                    <span class="page"></span>
                                </span> -->
                                <svg class="iv-edu-loader-cap" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                    <path d="M5 13.18V17c0 1.66 3.13 3 7 3s7-1.34 7-3v-3.82l-7 3.82-7-3.82z" opacity="0.85"/>
                                </svg>
                                <!-- <span class="iv-edu-loader-text">Opening class…</span> -->
                            </span>
                        </button>
                    </form>

                    
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('ivLoginForm');
            var btn = document.getElementById('ivSignInBtn');
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
