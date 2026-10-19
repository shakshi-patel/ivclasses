<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Flow login details</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px; background: #f7f8f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px 24px; border: 1px solid #e6ebe9;">
        <h2 style="margin: 0 0 8px; color: #0f5c4d;">Welcome to Class Flow</h2>
        <p style="margin: 0 0 18px; color: #5b6661;">
            Your institute <strong style="color: #1f2926;">{{ $credentials['institute_name'] }}</strong>
            is ready. Below are the logins created for you.
        </p>

        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #f3faf7; border: 1px solid #d5ebe3;">
            <p style="margin: 0 0 8px; font-weight: 700; color: #0f5c4d;">Institute Admin login (Web CRM)</p>
            <p style="margin: 0;">
                Name: {{ $credentials['admin_name'] }}<br>
                Email / Login ID: <strong>{{ $credentials['admin_email'] }}</strong><br>
                Temporary Password:
                <strong style="font-family: Consolas, Monaco, monospace; letter-spacing: 0.5px;">{{ $credentials['admin_password'] }}</strong>
                <br>
                Login URL:
                <a href="{{ $credentials['institute_login_url'] }}" style="color: #0f5c4d;">{{ $credentials['institute_login_url'] }}</a>
            </p>
        </div>

        @php
            $branchLogins = collect($credentials['branches'] ?? [])
                ->filter(fn ($b) => is_array($b) && ! empty($b['manager_email']))
                ->values();
            if ($branchLogins->isEmpty() && ! empty($credentials['has_separate_branch_login']) && ! empty($credentials['manager_email'])) {
                $branchLogins = collect([[
                    'branch_name' => $credentials['branch_name'] ?? 'Branch',
                    'manager_name' => $credentials['manager_name'] ?? '',
                    'manager_email' => $credentials['manager_email'],
                    'manager_password' => $credentials['manager_password'] ?? '',
                ]]);
            }
        @endphp

        @foreach ($branchLogins as $index => $branchLogin)
            <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #fffaf0; border: 1px solid #f0e2c4;">
                <p style="margin: 0 0 8px; font-weight: 700; color: #8a6418;">
                    Branch Manager login
                    @if ($branchLogins->count() > 1)
                        #{{ $index + 1 }}
                    @endif
                    (Branch portal)
                </p>
                <p style="margin: 0;">
                    Branch: {{ $branchLogin['branch_name'] ?? 'Branch' }}<br>
                    Manager: {{ $branchLogin['manager_name'] ?? '' }}<br>
                    Email / Login ID: <strong>{{ $branchLogin['manager_email'] }}</strong><br>
                    Temporary Password:
                    <strong style="font-family: Consolas, Monaco, monospace; letter-spacing: 0.5px;">{{ $branchLogin['manager_password'] ?? '' }}</strong>
                    <br>
                    Login URL:
                    <a href="{{ $credentials['branch_login_url'] }}" style="color: #8a6418;">{{ $credentials['branch_login_url'] }}</a>
                </p>
            </div>
        @endforeach

        <div style="margin: 0 0 8px; padding: 12px 14px; border-radius: 10px; background: #fff5f5; border: 1px solid #f0c9c9;">
            <p style="margin: 0; font-size: 14px; color: #9b2c2c;">
                <strong>Important:</strong>
                @if (!empty($credentials['is_temporary']))
                    These are <strong>temporary passwords</strong>. On first login you will be asked to set a new password.
                    You can also use
                    <a href="{{ $credentials['forgot_password_url'] ?? route('password.request') }}" style="color: #9b2c2c;">Forgot password</a>
                    anytime.
                @else
                    For your security, please change all passwords right after you log in for the first time.
                @endif
                Do not share this email with anyone you do not trust.
            </p>
        </div>

        <p style="margin: 18px 0 0; color: #5b6661; font-size: 13px;">
            If you did not expect this email, ignore it or contact Class Flow support.
        </p>
        <p style="margin: 12px 0 0; font-weight: 700; color: #0f5c4d;">Class Flow</p>
    </div>
</body>
</html>
