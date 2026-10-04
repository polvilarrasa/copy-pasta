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

        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-200" x-data="copypastaActions({
            body: @js($copypasta->body),
            copyUrl: @js(route('copypastas.copy', $copypasta)),
            shareUrl: @js($shareUrl),
            shareTitle: @js($copypasta->title),
            copiedMessage: @js(__('public.copy.copied')),
            linkCopiedMessage: @js(__('public.copy.link_copied')),
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
                    <span>{{ __('public.card.by', ['username' => $copypasta->user->username]) }}</span>
                    <span>· {{ $copypasta->published_at?->diffForHumans() }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="copy()"
                        @if ($copypasta->is_nsfw) :disabled="! revealed" @endif
                        class="rounded-md px-3 py-2 font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 disabled:opacity-40"
                    >{{ __('public.card.copy') }}</button>
                    <button
                        type="button"
                        x-on:click="share()"
                        class="rounded-md px-3 py-2 font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50"
                    >{{ __('public.card.share') }}</button>
                </div>
            </footer>
        </article>

        <a href="{{ route('home') }}" class="inline-block text-sm text-zinc-600 underline hover:text-zinc-900">{{ __('public.show.back') }}</a>
    </section>
</x-layouts::public>
