@props(['item'])

@php
    $tones = [
        't1' => 'bg-t1-bg text-t1-fg',
        't2' => 'bg-t2-bg text-t2-fg',
        't3' => 'bg-t3-bg text-t3-fg',
        't4' => 'bg-t4-bg text-t4-fg',
        't5' => 'bg-t5-bg text-t5-fg',
    ];
@endphp

{{-- A button, not a link: opening marks the notification as read, and a link would be prefetched on hover. --}}
<button
    type="button"
    wire:click="open('{{ $item->id }}')"
    {{ $attributes->class([
        'flex min-h-11 w-full items-start gap-3 px-4 py-3.5 text-start focus-visible:outline-none focus-visible:shadow-focus',
        'bg-unread' => ! $item->isRead,
    ]) }}
>
    <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $tones[$item->tone] }}">
        <x-dynamic-component :component="'lucide-'.$item->icon" class="size-5" />
    </span>
    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
        <span class="text-md text-ink {{ $item->isRead ? 'font-medium' : 'font-bold' }}">{{ $item->text }}</span>
        <span class="text-xs text-muted">{{ $item->at->diffForHumans() }}</span>
    </span>
    @unless ($item->isRead)
        <span role="img" aria-label="{{ __('notifications.bell.unread_dot') }}" class="mt-1.5 size-2.5 shrink-0 rounded-full bg-vote"></span>
    @endunless
</button>
