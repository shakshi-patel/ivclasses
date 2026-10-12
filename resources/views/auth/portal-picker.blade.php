<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name', 'IV Classes') }}</title>
    <link rel="stylesheet" href="{{ asset('css/iv-tokens.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Outfit", system-ui, sans-serif;
            color: var(--iv-text);
            min-height: 100vh;
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(var(--iv-primary-rgb), 0.16), transparent 55%),
                linear-gradient(160deg, #f7f3ea 0%, #eef6f3 42%, #f8f1e0 100%);
            padding: 2rem 1rem;
        }
        .wrap { max-width: 980px; margin: 0 auto; }
        .top { text-align: center; margin-bottom: 2rem; }
        .top img { height: 52px; margin-bottom: 1rem; }
        .top h1 { font-family: "Fraunces", serif; font-size: clamp(1.8rem, 4vw, 2.4rem); margin-bottom: 0.5rem; }
        .top p { color: var(--iv-muted, #5f6b66); max-width: 520px; margin: 0 auto; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .card {
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 18px 40px rgba(var(--iv-primary-dark-rgb), 0.08);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .card h2 { font-size: 1.15rem; }
        .card p { color: var(--iv-muted, #5f6b66); font-size: 0.92rem; line-height: 1.5; flex: 1; }
        .badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--iv-primary);
            background: rgba(var(--iv-primary-rgb), 0.08);
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            width: fit-content;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            padding: 0.75rem 1rem;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--iv-primary), var(--iv-primary-dark));
            color: #fff;
        }
        .btn-outline {
            border-color: rgba(var(--iv-primary-rgb), 0.25);
            color: var(--iv-primary-dark);
            background: #fff;
        }
        .footer { text-align: center; margin-top: 1.5rem; }
        .footer a { color: var(--iv-primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a href="{{ route('home') }}">
                <img src="{{ asset('website-assets/images/logo.png') }}" alt="IV Classes">
            </a>
            <h1>Choose your portal</h1>
            <p>Each role signs in through a separate secure entry point. Pick the workspace that matches your account.</p>
        </div>

        <div class="grid">
            <article class="card">
                <span class="badge">Institute</span>
                <h2>Institute CRM</h2>
                <p>Institute admin, accountant, receptionist and teachers — manage batches, fees and attendance.</p>
                <a href="{{ route('login.institute') }}" class="btn btn-primary">Institute sign in</a>
            </article>

            <article class="card">
                <span class="badge">Principal</span>
                <h2>Principal</h2>
                <p>Separate login for Principal role — academics and ops for assigned branch(es).</p>
                <a href="{{ route('login.principal') }}" class="btn btn-primary">Principal sign in</a>
            </article>

            <article class="card">
                <span class="badge">Branch</span>
                <h2>Branch Manager</h2>
                <p>Separate login for Branch Managers — day-to-day control of one branch only.</p>
                <a href="{{ route('login.branch') }}" class="btn btn-primary">Branch sign in</a>
            </article>

            <article class="card">
                <span class="badge">Parent</span>
                <h2>Parent portal</h2>
                <p>View homework, notices, attendance and exam results for your child.</p>
                <a href="{{ route('login.parent') }}" class="btn btn-primary">Parent sign in</a>
            </article>

            <article class="card">
                <span class="badge">Platform</span>
                <h2>Platform admin</h2>
                <p>IV Classes internal team — institutes, demos, billing and platform configuration.</p>
                <a href="{{ route('login.platform') }}" class="btn btn-outline">Platform sign in</a>
            </article>
        </div>

        <div class="footer">
            <a href="{{ route('home') }}">← Back to website</a>
        </div>
    </div>
</body>
</html>
