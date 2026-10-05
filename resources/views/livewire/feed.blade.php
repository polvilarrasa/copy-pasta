<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    @if ($fixedSort === null)
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('public.feed.sort_label') }}">
            @foreach ($sortOptions as $option)
                <button
                    type="button"
                    wire:click="$set('sort', '{{ $option->value }}')"
                    aria-pressed="{{ $activeSort === $option ? 'true' : 'false' }}"
                    @class([
                        'rounded-full px-3 py-1 text-sm font-medium',
                        'bg-zinc-900 text-white' => $activeSort === $option,
                        'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-100' => $activeSort !== $option,
                    ])
                >{{ __('public.sorts.'.$option->value) }}</button>
            @endforeach

            @if ($activeSort === \App\Enums\FeedSort::Random)
                <button type="button" wire:click="shuffle" class="rounded-full px-3 py-1 text-sm font-medium text-zinc-700 underline hover:text-zinc-900">
                    {{ __('public.feed.shuffle') }}
                </button>
            @endif
        </div>
    @endif

    <div class="space-y-3">
        <input
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="{{ __('public.feed.search_placeholder') }}"
            aria-label="{{ __('public.feed.search_placeholder') }}"
            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-zinc-900 focus:outline-none"
        >

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($availableTags as $tag)
                <button
                    type="button"
                    wire:click="toggleTag('{{ $tag->slug }}')"
                    aria-pressed="{{ in_array($tag->slug, $activeTagSlugs, true) ? 'true' : 'false' }}"
                    @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-zinc-900 text-white' => in_array($tag->slug, $activeTagSlugs, true),
                        'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-100' => ! in_array($tag->slug, $activeTagSlugs, true),
                    ])
                >{{ $tag->name }}</button>
            @endforeach
        </div>

        @if ($canToggleNsfw)
            <div x-data="{ confirming: false, redirect: '' }">
                @if ($hasNsfwConsent)
                    <button type="button" wire:click="$toggle('nsfw')" class="text-sm text-zinc-700 underline hover:text-zinc-900">
                        {{ $includesNsfw ? __('public.feed.nsfw_hide') : __('public.feed.nsfw_show') }}
                    </button>
                @else
                    <button type="button" x-on:click="confirming = true" class="text-sm text-zinc-700 underline hover:text-zinc-900">
                        {{ __('public.feed.nsfw_show') }}
                    </button>

                    <div x-show="confirming" x-cloak role="dialog" aria-modal="true" aria-labelledby="nsfw-confirm-title"
                         class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-900/50 px-4"
                         x-on:keydown.escape.window="confirming = false">
                        <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl" x-on:click.outside="confirming = false">
                            <h2 id="nsfw-confirm-title" class="text-lg font-semibold">{{ __('public.nsfw.title') }}</h2>
                            <p class="mt-2 text-sm text-zinc-600">{{ __('public.nsfw.body') }}</p>

                            <form method="POST" action="{{ $nsfwConfirmRoute }}" class="mt-5 flex justify-end gap-2"
                                  x-on:submit="const url = new URL(window.location.href); url.searchParams.set('nsfw', '1'); redirect = url.pathname + url.search">
                                @csrf
                                <input type="hidden" name="redirect" x-bind:value="redirect">
                                <button type="button" x-on:click="confirming = false" class="rounded-lg px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">
                                    {{ __('public.nsfw.cancel') }}
                                </button>
                                <button type="submit" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                                    {{ __('public.nsfw.confirm') }}
                                </button>
                            </form>
                        </div>
                    </div>
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
            <p class="rounded-xl bg-white p-6 text-center text-sm text-zinc-500 ring-1 ring-zinc-200">
                {{ __('public.feed.empty') }}
            </p>
        @endforelse
    </div>

    @if ($hasMore)
        <div class="flex justify-center">
            <button type="button" wire:click="loadMore" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-100">
                {{ __('public.feed.load_more') }}
            </button>
        </div>
    @endif
</section>
