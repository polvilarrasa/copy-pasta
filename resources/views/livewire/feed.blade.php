<div class="mx-auto flex w-full items-start gap-6 px-4 py-6 {{ $viewer ? 'max-w-5xl' : 'max-w-3xl' }}">
<section class="min-w-0 flex-1 space-y-5">
    @if ($fixedSort === null)
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('public.feed.sort_label') }}">
            <div class="flex flex-wrap gap-1 rounded-full bg-surface-2 p-1">
                @if ($hasForYouTab)
                    <button
                        type="button"
                        wire:click="$set('sort', '{{ \App\Livewire\Feed::FOR_YOU }}')"
                        data-test="tab-for-you"
                        aria-pressed="{{ $activeTab === \App\Livewire\Feed::FOR_YOU ? 'true' : 'false' }}"
                        class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $activeTab === \App\Livewire\Feed::FOR_YOU ? 'bg-accent text-on-accent' : 'text-muted' }}"
                    >{{ __('public.feed.for_you') }}</button>
                @endif
                @foreach ($sortOptions as $option)
                    <button
                        type="button"
                        wire:click="$set('sort', '{{ $option->value }}')"
                        aria-pressed="{{ $activeTab === $option->value ? 'true' : 'false' }}"
                        class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $activeTab === $option->value ? 'bg-accent text-on-accent' : 'text-muted' }}"
                    >{{ __('public.sorts.'.$option->value) }}</button>
                @endforeach
            </div>

            @if ($forYou !== null)
                <button type="button" wire:click="refreshForYou" data-test="for-you-refresh" class="text-sm font-semibold text-ink underline">
                    {{ __('public.feed.for_you_refresh') }}
                </button>
                <a href="{{ route('welcome', ['modo' => 'editar']) }}" wire:navigate data-test="for-you-edit-favorites" class="text-sm font-semibold text-ink underline lg:hidden">
                    {{ __('public.favorites.edit') }}
                </a>
            @elseif ($activeSort === \App\Enums\FeedSort::Random)
                <button type="button" wire:click="shuffle" class="text-sm font-semibold text-ink underline">
                    {{ __('public.feed.shuffle') }}
                </button>
            @endif
        </div>
    @endif

    @if ($featured)
        <x-copypasta-card
            :copypasta="$featured"
            source="featured"
            :featured="true"
            :featured-date="now()->translatedFormat('j \\d\\e F')"
            wire:key="featured-{{ $featured->id }}"
        />
    @endif

    <div class="space-y-3" @if ($forYou !== null) hidden @endif>
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
        @if ($forYou !== null)
            @forelse ($forYou->items as $item)
                <x-copypasta-card
                    :copypasta="$item->copypasta"
                    :context="['source' => 'para_ti', 'position' => $item->position, 'group' => $item->group]"
                    :reason="$item->explanation === 'liked' ? ['name' => $item->tag->name, 'color' => $item->tag->color] : null"
                    :explanation="$item->explanation === 'liked' ? null : __('ui.card.'.$item->explanation)"
                    wire:key="for-you-{{ $item->copypasta->id }}"
                />
            @empty
                <x-ui.empty-state :title="__('public.feed.for_you_empty')" />
            @endforelse
        @else
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
        @endif
    </div>

    @if ($hasMore)
        <div class="flex justify-center">
            <x-ui.button type="button" variant="secondary" wire:click="loadMore">
                {{ __('public.feed.load_more') }}
            </x-ui.button>
        </div>
    @endif
</section>

@if ($viewer)
    <aside class="sticky top-6 hidden w-64 shrink-0 flex-col gap-3 rounded-2xl border border-border bg-surface p-4 lg:flex" aria-labelledby="favorite-tags-heading" data-test="favorite-tags">
        <h2 id="favorite-tags-heading" class="text-md font-extrabold text-ink">{{ __('public.favorites.title') }}</h2>

        @if ($favoriteTags->isEmpty())
            <p class="text-sm text-muted">{{ __('public.favorites.empty') }}</p>
        @else
            <div class="flex flex-wrap gap-1.5">
                @foreach ($favoriteTags as $tag)
                    <x-ui.tag-chip :name="$tag->name" :color="$tag->color" />
                @endforeach
            </div>
        @endif

        <a href="{{ route('welcome', ['modo' => 'editar']) }}" wire:navigate data-test="edit-favorites" class="text-sm font-semibold text-ink underline">
            {{ $favoriteTags->isEmpty() ? __('public.favorites.choose') : __('public.favorites.edit') }}
        </a>
    </aside>
@endif
</div>
