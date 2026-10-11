{{-- What the page says when a Livewire request fails (see the livewire:init hook in resources/js/app.js): the "session
     expired" modal, and the texts of the toasts for "too fast" and "no network". --}}
<x-ui.modal id="session-expired" :title="__('pwa.request_failed.expired_title')" data-test="session-expired-modal">
    {{ __('pwa.request_failed.expired_body') }}

    <x-slot:actions>
        <x-ui.button type="button" variant="primary" x-on:click="window.location.reload()" data-test="session-expired-reload">
            {{ __('pwa.request_failed.reload') }}
        </x-ui.button>
    </x-slot:actions>
</x-ui.modal>

<div
    hidden
    data-request-errors
    data-rate-limited="{{ __('pwa.request_failed.rate_limited') }}"
    data-failed="{{ __('public.copy.action_failed') }}"
></div>
