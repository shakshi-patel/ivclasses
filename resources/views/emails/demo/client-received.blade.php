<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Demo request received</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
    <h2 style="margin-bottom: 8px; color: #0f5c4d;">We received your demo request</h2>
    <p>
        Thanks for booking a demo with IV Classes.
        Our team will review this slot and confirm shortly.
    </p>

    <p>
        <strong>Inquiry Code:</strong> {{ $inquiry->inquiry_code }}<br>
        <strong>Institute:</strong> {{ $inquiry->institute_name }}<br>
        <strong>Preferred Date:</strong> {{ $dateLabel }}<br>
        <strong>Preferred Time:</strong> {{ $timeLabel }}<br>
        <strong>Timezone:</strong> {{ $inquiry->timezone }}
    </p>

    <p style="color: #5b6661;">
        You will get another email once the demo is confirmed by our team,
        with the Google Meet link.
    </p>

    <p>IV Classes</p>
</body>
</html>
