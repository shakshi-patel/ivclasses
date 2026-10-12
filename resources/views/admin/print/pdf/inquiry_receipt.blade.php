<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inquiry Receipt — {{ $inquiry->inquiry_code ?? $inquiry->id }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 9mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2a28; margin: 0; padding: 0; }
        .wrap { border: 1px solid #d7e4e0; border-radius: 10px; overflow: hidden; }
        .banner { background: #0f5c4d; color: #fff; padding: 14px 14px 10px; }
        .banner-table { width: 100%; border-collapse: collapse; }
        .banner-table td { vertical-align: top; }
        .logo-box {
            width: 58px; height: 58px; background: #fff; border: 2px solid #d9a441; border-radius: 10px;
            text-align: center; line-height: 54px; overflow: hidden;
        }
        .logo { max-height: 48px; max-width: 48px; vertical-align: middle; }
        .inst-name { font-size: 15px; font-weight: 700; color: #fff; margin: 0 0 3px 10px; }
        .tag {
            display: inline-block; margin: 0 0 4px 10px; background: #d9a441; color: #1f2a28;
            font-size: 8px; font-weight: 700; padding: 2px 7px; border-radius: 10px;
        }
        .contact { font-size: 8.2px; color: rgba(255,255,255,0.88); margin-left: 10px; line-height: 1.4; }
        .meta-box {
            width: 185px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22);
            border-radius: 8px; padding: 8px 9px;
        }
        .doc-title {
            background: #d9a441; color: #1f2a28; text-align: center; font-size: 9.5px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase; padding: 5px 6px; border-radius: 5px; margin-bottom: 6px;
        }
        .meta-box div { margin-bottom: 3px; font-size: 8.5px; color: rgba(255,255,255,0.85); }
        .meta-box strong { color: #fff; float: right; }
        .gold { height: 4px; background: linear-gradient(90deg, transparent, #d9a441, transparent); }
        .body { padding: 12px 12px 10px; background: #fff; }
        .section {
            margin: 2px 0 7px; color: #0a453a; font-size: 9.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.04em; border-left: 3px solid #d9a441; padding-left: 7px;
        }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid td { width: 50%; padding: 3px; vertical-align: top; }
        .cell {
            background: #f7faf8; border: 1px solid #d7e4e0; border-radius: 7px; padding: 7px 8px;
        }
        .lbl { display: block; font-size: 7.5px; color: #6b7a76; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 700; margin-bottom: 1px; }
        .val { font-size: 10px; font-weight: 700; color: #0a453a; }
        .note {
            margin: 0 0 10px; padding: 7px 10px; background: #f7faf8; border: 1px solid #d7e4e0;
            border-radius: 7px; font-size: 8.5px; color: #4a5754;
        }
        .terms {
            margin: 0 0 10px; padding: 8px 10px 8px 18px; background: #fffdf8; border: 1px solid #ecd9a8;
            border-radius: 8px; color: #4a5754; font-size: 8.5px; line-height: 1.45;
        }
        .terms li { margin-bottom: 2px; }
        .signs { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .signs td { width: 50%; text-align: center; vertical-align: bottom; padding: 4px; }
        .sign-box { border: 1px dashed #b7cbc5; border-radius: 8px; padding: 10px 8px 8px; }
        .signs img { max-height: 54px; max-width: 120px; }
        .signs .cap {
            display: block; margin-top: 5px; padding-top: 4px; border-top: 1px solid #d7e4e0;
            font-size: 8px; font-weight: 700; color: #0a453a; text-transform: uppercase; letter-spacing: 0.03em;
        }
        .foot {
            margin-top: 10px; padding: 8px; text-align: center; font-size: 7.5px; color: #6b7a76;
            background: #f4faf8; border-top: 1px solid #d7e4e0;
        }
    </style>
</head>
<body>
@php
    $institute = $inquiry->institute;
    $terms = $institute?->feesReceiptTerms() ?? [];
    $code = $inquiry->inquiry_code ?? ('#'.$inquiry->id);
    $receiptDate = $inquiry->inquiry_date?->format('d M Y') ?? $inquiry->created_at?->format('d M Y') ?? '—';
    $logoFile = ($institute?->logo_path && is_file(public_path('storage/'.$institute->logo_path))) ? public_path('storage/'.$institute->logo_path) : null;
    $stampFile = ($institute?->stamp_path && is_file(public_path('storage/'.$institute->stamp_path))) ? public_path('storage/'.$institute->stamp_path) : null;
    $signFile = ($institute?->signature_path && is_file(public_path('storage/'.$institute->signature_path))) ? public_path('storage/'.$institute->signature_path) : null;
@endphp

<div class="wrap">
    <div class="banner">
        <table class="banner-table">
            <tr>
                <td style="width: 62%;">
                    <table style="border-collapse:collapse;"><tr>
                        <td>
                            <div class="logo-box">
                                @if ($logoFile)<img src="{{ $logoFile }}" class="logo" alt="Logo">@else<span style="color:#0f5c4d;font-weight:700;">{{ strtoupper(substr($institute?->name ?? 'IV', 0, 2)) }}</span>@endif
                            </div>
                        </td>
                        <td>
                            <div class="inst-name">{{ $institute?->name ?? config('app.name') }}</div>
                            @if ($institute && ! empty($institute->settings_json['tagline'] ?? null))
                                <div class="tag">{{ $institute->settings_json['tagline'] }}</div>
                            @endif
                            <div class="contact">
                                @if ($institute?->address){{ $institute->address }}@endif
                                @if ($institute?->city){{ $institute->address ? ', ' : '' }}{{ $institute->city }}@endif
                                @if ($institute?->state), {{ $institute->state }}@endif
                                @if ($institute?->pincode) — {{ $institute->pincode }}@endif
                                <br>
                                @if ($institute?->phone)Phone: {{ $institute->phone }}@endif
                                @if ($institute?->email){{ $institute->phone ? ' | ' : '' }}Email: {{ $institute->email }}@endif
                                @if ($institute?->gstin){{ ($institute->phone || $institute->email) ? ' | ' : '' }}GSTIN: {{ $institute->gstin }}@endif
                            </div>
                        </td>
                    </tr></table>
                </td>
                <td style="width: 38%;" align="right">
                    <div class="meta-box" style="display:inline-block;text-align:left;">
                        <div class="doc-title">Inquiry Receipt</div>
                        <div>Inquiry Code <strong>{{ $code }}</strong><div style="clear:both;"></div></div>
                        <div>Date <strong>{{ $receiptDate }}</strong><div style="clear:both;"></div></div>
                        <div>Branch <strong>{{ $inquiry->branch?->name ?? '—' }}</strong><div style="clear:both;"></div></div>
                    </div>
                </td>
            </tr>
        </table>
        <div class="gold"></div>
    </div>

    <div class="body">
        <div class="section">Inquiry Details</div>
        <table class="grid">
            <tr>
                <td><div class="cell"><span class="lbl">Student Name</span><span class="val">{{ $inquiry->student_name }}</span></div></td>
                <td><div class="cell"><span class="lbl">Standard</span><span class="val">{{ $inquiry->standard?->name ?? '—' }}</span></div></td>
            </tr>
            <tr>
                <td><div class="cell"><span class="lbl">Father Name</span><span class="val">{{ $inquiry->father_name ?: ($inquiry->parent_name ?? '—') }}</span></div></td>
                <td><div class="cell"><span class="lbl">Contact</span><span class="val">{{ $inquiry->phone ?? '—' }}</span></div></td>
            </tr>
            <tr>
                <td><div class="cell"><span class="lbl">Email</span><span class="val">{{ $inquiry->email ?: '—' }}</span></div></td>
                <td><div class="cell"><span class="lbl">Status</span><span class="val">{{ str_replace('_', ' ', ucfirst($inquiry->status ?? '—')) }}</span></div></td>
            </tr>
            <tr>
                <td><div class="cell"><span class="lbl">School</span><span class="val">{{ $inquiry->school_name ?: '—' }}</span></div></td>
                <td><div class="cell"><span class="lbl">Form Fees</span><span class="val">@if ($inquiry->form_fees !== null)Rs. {{ number_format((float) $inquiry->form_fees, 2) }}@else—@endif</span></div></td>
            </tr>
            <tr>
                <td><div class="cell"><span class="lbl">Source</span><span class="val">{{ $inquiry->source ? ucfirst($inquiry->source) : '—' }}</span></div></td>
                <td><div class="cell"><span class="lbl">Next Follow Up</span><span class="val">{{ $inquiry->next_follow_up_at?->format('d M Y') ?? '—' }}</span></div></td>
            </tr>
        </table>

        @if ($inquiry->notes)
            <div class="note"><strong>Notes:</strong> {{ $inquiry->notes }}</div>
        @endif

        @if (count($terms))
            <div class="section">Terms &amp; Conditions</div>
            <ol class="terms">
                @foreach ($terms as $term)
                    <li>{{ $term }}</li>
                @endforeach
            </ol>
        @endif

        <table class="signs">
            <tr>
                <td>
                    <div class="sign-box">
                        @if ($stampFile)<img src="{{ $stampFile }}" alt="Stamp">@endif
                        <span class="cap">Office Stamp</span>
                    </div>
                </td>
                <td>
                    <div class="sign-box">
                        @if ($signFile)<img src="{{ $signFile }}" alt="Signature">@endif
                        <span class="cap">Authorized Signature</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="foot">This is a computer-generated inquiry receipt · We look forward to welcoming you · {{ config('app.name') }} · {{ now()->format('d M Y, h:i A') }}</div>
</div>
</body>
</html>
