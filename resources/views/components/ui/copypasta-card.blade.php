@props([
    'title',
    'body',
    'author',
    'authorHue' => 0,
    'achievement' => null,
    'score' => 0,
    'myVote' => null,
    'saved' => false,
    'tags' => [],
    'reason' => null,
    'template' => false,
    'featured' => false,
    'featuredDate' => null,
    'nsfw' => false,
    'ascii' => false,
    'full' => false,
    'copypasta' => null,
    'source' => null,
    'position' => null,
    'context' => null,
])

{{--
    Presentational on its own (as used in /_componentes); wired to the real vote/copy/save/share/folders/report
    actions when a `copypasta` model is passed, via the copypastaActions Alpine component in resources/js/app.js.
--}}
@php
    $scoreText = $score >= 1000 ? number_format($score / 1000, 1, ',', '').'k' : (string) $score;
    $isUpvoted = $myVote === 1;
    $isDownvoted = $myVote === -1;

    if ($copypasta) {
        $shareUrl = route('copypastas.show', [$copypasta, $copypasta->slug]);
        $listContext = $context ?? array_filter(['source' => $source, 'position' => $position], fn (mixed $value): bool => $value !== null);
        $detailQuery = array_filter(['from' => $listContext['source'] ?? null, 'pos' => $listContext['position'] ?? null]);
        $detailUrl = $detailQuery === [] ? $shareUrl : $shareUrl.'?'.http_build_query($detailQuery);
        $canReport = auth()->check() && auth()->id() !== $copypasta->user_id;
    }
@endphp

<article
    @if ($copypasta)
        x-data="copypastaActions({
            body: @js($copypasta->body),
            copyUrl: @js(route('copypastas.copy', $copypasta)),
            voteUrl: @js(route('copypastas.vote', $copypasta)),
            favoriteUrl: @js(route('copypastas.favorite', $copypasta)),
            shareEventUrl: @js(route('copypastas.share', $copypasta)),
            shareUrl: @js($shareUrl),
            shareTitle: @js($copypasta->title),
            context: @js($listContext),
            authenticated: @js(auth()->check()),
            score: @js($score),
            myVote: @js($myVote),
            isFavorite: @js($saved),
            favoritesCount: @js($copypasta->favorites_count),
            copiedMessage: @js(__('public.copy.copied')),
            linkCopiedMessage: @js(__('public.copy.link_copied')),
            loginRequiredVoteMessage: @js(__('public.login_modal.vote')),
            loginRequiredFavoriteMessage: @js(__('public.login_modal.favorite')),
            loginRequiredFolderMessage: @js(__('public.login_modal.folder')),
            actionFailedMessage: @js(__('public.copy.action_failed')),
        })"
        x-on:copypasta-folders-saved="applyFolderState($event.detail)"
    @else
        x-data="{ revealed: false }"
    @endif
    {{ $attributes->class([
        'flex w-full flex-col overflow-hidden rounded-2xl bg-surface text-ink',
        $featured ? 'border border-vote shadow-day' : 'border border-border',
    ]) }}
>
    @if ($featured)
        <div class="flex items-center justify-between bg-accent px-4 py-2.5 text-xs font-extrabold tracking-wider text-on-accent uppercase">
            <span class="flex items-center gap-2">
                <x-lucide-sparkle class="size-3.5" aria-hidden="true" />
                {{ __('ui.card.featured') }}
            </span>
            @if ($featuredDate)
                <span>{{ $featuredDate }}</span>
            @endif
        </div>
    @endif

    <div class="flex flex-col gap-3 p-3.5">
        <div class="flex gap-3">
            <div class="flex w-11 shrink-0 flex-col items-center gap-0.5">
                <button
                    type="button"
                    aria-label="{{ __('ui.card.vote_up') }}"
                    @if ($copypasta)
                        x-on:click="vote(1)"
                        :aria-pressed="myVote === 1 ? 'true' : 'false'"
                        :class="myVote === 1 ? 'bg-vote text-on-vote' : 'bg-surface-2 text-muted'"
                        class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus"
                    @else
                        aria-pressed="{{ $isUpvoted ? 'true' : 'false' }}"
                        class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus {{ $isUpvoted ? 'bg-vote text-on-vote' : 'bg-surface-2 text-muted' }}"
                    @endif
                >
                    <x-lucide-arrow-up class="size-5" aria-hidden="true" />
                </button>
                <span
                    @if ($copypasta) x-text="score" :class="myVote === 1 ? 'text-vote' : 'text-ink'" @endif
                    class="py-1 text-sm font-extrabold tabular-nums {{ $isUpvoted ? 'text-vote' : 'text-ink' }}"
                >{{ $scoreText }}</span>
                <button
                    type="button"
                    aria-label="{{ __('ui.card.vote_down') }}"
                    @if ($copypasta)
                        x-on:click="vote(-1)"
                        :aria-pressed="myVote === -1 ? 'true' : 'false'"
                        :class="myVote === -1 ? 'bg-ach text-surface' : 'text-muted'"
                        class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus"
                    @else
                        aria-pressed="{{ $isDownvoted ? 'true' : 'false' }}"
                        class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus {{ $isDownvoted ? 'bg-ach text-surface' : 'text-muted' }}"
                    @endif
                >
                    <x-lucide-arrow-down class="size-5" aria-hidden="true" />
                </button>
            </div>

            <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                @if ($reason)
                    <div class="flex flex-wrap items-center gap-1.5 text-sm text-muted">
                        <x-lucide-sparkle class="size-3.5" aria-hidden="true" />
                        <span>{{ __('ui.card.reason') }}</span>
                        <x-ui.tag-chip :name="$reason['name']" :color="$reason['color']" />
                    </div>
                @endif

                <div class="flex min-h-8 items-center gap-2">
                    <span class="size-7 shrink-0 rounded-full" aria-hidden="true" style="background: {{ \App\Support\AvatarColor::gradient($authorHue) }}"></span>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-sm font-bold leading-tight bidi-isolate">{{ $author }}</span>
                        @if ($achievement)
                            <span class="truncate text-xs font-semibold leading-snug text-ach bidi-isolate">{{ $achievement }}</span>
                        @endif
                    </div>
                    @if ($template)
                        <span class="flex h-6.5 shrink-0 items-center gap-1.5 rounded-sm bg-accent px-2.5 text-xs font-extrabold text-on-accent">
                            <x-lucide-layout-template class="size-3.5" aria-hidden="true" />
                            {{ __('ui.card.template') }}
                        </span>
                    @endif
                </div>

                <div class="relative">
                    {{-- Blurred from the server so sensitive text never shows before Alpine loads. --}}
                    <div
                        class="flex flex-col gap-1.5 bidi-isolate {{ $nsfw ? 'blur-md select-none pointer-events-none' : '' }}"
                        @if ($nsfw) x-bind:class="revealed && '!blur-none !select-auto !pointer-events-auto'" @endif
                    >
                        @if ($copypasta)
                            <h3 class="text-lg text-balance text-ink">
                                <a href="{{ $detailUrl }}" class="hover:underline">{{ $title }}</a>
                            </h3>
                        @else
                            <h3 class="text-lg text-balance text-ink">{{ $title }}</h3>
                        @endif
                        <p class="{{ $ascii ? 'rounded-md bg-surface-2 p-3 font-mono leading-ascii whitespace-pre' : 'text-base whitespace-pre-line text-ink' }} {{ $full ? '' : 'line-clamp-6' }}">{{ $body }}</p>
                    </div>

                    @if ($nsfw)
                        <div x-show="! revealed" class="absolute inset-0 flex items-center justify-center">
                            <button
                                type="button"
                                x-on:click="revealed = true"
                                class="flex min-h-12 flex-col items-center gap-1.5 rounded-xl bg-surface-2 p-3 text-center text-sm font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus"
                            >
                                <x-lucide-eye-off class="size-5.5" aria-hidden="true" />
                                {{ __('ui.card.sensitive') }}
                                <span class="font-medium text-muted">{{ __('ui.card.reveal') }}</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($template)
                    <p class="text-sm text-muted">{{ __('ui.card.template_hint') }}</p>
                @endif

                @if ($tags !== [])
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($tags as $tag)
                            <x-ui.tag-chip :name="$tag['name']" :color="$tag['color']" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <button
            type="button"
            @if ($copypasta)
                x-on:click="copy()"
                @if ($nsfw) x-bind:disabled="! revealed" @endif
            @endif
            class="flex h-13 w-full items-center justify-center gap-2.5 rounded-lg bg-accent text-md font-extrabold text-on-accent focus-visible:outline-none focus-visible:shadow-focus disabled:opacity-40"
        >
            <x-lucide-copy class="size-5" aria-hidden="true" />
            {{ __('ui.card.copy') }}
        </button>

        <div class="-mx-1.5 flex items-center gap-0.5">
            <button
                type="button"
                @if ($copypasta)
                    x-on:click="toggleFavorite()"
                    :aria-pressed="isFavorite ? 'true' : 'false'"
                    :class="isFavorite ? 'text-vote' : 'text-ink'"
                    class="flex h-11 items-center gap-2 rounded-md px-2.5 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus"
                @else
                    aria-pressed="{{ $saved ? 'true' : 'false' }}"
                    class="flex h-11 items-center gap-2 rounded-md px-2.5 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $saved ? 'text-vote' : 'text-ink' }}"
                @endif
            >
                @if ($copypasta)
                    <x-lucide-bookmark class="size-5" x-bind:class="isFavorite ? 'fill-current' : 'fill-none'" aria-hidden="true" />
                    <span x-text="isFavorite ? @js(__('ui.card.saved')) : @js(__('ui.card.save'))"></span>
                @else
                    <x-lucide-bookmark class="size-5 {{ $saved ? 'fill-current' : 'fill-none' }}" aria-hidden="true" />
                    {{ $saved ? __('ui.card.saved') : __('ui.card.save') }}
                @endif
            </button>
            <button
                type="button"
                @if ($copypasta) x-on:click="share()" @endif
                class="flex h-11 items-center gap-2 rounded-md px-2.5 text-sm font-semibold text-ink focus-visible:outline-none focus-visible:shadow-focus"
            >
                <x-lucide-upload class="size-5" aria-hidden="true" />
                {{ __('ui.card.share') }}
            </button>
            <span class="flex-1"></span>

            @if ($copypasta)
                <x-ui.dropdown :label="__('ui.card.more')">
                    <x-slot:trigger>
                        <span class="flex size-11 items-center justify-center rounded-md text-muted">
                            <x-lucide-ellipsis class="size-5" aria-hidden="true" />
                            <span class="sr-only">{{ __('ui.card.more') }}</span>
                        </span>
                    </x-slot:trigger>

                    <x-ui.menu-item x-on:click="open = false; authenticated ? $dispatch('folders-open', { copypasta: '{{ $copypasta->getKey() }}', context }) : requireLogin(loginRequiredFolderMessage)">
                        {{ __('public.folders.button') }}
                    </x-ui.menu-item>

                    @if ($canReport)
                        <x-ui.menu-item x-on:click="open = false; $dispatch('report-open', { copypasta: '{{ $copypasta->getKey() }}', context })">
                            {{ __('public.report.button') }}
                        </x-ui.menu-item>
                    @endif
                </x-ui.dropdown>
            @else
                <button
                    type="button"
                    aria-label="{{ __('ui.card.more') }}"
                    class="flex size-11 items-center justify-center rounded-md text-muted focus-visible:outline-none focus-visible:shadow-focus"
                >
                    <x-lucide-ellipsis class="size-5" aria-hidden="true" />
                </button>
            @endif
        </div>
    </div>

    @if ($copypasta)
        @include('components.copypasta-folder-selector')
        @if (auth()->check())
            @include('components.copypasta-report-modal')
        @endif
    @endif
</article>
