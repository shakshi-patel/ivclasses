@php
    $reportTitle = $reportTitle ?? 'Report';
    $reportSubtitle = $reportSubtitle ?? null;
@endphp
<div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 no-print">
    <div>
        <h4 class="admin-page-title mb-0">
            <i class="bx {{ $reportIcon ?? 'bx-bar-chart-alt-2' }}"></i>
            {{ $reportTitle }}
        </h4>
        @if ($reportSubtitle)
            <p class="text-muted small mb-0 mt-1">{{ $reportSubtitle }}</p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @isset($reportBackUrl)
            <a href="{{ $reportBackUrl }}" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-left-arrow-alt me-1"></i> Back
            </a>
        @endisset
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bx bx-printer me-1"></i> Print
        </button>
    </div>
</div>
<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb mb-0 admin-breadcrumb mt-2">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reports') }}">Reports</a></li>
        <li class="breadcrumb-item active">{{ $reportTitle }}</li>
    </ol>
</nav>

<div class="report-print-head d-none d-print-block mb-3">
    <h3 class="mb-1" style="color: var(--iv-primary-dark);">{{ $brandName ?? config('app.name') }}</h3>
    <h4 class="mb-0">{{ $reportTitle }}</h4>
    @if ($reportSubtitle)
        <p class="mb-0 text-muted">{{ $reportSubtitle }}</p>
    @endif
    <p class="small text-muted mb-0">Printed: {{ now()->format('d M Y, h:i A') }}</p>
</div>
