@php
    $institute = $student->institute;
    $terms = $institute?->feesReceiptTerms() ?? [];
    $receiptDate = $student->admission_date?->format('d M Y') ?? $student->created_at?->format('d M Y') ?? now()->format('d M Y');
@endphp

<div class="iv-receipt" id="ivReceiptPrintArea">
    @include('admin.partials.iv_receipt_brand_header', [
        'institute' => $institute,
        'docTitle' => 'Student Receipt',
        'metaRows' => [
            ['label' => 'Admission No.', 'value' => $student->admission_no ?? '—'],
            ['label' => 'Date', 'value' => $receiptDate],
            ['label' => 'Branch', 'value' => $student->branch?->name ?? '—'],
        ],
    ])

    <div class="iv-receipt__body">
        <div class="iv-receipt__section-title">Student Details</div>
        <div class="iv-receipt__grid">
            <div><span>Student Name</span><strong>{{ $student->name }}</strong></div>
            <div><span>Standard</span><strong>{{ $student->standard?->name ?? '—' }}</strong></div>
            <div><span>Father Name</span><strong>{{ $student->parent?->father_name ?? '—' }}</strong></div>
            <div><span>Contact</span><strong>{{ $student->parent?->phone ?? '—' }}</strong></div>
            <div><span>Gender</span><strong>{{ $student->gender ? ucfirst($student->gender) : '—' }}</strong></div>
            <div><span>Status</span><strong>{{ ucfirst($student->status ?? '—') }}</strong></div>
            <div><span>School</span><strong>{{ $student->school_name ?: '—' }}</strong></div>
            <div><span>Form Fees</span><strong>@if ($student->form_fees !== null)₹{{ number_format((float) $student->form_fees, 2) }}@else—@endif</strong></div>
        </div>

        <div class="iv-receipt__section-title">Active Batches</div>
        <table class="iv-receipt__table">
            <thead>
                <tr>
                    <th style="width: 56px;">Sr.</th>
                    <th>Batch</th>
                    <th>Subject</th>
                    <th style="width: 90px;">Roll No.</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($student->enrollments as $en)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $en->batch?->name ?? '—' }}</td>
                        <td>{{ $en->subject?->name ?? ($en->batch?->subjects?->pluck('name')->filter()->implode(', ') ?: '—') }}</td>
                        <td>{{ $en->roll_no ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No active enrollments</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

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
        This is a computer-generated student receipt · Please retain for your records
    </div>
</div>
