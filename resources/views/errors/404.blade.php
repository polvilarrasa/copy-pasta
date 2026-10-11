@php
    $art = <<<'ART'
  /\_/\
 ( ?.? )
  > ^ <
ART;
@endphp

<x-layouts::public :title="__('public.errors.404.title')">
    <x-state-card :art="$art" :code="'404'" :title="__('public.errors.404.title')">
        {{ __('public.errors.404.body') }}

        <x-slot:actions>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.back_home') }}</a>
        </x-slot:actions>
    </x-state-card>
</x-layouts::public>
