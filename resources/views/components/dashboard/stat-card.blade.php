@props([
    'label',
    'value',
    'icon' => 'bx-bar-chart-alt-2',
    'tone' => 'primary',
    'withChange' => false,
    'change' => null,
    'changeLabel' => 'vs last month',
    'href' => null,
])

{{-- change: % vs previous period; null with withChange = no baseline (shown as "New") --}}
@php
    $tag = $href ? 'a' : 'div';
    $hasChange = (bool) $withChange;
    $trend = $change === null ? 'new' : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'));
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['ivd-stat', 'ivd-tone-' . $tone]) }}>
    <div class="ivd-stat__head">
        <span class="ivd-stat__label">{{ $label }}</span>
        <span class="ivd-stat__icon"><i class="bx {{ $icon }}"></i></span>
    </div>
    <div class="ivd-stat__value">{{ $value }}</div>
    <div class="ivd-stat__foot">
        @if ($hasChange)
            <span class="ivd-trend ivd-trend--{{ $trend }}">
                @if ($trend === 'new')
                    <i class="bx bx-star"></i> New
                @else
                    <i class="bx {{ $trend === 'up' ? 'bx-trending-up' : ($trend === 'down' ? 'bx-trending-down' : 'bx-minus') }}"></i>
                    {{ abs($change) }}%
                @endif
            </span>
            <span class="ivd-stat__meta">{{ $changeLabel }}</span>
        @endif
        {{ $slot }}
    </div>
</{{ $tag }}>
