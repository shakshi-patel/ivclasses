@php
    $student = $payment->student;
    $invoice = $payment->invoice;
    $institute = $student?->institute;
    $terms = $institute?->feesReceiptTerms() ?? [];
    $receiptNo = $payment->receipt_no ?? ('#'.$payment->id);
    $receiptDate = $payment->payment_date?->format('d M Y') ?? $payment->created_at?->format('d M Y') ?? '—';
@endphp

<div class="iv-receipt" id="ivReceiptPrintArea">
    @include('admin.partials.iv_receipt_brand_header', [
        'institute' => $institute,
        'docTitle' => 'Fee Receipt',
        'metaRows' => [
            ['label' => 'Receipt No.', 'value' => $receiptNo],
            ['label' => 'Date', 'value' => $receiptDate],
            ['label' => 'Branch', 'value' => $student?->branch?->name ?? '—'],
        ],
    ])

    <div class="iv-receipt__body">
        <div class="iv-receipt__section-title">Student Details</div>
        <div class="iv-receipt__grid">
            <div><span>Student Name</span><strong>{{ $student?->name ?? '—' }}</strong></div>
            <div><span>Admission No.</span><strong>{{ $student?->admission_no ?? '—' }}</strong></div>
            <div><span>Father Name</span><strong>{{ $student?->parent?->father_name ?? '—' }}</strong></div>
            <div><span>Contact</span><strong>{{ $student?->parent?->phone ?? '—' }}</strong></div>
            <div><span>Standard</span><strong>{{ $student?->standard?->name ?? '—' }}</strong></div>
            <div><span>Batch</span><strong>{{ $invoice?->enrollment?->batch?->name ?? ($invoice?->title ?? '—') }}</strong></div>
        </div>

        <div class="iv-receipt__section-title">Payment Summary</div>
        <div class="iv-receipt__amount-card">
            <div>
                <div class="lbl">Amount Paid</div>
                <div class="small text-muted mt-1">{{ $invoice?->title ?? 'Fee Payment' }} · {{ $payment->payment_method ?: '—' }}</div>
            </div>
            <div class="val">₹{{ number_format((float) $payment->amount, 2) }}</div>
        </div>

        <table class="iv-receipt__table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Method</th>
                    <th>Collected By</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice?->title ?? 'Fee Payment' }}</td>
                    <td>{{ $payment->payment_method ?: '—' }}</td>
                    <td>{{ $payment->collector?->name ?? '—' }}</td>
                    <td class="text-end fw-bold">₹{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($payment->note)
            <div class="iv-receipt__note"><strong>Note:</strong> {{ $payment->note }}</div>
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
        This is a computer-generated fee receipt · Thank you for your payment
    </div>
</div>
