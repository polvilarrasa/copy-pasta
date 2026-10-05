@php
    $shareUrl = route('copypastas.show', [$copypasta, $copypasta->slug]);
    $description = $copypasta->is_nsfw
        ? __('public.og.nsfw_description')
        : Str::limit(Str::squish($copypasta->body), 200, '');
@endphp

<x-layouts::public :title="$copypasta->title">
    @push('meta')
        <meta name="description" content="{{ $description }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $copypasta->title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $shareUrl }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $copypasta->title }}">
        <meta name="twitter:description" content="{{ $description }}">
        @if ($copypasta->isHidden() || $copypasta->published_at === null)
            <meta name="robots" content="noindex">
        @endif
    @endpush

    <section class="mx-auto max-w-3xl space-y-4 px-4 py-6">
        @if ($copypasta->isHidden())
            <div role="alert" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">
                {{ __('public.show.hidden_notice', ['reason' => $copypasta->hidden_reason]) }}
            </div>
        @elseif ($copypasta->published_at === null)
            <div role="alert" class="rounded-lg bg-zinc-100 p-3 text-sm text-zinc-700 ring-1 ring-zinc-200">
                {{ __('public.show.unpublished_notice') }}
            </div>
        @endif

        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-200" x-on:copypasta-folders-saved="applyFolderState($event.detail)" x-data="copypastaActions({
            body: @js($copypasta->body),
            copyUrl: @js(route('copypastas.copy', $copypasta)),
            voteUrl: @js(route('copypastas.vote', $copypasta)),
            favoriteUrl: @js(route('copypastas.favorite', $copypasta)),
            shareEventUrl: @js(route('copypastas.share', $copypasta)),
            shareUrl: @js($shareUrl),
            shareTitle: @js($copypasta->title),
            context: @js($context),
            authenticated: @js(auth()->check()),
            score: @js($copypasta->score),
            myVote: @js($copypasta->my_vote),
            isFavorite: @js((bool) $copypasta->is_favorite),
            favoritesCount: @js($copypasta->favorites_count),
            copiedMessage: @js(__('public.copy.copied')),
            linkCopiedMessage: @js(__('public.copy.link_copied')),
            loginRequiredVoteMessage: @js(__('public.login_modal.vote')),
            loginRequiredFavoriteMessage: @js(__('public.login_modal.favorite')),
            loginRequiredFolderMessage: @js(__('public.login_modal.folder')),
            actionFailedMessage: @js(__('public.copy.action_failed')),
        })">
            <header class="flex items-start justify-between gap-3">
                <h1 class="text-xl font-bold">{{ $copypasta->title }}</h1>
                @if ($copypasta->is_nsfw)
                    <span class="shrink-0 rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">{{ __('public.card.nsfw_badge') }}</span>
                @endif
            </header>

            <div class="relative mt-4">
                <div
                    class="whitespace-pre-wrap break-words font-mono text-sm text-zinc-800"
                    @if ($copypasta->is_nsfw) :class="{ 'blur-md select-none': ! revealed }" @endif
                >{{ $copypasta->body }}</div>

                @if ($copypasta->is_nsfw)
                    <button
                        type="button"
                        x-show="! revealed"
                        x-on:click="revealed = true"
                        class="absolute inset-0 flex items-center justify-center text-sm font-medium text-zinc-900"
                    >{{ __('public.card.reveal') }}</button>
                @endif
            </div>

            <footer class="mt-5 flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-500">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($copypasta->tags as $tag)
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-700">{{ $tag->name }}</span>
                    @endforeach
                    <span>{{ __('public.card.by', ['username' => $copypasta->user->displayName()]) }}</span>
                    <span>· {{ $copypasta->published_at?->diffForHumans() }}</span>
                    @if ($copypasta->revisions->count() > 1)
                        <details class="w-full">
                            <summary class="cursor-pointer">{{ __('public.show.edited', ['date' => $copypasta->edited_at?->diffForHumans()]) }}</summary>
                            <ol class="mt-2 space-y-3">
                                @foreach ($copypasta->revisions->reverse()->skip(1) as $revision)
                                    <li class="rounded-md bg-zinc-50 p-3">
                                        <p class="font-medium text-zinc-700">{{ $revision->title }}</p>
                                        <p class="whitespace-pre-wrap text-zinc-600">{{ $revision->body }}</p>
                                        <time class="text-zinc-400">{{ $revision->created_at->diffForHumans() }}</time>
                                    </li>
                                @endforeach
                            </ol>
                        </details>
                    @endif
                    <a href="{{ route('notice.create', $copypasta) }}" class="underline hover:text-zinc-900">{{ __('public.notice.link') }}</a>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @include('components.copypasta-actions', ['requiresReveal' => $copypasta->is_nsfw])
                </div>
            </footer>
        </article>

        <a href="{{ route('home') }}" class="inline-block text-sm text-zinc-600 underline hover:text-zinc-900">{{ __('public.show.back') }}</a>
    </section>
</x-layouts::public>
