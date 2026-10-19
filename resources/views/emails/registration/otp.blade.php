<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Flow verification code</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px; background: #f7f8f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px 24px; border: 1px solid #e6ebe9;">
        <h2 style="margin: 0 0 8px; color: #0f5c4d;">Verify your email</h2>
        <p style="margin: 0 0 18px; color: #5b6661;">
            Hi {{ $adminName }}, use the code below to verify
            <strong style="color: #1f2926;">{{ $instituteName }}</strong> and activate your Class Flow trial.
        </p>

        <div style="margin-bottom: 18px; padding: 20px 16px; border-radius: 10px; background: #f3faf7; border: 1px solid #d5ebe3; text-align: center;">
            <p style="margin: 0 0 10px; font-weight: 700; color: #0f5c4d; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;">Your verification code</p>
            <div style="font-family: Consolas, Monaco, monospace; font-size: 34px; font-weight: 700; color: #0f5c4d; letter-spacing: 10px;">{{ $otp }}</div>
        </div>

        <div style="margin: 0 0 8px; padding: 12px 14px; border-radius: 10px; background: #fff5f5; border: 1px solid #f0c9c9;">
            <p style="margin: 0; font-size: 14px; color: #9b2c2c;">
                <strong>Important:</strong> This code expires in {{ $expiresMinutes }} minutes.
                Do not share it with anyone — Class Flow staff will never ask for it.
            </p>
        </div>

        <p style="margin: 18px 0 0; color: #5b6661; font-size: 13px;">
            If you did not request this, you can ignore this email.
        </p>
        <p style="margin: 12px 0 0; font-weight: 700; color: #0f5c4d;">Class Flow</p>
    </div>
</body>
</html>
