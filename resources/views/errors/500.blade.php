@php
    $art = <<<'ART'
  /\_/\
 ( @.@ )  ?!
  > ^ <
ART;
@endphp

<x-layouts::standalone :title="__('public.errors.500.title')">
    <x-state-card :art="$art" :code="'500'" :title="__('public.errors.500.title')">
        {{ __('public.errors.500.body') }}

        <x-slot:actions>
            <button type="button" onclick="window.location.reload()" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.retry') }}</button>
            <a href="/" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-2 px-4 text-md font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.back_home') }}</a>
        </x-slot:actions>
    </x-state-card>
</x-layouts::standalone>
