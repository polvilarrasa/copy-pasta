@php
    $art = <<<'ART'
  /\_/\
 ( -.- )  zZz
  > ^ <
ART;
@endphp

<x-layouts::standalone :title="__('public.errors.503.title')">
    <x-state-card :art="$art" :code="'503'" :title="__('public.errors.503.title')">
        {{ __('public.errors.503.body') }}

        <x-slot:actions>
            <button type="button" onclick="window.location.reload()" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.retry') }}</button>
        </x-slot:actions>
    </x-state-card>
</x-layouts::standalone>
