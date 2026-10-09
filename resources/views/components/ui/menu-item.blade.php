@props(['href' => null, 'type' => 'button'])

@php
    $classes = 'flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-semibold text-ink hover:bg-surface-2 focus-visible:bg-surface-2 focus-visible:outline-none';
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
