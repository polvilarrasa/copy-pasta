@php
    $tones = [
        't1' => 'bg-t1-bg text-t1-fg',
        't2' => 'bg-t2-bg text-t2-fg',
        't3' => 'bg-t3-bg text-t3-fg',
        't4' => 'bg-t4-bg text-t4-fg',
        't5' => 'bg-t5-bg text-t5-fg',
    ];
@endphp

<section class="mx-auto flex w-full max-w-2xl flex-col gap-8 px-4 py-10">
    <div class="flex flex-col gap-3">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ $editing ? __('public.welcome.edit_title') : __('public.welcome.title') }}</h1>
        <p class="text-base text-muted">{!! $editing ? e(__('public.welcome.edit_intro')) : __('public.welcome.intro', ['for_you' => '<strong>'.e(__('public.feed.for_you')).'</strong>']) !!}</p>
    </div>

    <div class="flex flex-col gap-3.5">
        <div class="flex items-center justify-between text-sm font-bold">
            <span data-test="welcome-count" class="rounded-full bg-surface-2 px-3 py-1 text-ink">{{ trans_choice('public.welcome.chosen', count($selected), ['count' => count($selected)]) }}</span>
            <span class="text-muted">{{ __('public.welcome.minimum', ['min' => $minimum]) }}</span>
        </div>

        <div class="flex flex-wrap gap-2.5" role="group" aria-label="{{ __('public.welcome.tags_label') }}">
            @foreach ($tags as $tag)
                @php($isSelected = in_array($tag->id, $selected, true))
                <button
                    type="button"
                    wire:click="toggle({{ $tag->id }})"
                    wire:key="welcome-tag-{{ $tag->id }}"
                    id="welcome-tag-{{ $tag->slug }}"
                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                    class="flex min-h-11 items-center gap-1.5 rounded-full px-4 text-md font-bold focus-visible:outline-none focus-visible:shadow-focus {{ $isSelected ? $tones[$tag->color].' shadow-focus' : 'bg-surface-2 text-ink' }}"
                >
                    @if ($isSelected)
                        <x-lucide-check class="size-4" aria-hidden="true" />
                    @endif
                    #{{ $tag->name }}
                </button>
            @endforeach
        </div>

        @error('tags')
            <p class="text-sm font-semibold text-bad" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <x-ui.button
            type="button"
            variant="primary"
            wire:click="save"
            data-test="welcome-save"
            :disabled="$missing > 0"
        >
            @if ($missing > 0)
                {{ trans_choice('public.welcome.choose_more', $missing, ['count' => $missing]) }}
            @else
                {{ $editing ? __('public.welcome.save') : __('public.welcome.see_feed') }}
            @endif
        </x-ui.button>

        @if ($editing)
            <a href="{{ route('home') }}" wire:navigate class="flex min-h-11 items-center text-sm font-semibold text-muted underline">{{ __('public.welcome.cancel') }}</a>
        @else
            <button type="button" wire:click="skip" data-test="welcome-skip" class="flex min-h-11 items-center text-sm font-semibold text-muted underline focus-visible:outline-none focus-visible:shadow-focus">
                {{ __('public.welcome.skip') }}
            </button>
            <span class="text-sm text-muted">{{ __('public.welcome.hint') }}</span>
        @endif
    </div>
</section>
