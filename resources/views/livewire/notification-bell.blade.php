@php
    $impersonating = is_impersonating();
    $countLabel = $unreadCount > 99 ? __('notifications.bell.more_than_99') : $unreadCount;
@endphp

{{-- Polling lives inside an x-if so it only exists while the tab is visible; coming back to the tab refreshes at once. --}}
<div
    x-data="{ open: false, visible: ! document.hidden }"
    x-on:visibilitychange.window="visible = ! document.hidden; if (visible) $wire.$refresh()"
    x-on:click.outside="open = false"
    x-on:keydown.escape.stop="if (open) { open = false; $refs.trigger.focus() }"
    class="sm:relative"
>
    <template x-if="visible">
        <div wire:poll.60s></div>
    </template>

    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = ! open; if (open) $wire.loadList()"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="dialog"
        aria-label="{{ $unreadCount > 0 ? trans_choice('notifications.bell.label_unread', $unreadCount, ['count' => $unreadCount]) : __('notifications.bell.label') }}"
        :class="open ? 'border-vote' : 'border-transparent'"
        class="relative flex size-11 items-center justify-center rounded-full border-2 bg-surface-2 text-ink focus-visible:outline-none focus-visible:shadow-focus"
        data-test="notification-bell"
    >
        <x-lucide-bell class="size-5" aria-hidden="true" />
        @if ($unreadCount > 0)
            <span aria-hidden="true" class="absolute -end-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-accent px-1 text-xs font-extrabold text-on-accent" data-test="notification-count">{{ $countLabel }}</span>
        @endif
    </button>

    <section
        x-show="open"
        x-cloak
        role="dialog"
        aria-label="{{ __('notifications.bell.label') }}"
        class="absolute inset-x-2 top-full z-40 mt-2 overflow-hidden rounded-2xl border border-border bg-surface shadow-pop sm:inset-x-auto sm:end-0 sm:w-md"
    >
        <div class="flex items-center gap-2 px-4 pb-1.5 pt-3.5">
            <h2 class="flex flex-1 items-center gap-2 text-xl font-extrabold text-ink">
                {{ __('notifications.bell.label') }}
                @if ($unreadCount > 0)
                    <span class="rounded-full bg-accent px-2 py-0.5 text-xs font-extrabold text-on-accent">{{ $countLabel }}</span>
                @endif
            </h2>
            @if ($unreadCount > 0 && ! $impersonating)
                <button type="button" wire:click="markAllRead" class="min-h-11 px-2.5 text-sm font-bold text-vote focus-visible:outline-none focus-visible:shadow-focus" data-test="notification-mark-all">
                    {{ __('notifications.bell.mark_all') }}
                </button>
            @endif
        </div>

        <div role="tablist" aria-label="{{ __('notifications.bell.tabs') }}" class="flex gap-2 px-4 pb-2.5">
            @foreach (['all' => __('notifications.bell.all'), 'unread' => __('notifications.bell.unread')] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="setTab('{{ $key }}')"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="min-h-11 rounded-full px-4 text-sm font-bold focus-visible:outline-none focus-visible:shadow-focus {{ $tab === $key ? 'bg-accent text-on-accent' : 'bg-surface-2 text-ink' }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        <ul class="divide-y divide-border border-t border-border">
            @if (! $loaded)
                <li class="px-4 py-6 text-center text-base text-muted">{{ __('notifications.bell.loading') }}</li>
            @else
                @forelse ($items as $item)
                    <li wire:key="bell-{{ $item->id }}">
                        <x-notification-item :item="$item" />
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-base text-muted">
                        {{ $tab === 'unread' ? __('notifications.bell.empty_unread') : __('notifications.bell.empty') }}
                    </li>
                @endforelse
            @endif
        </ul>

        <a href="{{ route('notifications.index') }}" wire:navigate class="flex h-13 items-center justify-center border-t border-border text-md font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus">
            {{ __('notifications.bell.view_all') }}
        </a>
    </section>
</div>
