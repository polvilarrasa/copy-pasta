<x-layouts::auth :title="__('auth.temporary_password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.temporary_password.title')" :description="__('auth.temporary_password.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.temporary.update') }}" class="flex flex-col gap-6">
            @csrf
            @method('PUT')

            <flux:input
                name="current_password"
                :label="__('auth.temporary_password.current_password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:input
                name="password"
                :label="__('auth.temporary_password.password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('auth.temporary_password.password_confirmation')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="temporary-password-button">
                    {{ __('auth.temporary_password.submit') }}
                </flux:button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">
                {{ __('auth.temporary_password.logout') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
