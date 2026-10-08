<x-layouts::auth :title="__('auth.invitation.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.invitation.title')" :description="__('auth.invitation.description')" />

        <form method="POST" action="{{ request()->fullUrl() }}" class="flex flex-col gap-6">
            @csrf

            <x-ui.password-input
                name="password"
                :label="__('auth.invitation.password')"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <x-ui.password-input
                name="password_confirmation"
                :label="__('auth.invitation.password_confirmation')"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" variant="primary" class="w-full" data-test="accept-invitation-button">
                    {{ __('auth.invitation.submit') }}
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts::auth>
