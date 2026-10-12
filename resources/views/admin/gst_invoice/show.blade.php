@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-receipt"></i> GST Invoice — {{ $gstInvoice->invoice_no }}</h4>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i> Print
                    </button>
                </div>

                <div class="fees-panel mt-4">
                    <div class="fees-panel-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="fw-semibold" style="color: var(--iv-primary);">{{ $gstInvoice->institute?->name }}</h6>
                                <p class="mb-1 small text-muted">{{ $gstInvoice->institute?->address }}</p>
                                <p class="mb-0 small"><strong>GSTIN:</strong> {{ $gstInvoice->gstin ?? '—' }}</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1"><strong>Invoice No:</strong> {{ $gstInvoice->invoice_no }}</p>
                                <p class="mb-0"><strong>Date:</strong> {{ $gstInvoice->created_at?->format('d M Y') }}</p>
                            </div>
                            <div class="col-12"><hr></div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Student:</strong> {{ $gstInvoice->student?->name }}</p>
                                <p class="mb-1"><strong>Father:</strong> {{ $gstInvoice->student?->parent?->father_name ?? '—' }}</p>
                                <p class="mb-0"><strong>Standard:</strong> {{ $gstInvoice->student?->standard?->name ?? '—' }}</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1"><strong>Branch:</strong> {{ $gstInvoice->branch?->name ?? '—' }}</p>
                                <p class="mb-0"><strong>Receipt:</strong> {{ $gstInvoice->payment?->receipt_no ?? '—' }}</p>
                            </div>
                            <div class="col-12">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Taxable Amount</th>
                                            <th>CGST (9%)</th>
                                            <th>SGST (9%)</th>
                                            <th>IGST</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>₹{{ number_format((float) $gstInvoice->taxable, 2) }}</td>
                                            <td>₹{{ number_format((float) $gstInvoice->cgst, 2) }}</td>
                                            <td>₹{{ number_format((float) $gstInvoice->sgst, 2) }}</td>
                                            <td>₹{{ number_format((float) $gstInvoice->igst, 2) }}</td>
                                            <td class="fw-bold">₹{{ number_format((float) $gstInvoice->total, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.gst_invoice') }}" class="btn btn-outline-secondary mt-3">
                    <i class="bx bx-left-arrow-alt me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
@endsection
