@php
    $art = <<<'ART'
  .-----.
  | x.x |
  '-----'
ART;
@endphp

<x-layouts::public :title="__('public.errors.403.title')">
    <x-state-card :art="$art" :code="'403'" :title="__('public.errors.403.title')">
        {{ __('public.errors.403.body') }}

        <x-slot:actions>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.back_home') }}</a>
        </x-slot:actions>
    </x-state-card>
</x-layouts::public>
