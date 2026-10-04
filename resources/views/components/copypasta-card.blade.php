@props(['copypasta'])

@php
    $detailUrl = route('copypastas.show', [$copypasta, $copypasta->slug]);
    $previewLines = explode("\n", $copypasta->body);
    $isTruncated = count($previewLines) > 6;
    $preview = implode("\n", array_slice($previewLines, 0, 6));
@endphp

<article
    x-data="copypastaActions({
        body: @js($copypasta->body),
        copyUrl: @js(route('copypastas.copy', $copypasta)),
        voteUrl: @js(route('copypastas.vote', $copypasta)),
        favoriteUrl: @js(route('copypastas.favorite', $copypasta)),
        shareUrl: @js($detailUrl),
        shareTitle: @js($copypasta->title),
        authenticated: @js(auth()->check()),
        score: @js($copypasta->score),
        myVote: @js($copypasta->my_vote),
        isFavorite: @js((bool) $copypasta->is_favorite),
        favoritesCount: @js($copypasta->favorites_count),
        copiedMessage: @js(__('public.copy.copied')),
        linkCopiedMessage: @js(__('public.copy.link_copied')),
        loginRequiredVoteMessage: @js(__('public.login_modal.vote')),
        loginRequiredFavoriteMessage: @js(__('public.login_modal.favorite')),
        actionFailedMessage: @js(__('public.copy.action_failed')),
    })"
    class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-zinc-200"
    {{ $attributes }}
>
    <header class="flex items-start justify-between gap-3">
        <a href="{{ $detailUrl }}" class="font-semibold text-zinc-900 hover:underline">{{ $copypasta->title }}</a>
        @if ($copypasta->is_nsfw)
            <span class="shrink-0 rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">{{ __('public.card.nsfw_badge') }}</span>
        @endif
    </header>

    <div class="relative mt-3">
        <div
            class="whitespace-pre-wrap break-words font-mono text-sm text-zinc-700"
            @if ($copypasta->is_nsfw) :class="{ 'blur-md select-none': ! revealed }" @endif
        >{{ $preview }}{{ $isTruncated ? "\n…" : '' }}</div>

        @if ($copypasta->is_nsfw)
            <button
                type="button"
                x-show="! revealed"
                x-on:click="revealed = true"
                class="absolute inset-0 flex items-center justify-center text-sm font-medium text-zinc-900"
            >{{ __('public.card.reveal') }}</button>
        @endif
    </div>

    <footer class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-500">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($copypasta->tags as $tag)
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-700">{{ $tag->name }}</span>
            @endforeach
            <span>{{ __('public.card.by', ['username' => $copypasta->user->username]) }}</span>
            <span>· {{ $copypasta->published_at->diffForHumans() }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @include('components.copypasta-actions', ['requiresReveal' => $copypasta->is_nsfw])
        </div>
    </footer>
</article>
