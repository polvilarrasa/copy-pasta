@props(['tab', 'prefix'])

<div
    id="{{ $prefix }}-panel-{{ $tab }}"
    role="tabpanel"
    tabindex="0"
    aria-labelledby="{{ $prefix }}-tab-{{ $tab }}"
    x-show="selected === '{{ $tab }}'"
    {{ $attributes }}
>{{ $slot }}</div>
