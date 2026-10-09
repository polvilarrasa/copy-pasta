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
            <div role="alert" class="rounded-lg bg-warn-bg p-3 text-sm font-semibold text-warn">
                {{ __('public.show.hidden_notice', ['reason' => $copypasta->hidden_reason]) }}
            </div>
        @elseif ($copypasta->published_at === null)
            <div role="alert" class="rounded-lg bg-surface-2 p-3 text-sm font-semibold text-ink">
                {{ __('public.show.unpublished_notice') }}
            </div>
        @endif

        <x-copypasta-card :copypasta="$copypasta" :context="$context" :full="true" />

        <div class="flex flex-wrap items-center gap-3 px-1 text-sm text-muted">
            @if ($copypasta->revisions->count() > 1)
                <details class="w-full">
                    <summary class="cursor-pointer font-semibold text-ink">{{ __('public.show.edited', ['date' => $copypasta->edited_at?->diffForHumans()]) }}</summary>
                    <ol class="mt-2 space-y-3">
                        @foreach ($copypasta->revisions->reverse()->skip(1) as $revision)
                            <li class="rounded-lg bg-surface-2 p-3">
                                <p class="font-semibold text-ink">{{ $revision->title }}</p>
                                <p class="whitespace-pre-wrap text-muted">{{ $revision->body }}</p>
                                <time class="text-muted">{{ $revision->created_at->diffForHumans() }}</time>
                            </li>
                        @endforeach
                    </ol>
                </details>
            @endif
            <a href="{{ route('notice.create', $copypasta) }}" class="underline">{{ __('public.notice.link') }}</a>
        </div>

        <a href="{{ route('home') }}" class="inline-block text-sm font-semibold text-ink underline">{{ __('public.show.back') }}</a>
    </section>
</x-layouts::public>
