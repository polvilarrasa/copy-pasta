<x-layouts::public :title="$profileUser->username">
    <section class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center gap-5 rounded-3xl border border-border bg-surface p-6">
            <x-avatar :user="$profileUser" size="lg" />

            <div class="flex min-w-56 flex-1 flex-col gap-1">
                <h1 class="text-3xl font-extrabold tracking-tight text-ink bidi-isolate">{{ $profileUser->username }}</h1>
                @if ($profileUser->titleLabel())
                    <span data-test="profile-title" class="flex h-8 w-fit items-center rounded-full bg-surface-2 px-3.5 text-sm font-bold text-ach bidi-isolate">{{ $profileUser->titleLabel() }}</span>
                @endif
                <span class="text-sm font-semibold text-muted">
                    {{ __('public.profile.member_since', ['month' => $profileUser->created_at->translatedFormat('F'), 'year' => $profileUser->created_at->year]) }}
                </span>
            </div>

            <div class="grid w-full grid-cols-3 gap-2 sm:w-auto sm:min-w-80" aria-label="{{ __('public.profile.counters_label') }}">
                <div class="flex flex-col items-center gap-0.5 rounded-2xl bg-surface-2 px-2 py-4">
                    <span data-test="profile-counter-published" class="text-2xl font-extrabold tabular-nums">{{ \App\Support\Numbers::abbreviate((int) $counters->published) }}</span>
                    <span class="text-xs font-semibold text-muted">{{ __('public.profile.counter_published') }}</span>
                </div>
                <div class="flex flex-col items-center gap-0.5 rounded-2xl bg-surface-2 px-2 py-4">
                    <span data-test="profile-counter-copies" class="text-2xl font-extrabold tabular-nums">{{ \App\Support\Numbers::abbreviate((int) $counters->copies) }}</span>
                    <span class="text-xs font-semibold text-muted">{{ __('public.profile.counter_copies') }}</span>
                </div>
                <div class="flex flex-col items-center gap-0.5 rounded-2xl bg-surface-2 px-2 py-4">
                    <span data-test="profile-counter-upvotes" class="text-2xl font-extrabold tabular-nums">{{ \App\Support\Numbers::abbreviate((int) $counters->upvotes) }}</span>
                    <span class="text-xs font-semibold text-muted">{{ __('public.profile.counter_upvotes') }}</span>
                </div>
            </div>
        </div>

        @include('partials.profile-achievements', ['achievements' => $achievements])

        @if ($publicFolders->isNotEmpty())
            <section class="flex flex-col gap-3" aria-labelledby="profile-folders-title" data-test="profile-public-folders">
                <h2 id="profile-folders-title" class="text-xl font-extrabold text-ink">{{ __('public.public_folder.profile_title') }}</h2>

                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach ($publicFolders as $folder)
                        <li>
                            <a href="{{ route('folders.public', $folder->public_id) }}" class="flex h-full items-center gap-3 rounded-2xl border border-border bg-surface p-4 hover:border-vote focus-visible:outline-none focus-visible:shadow-focus">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-t3-bg text-t3-fg" aria-hidden="true">
                                    <x-lucide-folder class="size-6" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-ink bidi-isolate">{{ $folder->name }}</span>
                                    <span class="text-sm text-muted">{{ trans_choice('public.public_folder.profile_count', $folder->visible_count, ['count' => $folder->visible_count]) }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <livewire:profile-copypastas :user="$profileUser" />
    </section>
</x-layouts::public>
