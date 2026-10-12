@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap no-print">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-receipt"></i> Inquiry Receipt — {{ $inquiry->inquiry_code ?? ('#'.$inquiry->id) }}</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.inquiry.receipt_pdf', $inquiry) }}" class="btn btn-sm btn-export-students">
                            <i class="bx bx-download me-1"></i> Export PDF
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bx bx-printer me-1"></i> Print
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    @include('admin.inquiry.partials.receipt_card')
                </div>
                <a href="{{ route('admin.inquiry') }}" class="btn btn-outline-secondary mt-3 no-print">
                    <i class="bx bx-left-arrow-alt me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @include('admin.partials.iv_receipt_styles')
    <style>
        @media print { .no-print { display: none !important; } .iv-receipt { box-shadow: none !important; border: none !important; } }
    </style>
@endpush
