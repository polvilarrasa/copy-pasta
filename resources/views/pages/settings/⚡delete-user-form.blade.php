<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg text-ink">{{ __('settings.delete_account.title') }}</h3>
        <p class="text-base text-muted">{{ __('settings.delete_account.description') }}</p>
    </div>

    <x-ui.button
        variant="danger"
        data-test="delete-user-button"
        x-on:click="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('settings.delete_account.submit') }}
    </x-ui.button>

    <livewire:pages::settings.delete-user-modal />
</section>
