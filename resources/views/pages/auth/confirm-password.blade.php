<x-layouts::auth :title="__('auth.confirm_password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('auth.confirm_password.title')"
            :description="__('auth.confirm_password.description')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('auth.confirm_password.passkey')"
            :loading-label="__('auth.confirm_password.passkey_confirming')"
            :separator="__('auth.confirm_password.or_password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <x-ui.password-input
                name="password"
                :label="__('auth.register.password')"
                required
                autocomplete="current-password"
                :placeholder="__('auth.register.password')"
            />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth.confirm_password.submit') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
