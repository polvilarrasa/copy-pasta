@props(['user'])

@php
    $hue = \App\Support\AvatarColor::hueFor($user->getKey());
@endphp

<span
    {{ $attributes->class(['inline-flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-avatar-ink select-none']) }}
    style="background: {{ \App\Support\AvatarColor::gradient($hue) }}"
    aria-hidden="true"
>{{ $user->initials() }}</span>
