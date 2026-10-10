@php
    $canonicalUrl = route('folders.public', $folder->public_id);
    $description = $folder->description ?: __('public.public_folder.default_description', ['name' => $owner->displayName()]);
@endphp

<x-layouts::public :title="$folder->name">
    @push('meta')
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $folder->name }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ asset('images/og-generic.png') }}">
        <meta name="twitter:card" content="summary_large_image">
    @endpush

    <section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
        <div class="flex flex-wrap items-center gap-4 rounded-3xl border border-border bg-surface p-5">
            <span class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-t3-bg text-t3-fg" aria-hidden="true">
                <x-lucide-folder class="size-8" />
            </span>

            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-extrabold text-ink bidi-isolate" data-test="public-folder-name">{{ $folder->name }}</h1>
                @if ($folder->description)
                    <p class="text-sm text-muted bidi-isolate" data-test="public-folder-description">{{ $folder->description }}</p>
                @endif
                <p class="mt-1 text-sm font-semibold text-muted">
                    {{ __('public.public_folder.by') }}
                    @if ($owner->isAnonymized())
                        {{ $owner->displayName() }}
                    @else
                        <a href="{{ route('profile.show', $owner->username) }}" class="text-ink underline bidi-isolate">{{ $owner->username }}</a>
                    @endif
                </p>
            </div>
        </div>

        <livewire:public-folder-copypastas :folder="$folder" />
    </section>
</x-layouts::public>
