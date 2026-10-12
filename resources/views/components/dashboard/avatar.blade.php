@props([
    'name' => null,
    'src' => null,
    'icon' => null,
])

@php
    $initials = \Illuminate\Support\Str::upper(
        collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))
            ->implode('')
    );
@endphp

<span {{ $attributes->class(['ivd-avatar']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" loading="lazy">
    @elseif ($icon)
        <i class="bx {{ $icon }}"></i>
    @else
        {{ $initials ?: '—' }}
    @endif
</span>
