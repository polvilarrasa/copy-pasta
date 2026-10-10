<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    <h1 class="text-2xl font-extrabold text-ink">{{ __('public.mine.title') }}</h1>

    @if ($copypastas->isEmpty())
        <x-ui.empty-state :title="__('public.mine.empty_title')">
            {{ __('public.mine.empty_body') }}
            <div class="mt-4">
                <a href="{{ route('copypastas.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-accent px-4 text-md font-bold text-on-accent">
                    {{ __('public.mine.empty_cta') }}
                </a>
            </div>
        </x-ui.empty-state>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($copypastas as $copypasta)
                <article class="flex flex-col gap-3 rounded-2xl border border-border bg-surface p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="mine-{{ $copypasta->id }}">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('copypastas.show', [$copypasta, $copypasta->slug]) }}" class="block truncate text-lg font-bold text-ink hover:underline">
                            {{ $copypasta->title }}
                        </a>

                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
                            @if ($copypasta->isHidden())
                                <span class="font-semibold text-bad">{{ __('app.status.hidden') }}</span>
                                <span>&middot;</span>
                                <span>{{ $copypasta->hidden_reason }}</span>
                            @else
                                <span class="font-semibold text-ok">{{ __('app.status.visible') }}</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap gap-4 text-sm font-semibold text-muted">
                            <span>{{ __('app.fields.score') }}: {{ $copypasta->score }}</span>
                            <span>{{ __('public.mine.copies') }}: {{ $copypasta->copies_count }}</span>
                            <span>{{ __('public.mine.saved') }}: {{ $copypasta->favorites_count }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('copypastas.show', [$copypasta, $copypasta->slug]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-2 px-3 text-sm font-bold text-ink">
                            {{ __('public.mine.view') }}
                        </a>
                        <a href="{{ route('copypastas.edit', $copypasta) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-2 px-3 text-sm font-bold text-ink">
                            {{ __('public.mine.edit') }}
                        </a>
                        <x-ui.button
                            size="sm"
                            variant="danger"
                            x-on:click="$wire.confirmDelete('{{ $copypasta->id }}'); $dispatch('open-modal', 'confirm-delete-copypasta')"
                        >
                            {{ __('public.mine.delete') }}
                        </x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($hasMore)
            <div class="flex justify-center">
                <x-ui.button variant="secondary" wire:click="loadMore">{{ __('public.feed.load_more') }}</x-ui.button>
            </div>
        @endif
    @endif

    <x-ui.modal id="confirm-delete-copypasta" :title="__('public.publish.delete')" :close="'resetConfirmingDelete'">
        {{ __('public.publish.delete_confirm') }}

        <x-slot:actions>
            <x-ui.button variant="danger" wire:click="delete" x-on:click="open = false">
                {{ __('public.mine.delete') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</section>
