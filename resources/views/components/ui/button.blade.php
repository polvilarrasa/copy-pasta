@props(['variant' => 'secondary', 'size' => 'md', 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-accent text-on-accent',
        'secondary' => 'bg-surface-2 text-ink',
        'ghost' => 'bg-transparent text-muted',
        'danger' => 'bg-bad-bg text-bad',
    ];
    $sizes = [
        'md' => 'min-h-11 px-4 text-md',
        'lg' => 'h-13 px-5 text-md',
        'sm' => 'min-h-11 px-3 text-sm',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-lg font-bold transition-colors focus-visible:outline-none focus-visible:shadow-focus disabled:pointer-events-none disabled:opacity-50',
        $variants[$variant],
        $sizes[$size],
    ]) }}
>{{ $slot }}</button>
