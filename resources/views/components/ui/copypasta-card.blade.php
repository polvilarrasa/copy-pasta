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
])

{{-- Presentational: the feed and detail pages wire the buttons to their actions in Fase 14b. --}}
@php
    $scoreText = $score >= 1000 ? number_format($score / 1000, 1, ',', '').'k' : (string) $score;
    $isUpvoted = $myVote === 1;
    $isDownvoted = $myVote === -1;
@endphp

<article
    x-data="{ revealed: false }"
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
                    aria-pressed="{{ $isUpvoted ? 'true' : 'false' }}"
                    class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus {{ $isUpvoted ? 'bg-vote text-on-vote' : 'bg-surface-2 text-muted' }}"
                >
                    <x-lucide-arrow-up class="size-5" aria-hidden="true" />
                </button>
                <span class="py-1 text-sm font-extrabold tabular-nums {{ $isUpvoted ? 'text-vote' : 'text-ink' }}">{{ $scoreText }}</span>
                <button
                    type="button"
                    aria-label="{{ __('ui.card.vote_down') }}"
                    aria-pressed="{{ $isDownvoted ? 'true' : 'false' }}"
                    class="flex size-11 items-center justify-center rounded-lg focus-visible:outline-none focus-visible:shadow-focus {{ $isDownvoted ? 'bg-ach text-surface' : 'text-muted' }}"
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
                        <h3 class="text-lg text-balance text-ink">{{ $title }}</h3>
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
            class="flex h-13 w-full items-center justify-center gap-2.5 rounded-lg bg-accent text-md font-extrabold text-on-accent focus-visible:outline-none focus-visible:shadow-focus"
        >
            <x-lucide-copy class="size-5" aria-hidden="true" />
            {{ __('ui.card.copy') }}
        </button>

        <div class="-mx-1.5 flex items-center gap-0.5">
            <button
                type="button"
                aria-pressed="{{ $saved ? 'true' : 'false' }}"
                class="flex h-11 items-center gap-2 rounded-md px-2.5 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $saved ? 'text-vote' : 'text-ink' }}"
            >
                <x-lucide-bookmark class="size-5 {{ $saved ? 'fill-current' : 'fill-none' }}" aria-hidden="true" />
                {{ $saved ? __('ui.card.saved') : __('ui.card.save') }}
            </button>
            <button
                type="button"
                class="flex h-11 items-center gap-2 rounded-md px-2.5 text-sm font-semibold text-ink focus-visible:outline-none focus-visible:shadow-focus"
            >
                <x-lucide-upload class="size-5" aria-hidden="true" />
                {{ __('ui.card.share') }}
            </button>
            <span class="flex-1"></span>
            <button
                type="button"
                aria-label="{{ __('ui.card.more') }}"
                class="flex size-11 items-center justify-center rounded-md text-muted focus-visible:outline-none focus-visible:shadow-focus"
            >
                <x-lucide-ellipsis class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</article>
