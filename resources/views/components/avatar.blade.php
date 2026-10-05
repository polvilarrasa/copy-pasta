{{-- Provisional neutral style: the Phase 14 design system replaces these classes. --}}
@props(['user'])

@php
    $tones = [
        'bg-zinc-200 text-zinc-700',
        'bg-amber-100 text-amber-800',
        'bg-sky-100 text-sky-800',
        'bg-emerald-100 text-emerald-800',
        'bg-rose-100 text-rose-800',
        'bg-violet-100 text-violet-800',
    ];
    $tone = $tones[crc32((string) $user->getKey()) % count($tones)];
@endphp

<span {{ $attributes->class(['inline-flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold select-none', $tone]) }} aria-hidden="true">{{ $user->initials() }}</span>
