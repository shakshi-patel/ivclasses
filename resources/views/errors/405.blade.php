<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Method not allowed — {{ config('brand.name', 'IVClasses') }}</title>
    <link rel="stylesheet" href="{{ asset('css/iv-tokens.css') }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            color: var(--iv-text, #1f2933);
            background:
                radial-gradient(ellipse 70% 45% at 50% -10%, rgba(var(--iv-primary-rgb, 15, 92, 77), 0.12), transparent 55%),
                var(--iv-bg, #fbf8f1);
            padding: 24px;
        }
        .card {
            width: min(100%, 480px);
            background: #fff;
            border: 1px solid rgba(var(--iv-primary-rgb, 15, 92, 77), 0.12);
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 40, 35, 0.08);
            padding: 36px 32px;
            text-align: center;
        }
        .code {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(var(--iv-primary-rgb, 15, 92, 77), 0.1);
            color: var(--iv-primary, #0f5c4d);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.06em;
        }
        h1 {
            margin: 14px 0 8px;
            font-size: 1.55rem;
            letter-spacing: -0.03em;
            color: var(--iv-primary-dark, #08483d);
        }
        p {
            margin: 0 0 22px;
            color: var(--iv-text-secondary, #667085);
            line-height: 1.55;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--iv-primary, #0f5c4d);
            color: #fff;
        }
        .btn-ghost {
            border: 1.5px solid rgba(var(--iv-primary-rgb, 15, 92, 77), 0.28);
            color: var(--iv-primary, #0f5c4d);
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="code">405</span>
        <h1>This action cannot be opened here</h1>
        <p>
            That address only accepts a form submit, not a direct browser open.
            Go back to the list or dashboard and continue from there.
        </p>
        <div class="actions">
            @auth
                <a class="btn-primary" href="{{ $adminUrl }}">Go to dashboard</a>
                @if (! empty($indexUrl))
                    <a class="btn-ghost" href="{{ $indexUrl }}">Open related page</a>
                @endif
            @else
                <a class="btn-primary" href="{{ url('/') }}">Go to home</a>
                <a class="btn-ghost" href="{{ route('login') }}">Sign in</a>
            @endauth
        </div>
    </div>
</body>
</html>
