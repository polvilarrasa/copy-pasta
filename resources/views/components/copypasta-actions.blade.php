{{-- Vote, favorite, copy and share controls shared by the feed card and the detail page. --}}
<div class="flex items-center gap-1 rounded-md ring-1 ring-zinc-200">
    <button
        type="button"
        x-on:click="vote(1)"
        aria-label="{{ __('public.vote.up') }}"
        :aria-pressed="myVote === 1 ? 'true' : 'false'"
        class="rounded-l-md px-2 py-1 font-medium hover:bg-zinc-50"
        :class="myVote === 1 ? 'text-emerald-700 bg-emerald-50' : 'text-zinc-700'"
    >▲</button>
    <span class="min-w-6 text-center font-medium tabular-nums text-zinc-800" x-text="score"></span>
    <button
        type="button"
        x-on:click="vote(-1)"
        aria-label="{{ __('public.vote.down') }}"
        :aria-pressed="myVote === -1 ? 'true' : 'false'"
        class="rounded-r-md px-2 py-1 font-medium hover:bg-zinc-50"
        :class="myVote === -1 ? 'text-rose-700 bg-rose-50' : 'text-zinc-700'"
    >▼</button>
</div>

<button
    type="button"
    x-on:click="toggleFavorite()"
    :aria-pressed="isFavorite ? 'true' : 'false'"
    class="rounded-md px-2 py-1 font-medium ring-1 ring-zinc-200 hover:bg-zinc-50"
    :class="isFavorite ? 'text-amber-600 bg-amber-50' : 'text-zinc-700'"
>
    <span aria-hidden="true">★</span>
    <span class="sr-only" x-text="isFavorite ? '{{ __('public.favorite.remove') }}' : '{{ __('public.favorite.add') }}'"></span>
    <span class="tabular-nums" x-text="favoritesCount"></span>
</button>

<button
    type="button"
    x-on:click="copy()"
    @if (! empty($requiresReveal)) :disabled="! revealed" @endif
    class="rounded-md px-2 py-1 font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 disabled:opacity-40"
>{{ __('public.card.copy') }}</button>

<button
    type="button"
    x-on:click="share()"
    class="rounded-md px-2 py-1 font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50"
>{{ __('public.card.share') }}</button>
