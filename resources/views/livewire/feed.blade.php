<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    @if ($fixedSort === null)
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('public.feed.sort_label') }}">
            <div class="flex flex-wrap gap-1 rounded-full bg-surface-2 p-1">
                @foreach ($sortOptions as $option)
                    <button
                        type="button"
                        wire:click="$set('sort', '{{ $option->value }}')"
                        aria-pressed="{{ $activeSort === $option ? 'true' : 'false' }}"
                        class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $activeSort === $option ? 'bg-accent text-on-accent' : 'text-muted' }}"
                    >{{ __('public.sorts.'.$option->value) }}</button>
                @endforeach
            </div>

            @if ($activeSort === \App\Enums\FeedSort::Random)
                <button type="button" wire:click="shuffle" class="text-sm font-semibold text-ink underline">
                    {{ __('public.feed.shuffle') }}
                </button>
            @endif
        </div>
    @endif

    <div class="space-y-3">
        <x-ui.input
            name="search"
            :label="__('public.feed.search_placeholder')"
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="{{ __('public.feed.search_placeholder') }}"
        />

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($availableTags as $tag)
                <button
                    type="button"
                    wire:click="toggleTag('{{ $tag->slug }}')"
                    aria-pressed="{{ in_array($tag->slug, $activeTagSlugs, true) ? 'true' : 'false' }}"
                    class="min-h-11 rounded-full px-3 text-sm font-semibold {{ in_array($tag->slug, $activeTagSlugs, true) ? 'bg-accent text-on-accent' : 'bg-surface-2 text-muted' }}"
                >{{ $tag->name }}</button>
            @endforeach
        </div>

        @if ($canToggleNsfw)
            <div x-data="{ redirect: '' }">
                @if ($hasNsfwConsent)
                    <button type="button" wire:click="$toggle('nsfw')" class="text-sm font-semibold text-ink underline">
                        {{ $includesNsfw ? __('public.feed.nsfw_hide') : __('public.feed.nsfw_show') }}
                    </button>
                @else
                    <button type="button" x-on:click="$dispatch('open-modal', 'nsfw-confirm')" class="text-sm font-semibold text-ink underline">
                        {{ __('public.feed.nsfw_show') }}
                    </button>

                    <x-ui.modal id="nsfw-confirm" :title="__('public.nsfw.title')">
                        {{ __('public.nsfw.body') }}

                        <form
                            method="POST"
                            action="{{ $nsfwConfirmRoute }}"
                            x-on:submit="const url = new URL(window.location.href); url.searchParams.set('nsfw', '1'); redirect = url.pathname + url.search"
                        >
                            @csrf
                            <input type="hidden" name="redirect" x-bind:value="redirect">

                            <x-slot:actions>
                                <x-ui.button type="submit" variant="primary">
                                    {{ __('public.nsfw.confirm') }}
                                </x-ui.button>
                            </x-slot:actions>
                        </form>
                    </x-ui.modal>
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($copypastas as $copypasta)
            <x-copypasta-card
                :copypasta="$copypasta"
                :source="$activeSort->value"
                :position="$loop->index"
                wire:key="{{ $copypasta->id }}"
            />
        @empty
            <x-ui.empty-state :title="__('public.feed.empty')" />
        @endforelse
    </div>

    @if ($hasMore)
        <div class="flex justify-center">
            <x-ui.button type="button" variant="secondary" wire:click="loadMore">
                {{ __('public.feed.load_more') }}
            </x-ui.button>
        </div>
    @endif
</section>
