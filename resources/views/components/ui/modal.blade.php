@props(['id', 'title'])

@php
    $titleId = $id.'-title';
@endphp

{{-- Opened by dispatching open-modal with this id; the focus trap keeps Tab inside and returns focus on close. --}}
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    x-trap.inert.noscroll="open"
    x-on:click.self="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-scrim p-4"
>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
        {{ $attributes->class(['w-full max-w-md rounded-3xl border border-border bg-surface p-5 text-ink shadow-pop']) }}
    >
        <h2 id="{{ $titleId }}" class="text-xl font-extrabold">{{ $title }}</h2>
        <div class="mt-3 text-base text-muted">{{ $slot }}</div>
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            {{ $actions ?? '' }}
            <x-ui.button variant="ghost" x-on:click="open = false">{{ __('ui.close') }}</x-ui.button>
        </div>
    </div>
</div>
