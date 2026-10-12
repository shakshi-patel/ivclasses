<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Demo request update</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
    <h2 style="margin-bottom: 8px; color: #0f5c4d;">Update on your demo request</h2>
    <p>
        Thank you for your interest in IV Classes.
        Unfortunately we could not confirm the slot below.
        Please book another time that works for you.
    </p>

    <p>
        <strong>Inquiry Code:</strong> {{ $inquiry->inquiry_code }}<br>
        <strong>Requested Date:</strong> {{ $dateLabel }}<br>
        <strong>Requested Time:</strong> {{ $timeLabel }}
    </p>

    <p>
        <a href="{{ route('book-demo') }}" style="color: #0f5c4d; font-weight: 700;">
            Book another demo slot
        </a>
    </p>

    <p>IV Classes</p>
</body>
</html>
