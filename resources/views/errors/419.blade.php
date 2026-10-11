@php
    $art = <<<'ART'
  .---.
 ( z.z )  zZz
  '---'
ART;
@endphp

<x-layouts::public :title="__('public.errors.419.title')">
    <x-state-card :art="$art" :code="'419'" :title="__('public.errors.419.title')">
        {{ __('public.errors.419.body') }}

        <x-slot:actions>
            <a href="{{ url()->previous(route('home')) }}" data-test="error-reload" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-md font-bold text-on-accent focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.reload') }}</a>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-2 px-4 text-md font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus">{{ __('public.errors.back_home') }}</a>
        </x-slot:actions>
    </x-state-card>
</x-layouts::public>
