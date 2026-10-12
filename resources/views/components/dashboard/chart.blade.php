@props([
    'widget',
    'height' => 300,
    'params' => [],
])

@php
    $chartQuery = http_build_query(array_filter(
        $params,
        static fn ($value) => $value !== null && $value !== ''
    ));
    // Include subdirectory base path (Laragon: /iv_classes/public). route(..., false) drops it.
    $chartUrl = rtrim(request()->getBasePath(), '/').'/dashboard/data/'.$widget
        .($chartQuery !== '' ? '?'.$chartQuery : '');
@endphp

<div {{ $attributes->class(['ivd-chart']) }} data-ivd-chart="{{ $widget }}"
    data-url="{{ $chartUrl }}"
    style="--ivd-chart-h: {{ (int) $height }}px">
    <div class="ivd-chart__skeleton" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
    </div>
    <div class="ivd-chart__error d-none">
        <i class="bx bx-error-circle"></i>
        <span>Could not load chart.</span>
        <button type="button" class="ivd-link" data-ivd-retry>Retry</button>
    </div>
    <div class="ivd-chart__canvas"></div>
</div>
