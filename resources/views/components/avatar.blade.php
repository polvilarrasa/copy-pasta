@props(['user', 'size' => 'sm'])

@php
    $hue = \App\Support\AvatarColor::hueFor($user->getKey());
    $sizeClasses = match ($size) {
        'lg' => 'size-28 text-3xl',
        default => 'size-8 text-xs',
    };
@endphp

<span
    {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-full font-bold text-avatar-ink select-none', $sizeClasses]) }}
    style="background: {{ \App\Support\AvatarColor::gradient($hue) }}"
    aria-hidden="true"
>{{ $user->initials() }}</span>
