<section class="flex flex-col gap-4">
    @if ($copypastas->isEmpty())
        <x-ui.empty-state :title="__('public.public_folder.empty_title')">
            {{ __('public.public_folder.empty_body') }}
        </x-ui.empty-state>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($copypastas as $copypasta)
                <x-copypasta-card :copypasta="$copypasta" source="public_folder" wire:key="public-folder-copypasta-{{ $copypasta->id }}" />
            @endforeach
        </div>

        @if ($hasMore)
            <div class="flex justify-center">
                <x-ui.button variant="secondary" wire:click="loadMore">{{ __('public.feed.load_more') }}</x-ui.button>
            </div>
        @endif
    @endif
</section>
