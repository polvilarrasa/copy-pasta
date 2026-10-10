<section class="flex flex-col gap-4">
    <div role="group" aria-label="{{ __('public.profile.tabs_label') }}" class="flex w-fit gap-1 rounded-full bg-surface-2 p-1">
        <button
            type="button"
            wire:click="selectTab('top')"
            aria-pressed="{{ $tab === 'top' ? 'true' : 'false' }}"
            class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $tab === 'top' ? 'bg-accent text-on-accent' : 'text-muted' }}"
        >{{ __('public.profile.tab_top') }}</button>
        <button
            type="button"
            wire:click="selectTab('new')"
            aria-pressed="{{ $tab === 'new' ? 'true' : 'false' }}"
            class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $tab === 'new' ? 'bg-accent text-on-accent' : 'text-muted' }}"
        >{{ __('public.profile.tab_new') }}</button>
    </div>

    @if ($copypastas->isEmpty())
        <x-ui.empty-state :title="__('public.profile.empty_title')">
            {{ __('public.profile.empty_body') }}
        </x-ui.empty-state>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($copypastas as $copypasta)
                <x-copypasta-card :copypasta="$copypasta" source="profile" wire:key="profile-copypasta-{{ $copypasta->id }}" />
            @endforeach
        </div>

        @if ($hasMore)
            <div class="flex justify-center">
                <x-ui.button variant="secondary" wire:click="loadMore">{{ __('public.feed.load_more') }}</x-ui.button>
            </div>
        @endif
    @endif
</section>
