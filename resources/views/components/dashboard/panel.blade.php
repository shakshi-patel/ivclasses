@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'actionUrl' => null,
    'actionLabel' => 'View all',
])

<div {{ $attributes->class(['ivd-panel']) }}>
    <div class="ivd-panel__head">
        <div class="ivd-panel__title-wrap">
            @if ($icon)
                <span class="ivd-panel__icon"><i class="bx {{ $icon }}"></i></span>
            @endif
            <div>
                <h6 class="ivd-panel__title">{{ $title }}</h6>
                @if ($subtitle)
                    <p class="ivd-panel__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($tools)
            <div class="ivd-panel__tools">{{ $tools }}</div>
        @elseif ($actionUrl)
            <a href="{{ $actionUrl }}" class="ivd-link">{{ $actionLabel }} <i class="bx bx-right-arrow-alt"></i></a>
        @endisset
    </div>
    <div class="ivd-panel__body">
        {{ $slot }}
    </div>
</div>
