@php
    $impersonating = is_impersonating();
@endphp

<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('notifications.page.title') }}</h1>
            <p class="text-base text-muted">{{ __('notifications.page.description') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.edit') }}" wire:navigate class="flex min-h-11 items-center px-2 text-sm font-semibold text-muted hover:text-ink">
                {{ __('notifications.page.preferences') }}
            </a>
            @unless ($impersonating)
                <x-ui.button variant="secondary" wire:click="markAllRead" data-test="notifications-mark-all">
                    {{ __('notifications.page.mark_all') }}
                </x-ui.button>
            @endunless
        </div>
    </div>

    <div role="tablist" aria-label="{{ __('notifications.bell.tabs') }}" class="flex gap-2">
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

    @if ($items->isEmpty())
        <x-ui.empty-state :title="$tab === 'unread' ? __('notifications.bell.empty_unread') : __('notifications.bell.empty')">
            {{ __('notifications.page.description') }}
        </x-ui.empty-state>
    @else
        <ul class="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-surface">
            @foreach ($items as $item)
                <li wire:key="list-{{ $item->id }}">
                    <x-notification-item :item="$item" />
                </li>
            @endforeach
        </ul>

        @if ($paginator->hasPages())
            <nav class="flex items-center justify-between gap-3" aria-label="{{ __('notifications.page.title') }}">
                <x-ui.button variant="secondary" wire:click="previousPage('{{ $paginator->getPageName() }}')" :disabled="$paginator->onFirstPage()">
                    {{ __('notifications.page.previous') }}
                </x-ui.button>
                <span class="text-sm text-muted">{{ __('notifications.page.page_of', ['page' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}</span>
                <x-ui.button variant="secondary" wire:click="nextPage('{{ $paginator->getPageName() }}')" :disabled="! $paginator->hasMorePages()">
                    {{ __('notifications.page.next') }}
                </x-ui.button>
            </nav>
        @endif
    @endif
</section>
