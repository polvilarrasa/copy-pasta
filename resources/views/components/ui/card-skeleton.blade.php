@props(['count' => 1])

{{-- Placeholder with the shape of a copy-pasta card, for Livewire loads: <x-ui.card-skeleton wire:loading.delay wire:target="..." />. --}}
<div role="status" {{ $attributes->class(['space-y-4']) }}>
    <span class="sr-only">{{ __('ui.loading') }}</span>

    @for ($i = 0; $i < $count; $i++)
        <div aria-hidden="true" class="space-y-3 rounded-2xl border border-border bg-surface p-3.5">
            <x-ui.skeleton class="h-4 w-1/3" />
            <x-ui.skeleton class="h-6 w-3/4" />
            <x-ui.skeleton class="h-20 w-full" />
            <div class="flex gap-2">
                <x-ui.skeleton class="h-11 w-24" />
                <x-ui.skeleton class="h-11 w-16" />
                <x-ui.skeleton class="h-11 w-16" />
            </div>
        </div>
    @endfor
</div>
