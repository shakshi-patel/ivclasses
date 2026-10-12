<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirm demo request</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
    <h2 style="margin-bottom: 8px; color: #0f5c4d;">New demo — please confirm</h2>
    <p>A client booked a demo slot. Are you ready to take this demo?</p>

    <p>
        <strong>Inquiry Code:</strong> {{ $inquiry->inquiry_code }}<br>
        <strong>Institute:</strong> {{ $inquiry->institute_name }}<br>
        <strong>Contact Person:</strong> {{ $inquiry->contact_person_name }}<br>
        <strong>Email:</strong> {{ $inquiry->email }}<br>
        <strong>Phone:</strong> {{ $inquiry->contact_no }}<br>
        <strong>City:</strong> {{ $inquiry->city }}<br>
        <strong>Demo Date:</strong> {{ $dateLabel }}<br>
        <strong>Demo Time:</strong> {{ $timeLabel }}
    </p>

    @if ($inquiry->google_meet_link)
        <p>
            <strong>Google Meet (for you):</strong><br>
            <a href="{{ $inquiry->google_meet_link }}">{{ $inquiry->google_meet_link }}</a>
        </p>
    @endif

    <p style="margin: 28px 0 12px;">
        <a href="{{ $confirmUrl }}"
           style="display:inline-block;background:#0f5c4d;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:700;margin-right:10px;">
            Yes — Confirm Demo
        </a>
        <a href="{{ $declineUrl }}"
           style="display:inline-block;background:#fff;color:#b42318;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:700;border:2px solid #b42318;">
            No — Decline
        </a>
    </p>

    <p style="font-size: 13px; color: #5b6661;">
        Yes → client gets final confirmation + Meet link.<br>
        No → slot is released and client is notified.
    </p>
</body>
</html>
