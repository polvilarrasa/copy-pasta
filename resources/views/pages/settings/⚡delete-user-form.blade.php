<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg text-ink">{{ __('Delete account') }}</h3>
        <p class="text-base text-muted">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-ui.button
        variant="danger"
        data-test="delete-user-button"
        x-on:click="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('Delete account') }}
    </x-ui.button>

    <livewire:pages::settings.delete-user-modal />
</section>
