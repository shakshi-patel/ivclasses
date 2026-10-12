{{-- Shared institute brand header for receipts --}}
@php
    $institute = $institute ?? null;
    $docTitle = $docTitle ?? 'Receipt';
    $metaRows = $metaRows ?? [];
@endphp
<div class="iv-receipt__banner">
    <div class="iv-receipt__banner-inner">
        <div class="iv-receipt__brand">
            <div class="iv-receipt__logo-wrap">
                @if ($institute?->logoUrl())
                    <img src="{{ $institute->logoUrl() }}" alt="Logo" class="iv-receipt__logo">
                @else
                    <div class="iv-receipt__logo-fallback">{{ strtoupper(substr($institute?->name ?? 'IV', 0, 2)) }}</div>
                @endif
            </div>
            <div class="iv-receipt__brand-text">
                <div class="iv-receipt__name">{{ $institute?->name ?? config('app.name') }}</div>
                @if ($institute && ! empty($institute->settings_json['tagline'] ?? null))
                    <div class="iv-receipt__tag">{{ $institute->settings_json['tagline'] }}</div>
                @endif
                <div class="iv-receipt__contact">
                    @if ($institute?->address){{ $institute->address }}@endif
                    @if ($institute?->city){{ $institute->address ? ', ' : '' }}{{ $institute->city }}@endif
                    @if ($institute?->state), {{ $institute->state }}@endif
                    @if ($institute?->pincode) — {{ $institute->pincode }}@endif
                </div>
                <div class="iv-receipt__contact iv-receipt__contact--muted">
                    @if ($institute?->phone)<span><i class="bx bx-phone"></i> {{ $institute->phone }}</span>@endif
                    @if ($institute?->email)<span><i class="bx bx-envelope"></i> {{ $institute->email }}</span>@endif
                    @if ($institute?->gstin)<span><i class="bx bx-id-card"></i> GSTIN: {{ $institute->gstin }}</span>@endif
                </div>
            </div>
        </div>
        <div class="iv-receipt__meta-box">
            <div class="iv-receipt__doc-title">{{ $docTitle }}</div>
            @foreach ($metaRows as $row)
                <div class="iv-receipt__meta-row">
                    <span>{{ $row['label'] }}</span>
                    <strong>{{ $row['value'] }}</strong>
                </div>
            @endforeach
        </div>
    </div>
    <div class="iv-receipt__gold-bar"></div>
</div>
