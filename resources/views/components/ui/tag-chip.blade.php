@props(['name', 'color' => 't3', 'href' => null])

@php
    $tones = [
        't1' => 'bg-t1-bg text-t1-fg',
        't2' => 'bg-t2-bg text-t2-fg',
        't3' => 'bg-t3-bg text-t3-fg',
        't4' => 'bg-t4-bg text-t4-fg',
        't5' => 'bg-t5-bg text-t5-fg',
    ];
    $classes = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold '.$tones[$color];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>#{{ $name }}</a>
@else
    <span {{ $attributes->class([$classes]) }}>#{{ $name }}</span>
@endif
