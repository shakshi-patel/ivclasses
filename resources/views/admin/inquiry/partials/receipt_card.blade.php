@php
    $institute = $inquiry->institute;
    $terms = $institute?->feesReceiptTerms() ?? [];
    $code = $inquiry->inquiry_code ?? ('#'.$inquiry->id);
    $receiptDate = $inquiry->inquiry_date?->format('d M Y') ?? $inquiry->created_at?->format('d M Y') ?? '—';
@endphp

<div class="iv-receipt" id="ivReceiptPrintArea">
    @include('admin.partials.iv_receipt_brand_header', [
        'institute' => $institute,
        'docTitle' => 'Inquiry Receipt',
        'metaRows' => [
            ['label' => 'Inquiry Code', 'value' => $code],
            ['label' => 'Date', 'value' => $receiptDate],
            ['label' => 'Branch', 'value' => $inquiry->branch?->name ?? '—'],
        ],
    ])

    <div class="iv-receipt__body">
        <div class="iv-receipt__section-title">Inquiry Details</div>
        <div class="iv-receipt__grid">
            <div><span>Student Name</span><strong>{{ $inquiry->student_name }}</strong></div>
            <div><span>Standard</span><strong>{{ $inquiry->standard?->name ?? '—' }}</strong></div>
            <div><span>Father Name</span><strong>{{ $inquiry->father_name ?: ($inquiry->parent_name ?? '—') }}</strong></div>
            <div><span>Contact</span><strong>{{ $inquiry->phone ?? '—' }}</strong></div>
            @if ($inquiry->phone_2)
                <div><span>Contact 2</span><strong>{{ $inquiry->phone_2 }}</strong></div>
            @endif
            <div><span>Email</span><strong>{{ $inquiry->email ?: '—' }}</strong></div>
            <div><span>Status</span><strong>{{ str_replace('_', ' ', ucfirst($inquiry->status ?? '—')) }}</strong></div>
            <div><span>School</span><strong>{{ $inquiry->school_name ?: '—' }}</strong></div>
            <div><span>Form Fees</span><strong>@if ($inquiry->form_fees !== null)₹{{ number_format((float) $inquiry->form_fees, 2) }}@else—@endif</strong></div>
            <div><span>Source</span><strong>{{ $inquiry->source ? ucfirst($inquiry->source) : '—' }}</strong></div>
            <div><span>Next Follow Up</span><strong>{{ $inquiry->next_follow_up_at?->format('d M Y') ?? '—' }}</strong></div>
        </div>

        @if ($inquiry->notes)
            <div class="iv-receipt__note"><strong>Notes:</strong> {{ $inquiry->notes }}</div>
        @endif

        @if (count($terms))
            <div class="iv-receipt__section-title">Terms &amp; Conditions</div>
            <ol class="iv-receipt__terms">
                @foreach ($terms as $term)
                    <li>{{ $term }}</li>
                @endforeach
            </ol>
        @endif

        <div class="iv-receipt__signs">
            <div class="iv-receipt__sign">
                @if ($institute?->stampUrl())
                    <img src="{{ $institute->stampUrl() }}" alt="Stamp">
                @else
                    <div class="iv-receipt__sign-placeholder"></div>
                @endif
                <span>Office Stamp</span>
            </div>
            <div class="iv-receipt__sign">
                @if ($institute?->signatureUrl())
                    <img src="{{ $institute->signatureUrl() }}" alt="Signature">
                @else
                    <div class="iv-receipt__sign-placeholder"></div>
                @endif
                <span>Authorized Signature</span>
            </div>
        </div>
    </div>

    <div class="iv-receipt__foot">
        This is a computer-generated inquiry receipt · We look forward to welcoming you
    </div>
</div>
