<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Demo confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
    <h2 style="margin-bottom: 8px; color: #0f5c4d;">Your demo is confirmed</h2>
    <p>
        Great news — our team has confirmed your IV Classes demo.
        Please join using the Google Meet link below at the scheduled time.
    </p>

    <p>
        <strong>Inquiry Code:</strong> {{ $inquiry->inquiry_code }}<br>
        <strong>Institute:</strong> {{ $inquiry->institute_name }}<br>
        <strong>Demo Date:</strong> {{ $dateLabel }}<br>
        <strong>Demo Time:</strong> {{ $timeLabel }}<br>
        <strong>Timezone:</strong> {{ $inquiry->timezone }}
    </p>

    @if ($inquiry->google_meet_link)
        <p>
            <strong>Google Meet:</strong><br>
            <a href="{{ $inquiry->google_meet_link }}">{{ $inquiry->google_meet_link }}</a>
        </p>
    @endif

    <p>We look forward to speaking with you.</p>
    <p>IV Classes</p>
</body>
</html>
