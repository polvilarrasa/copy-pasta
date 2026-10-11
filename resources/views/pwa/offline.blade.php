@php
    $art = <<<'ART'
  /\_/\
 ( x.x )  . x .
  > ^ <
ART;
@endphp

<x-layouts::standalone :title="__('pwa.offline.title')">
    <x-state-card :art="$art" :title="__('pwa.offline.title')">
        {{ __('pwa.offline.body') }}

        <x-slot:actions>
            {{-- Inline handler: the offline page cannot rely on any script being cached. --}}
            <button type="button" onclick="window.location.reload()" data-test="offline-retry" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">
                {{ __('pwa.offline.retry') }}
            </button>
        </x-slot:actions>
    </x-state-card>
</x-layouts::standalone>
