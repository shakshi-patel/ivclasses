@props([
    'title',
    'text' => null,
    'count' => 0,
    'showCount' => true,
    'icon' => 'bx-bell',
    'tone' => 'primary',
    'href' => null,
])

@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['ivd-attn', 'ivd-tone-' . $tone]) }}>
    <span class="ivd-attn__icon"><i class="bx {{ $icon }}"></i></span>
    <span class="ivd-attn__body">
        <span class="ivd-attn__title">{{ $title }}</span>
        @if ($text)
            <span class="ivd-attn__text">{{ $text }}</span>
        @endif
    </span>
    @if ($showCount)
        <span class="ivd-attn__count">{{ number_format($count) }}</span>
    @endif
    @if ($href)
        <i class="bx bx-chevron-right ivd-attn__chev"></i>
    @endif
</{{ $tag }}>
