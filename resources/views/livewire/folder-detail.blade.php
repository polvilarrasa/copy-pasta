@php
    $icon = [
        't1' => ['bg' => 'bg-t1-bg', 'fg' => 'text-t1-fg'],
        't2' => ['bg' => 'bg-t2-bg', 'fg' => 'text-t2-fg'],
        't3' => ['bg' => 'bg-t3-bg', 'fg' => 'text-t3-fg'],
        't4' => ['bg' => 'bg-t4-bg', 'fg' => 'text-t4-fg'],
        't5' => ['bg' => 'bg-t5-bg', 'fg' => 'text-t5-fg'],
    ]['t3'];
@endphp

<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    <a href="{{ route('folders.index') }}" class="text-sm font-bold text-muted hover:text-ink">
        &larr; {{ __('public.folder.back') }}
    </a>

    <div class="flex flex-wrap items-center gap-4 rounded-3xl border border-border bg-surface p-5">
        <span class="flex size-16 shrink-0 items-center justify-center rounded-2xl {{ $icon['bg'] }} {{ $icon['fg'] }}" aria-hidden="true">
            <x-lucide-folder class="size-8" />
        </span>

        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-extrabold text-ink">{{ $folder->name }}</h1>
            @if ($folder->description)
                <p class="text-sm text-muted">{{ $folder->description }}</p>
            @endif
            <p class="mt-1 text-sm font-semibold text-muted">
                {{ trans_choice('public.folder.count', $copypastasCount, ['count' => $copypastasCount]) }}
            </p>
        </div>
    </div>

    <div>
        <label for="folder-search" class="sr-only">{{ __('public.folder.search_label') }}</label>
        <div class="flex h-12 items-center gap-2 rounded-full border border-border bg-surface px-4 text-muted">
            <x-lucide-search class="size-5" aria-hidden="true" />
            <input
                id="folder-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('public.folder.search_placeholder') }}"
                class="h-full flex-1 border-0 bg-transparent text-md text-ink outline-none placeholder:text-muted"
            >
        </div>
    </div>

    @if (! $hasAnyItems)
        <x-ui.empty-state :title="__('app.folders.empty')">
            {{ __('public.folder.empty_body') }}
            <div class="mt-4">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-accent px-4 text-md font-bold text-on-accent">
                    {{ __('public.folder.empty_cta') }}
                </a>
            </div>
        </x-ui.empty-state>
    @elseif ($copypastas->isEmpty())
        <p class="text-base text-muted">{{ __('public.feed.empty') }}</p>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($copypastas as $copypasta)
                <div wire:key="folder-item-{{ $copypasta->id }}">
                    @if ($copypasta->trashed() || $copypasta->isHidden())
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-dashed border-border bg-surface p-4">
                            <span class="text-sm font-semibold text-muted">{{ __('app.folders.removed_content') }}</span>
                            <x-ui.button
                                size="sm"
                                variant="secondary"
                                x-on:click="$wire.removeFromFolder('{{ $copypasta->id }}')"
                            >
                                {{ __('app.folders.remove') }}
                            </x-ui.button>
                        </div>
                    @else
                        <x-copypasta-card :copypasta="$copypasta" source="folder" />
                        <div class="mt-2 flex justify-end">
                            <x-ui.button
                                size="sm"
                                variant="secondary"
                                x-on:click="$wire.removeFromFolder('{{ $copypasta->id }}')"
                            >
                                {{ __('app.folders.remove') }}
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
