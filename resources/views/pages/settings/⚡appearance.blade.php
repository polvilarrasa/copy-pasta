<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        @php
            $currentTheme = app(\App\Support\ThemePreference::class)->current();
        @endphp

        <div class="inline-flex gap-1 rounded-full bg-surface-2 p-1" role="group" aria-label="{{ __('Appearance settings') }}">
            @foreach ([
                ['value' => \App\Enums\Theme::Light, 'label' => __('Light'), 'icon' => 'sun'],
                ['value' => \App\Enums\Theme::Dark, 'label' => __('Dark'), 'icon' => 'moon'],
                ['value' => \App\Enums\Theme::System, 'label' => __('System'), 'icon' => 'monitor'],
            ] as $option)
                <form method="POST" action="{{ route('theme.update') }}">
                    @csrf
                    <input type="hidden" name="theme" value="{{ $option['value']->value }}">
                    <button
                        type="submit"
                        class="flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus {{ $currentTheme === $option['value'] ? 'bg-accent text-on-accent' : 'text-muted' }}"
                    >
                        <x-dynamic-component :component="'lucide-'.$option['icon']" class="size-4" aria-hidden="true" />
                        {{ $option['label'] }}
                    </button>
                </form>
            @endforeach
        </div>
    </x-pages::settings.layout>
</section>
