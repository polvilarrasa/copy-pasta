<x-layouts::auth :title="__('auth.reset_password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.reset_password.title')" :description="__('auth.reset_password.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <x-ui.input
                name="email"
                value="{{ request('email') }}"
                :label="__('auth.reset_password.email')"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <x-ui.password-input
                name="password"
                :label="__('auth.register.password')"
                required
                autocomplete="new-password"
                :placeholder="__('auth.register.password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <!-- Confirm Password -->
            <x-ui.password-input
                name="password_confirmation"
                :label="__('auth.register.password_confirmation')"
                required
                autocomplete="new-password"
                :placeholder="__('auth.register.password_confirmation')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    {{ __('auth.reset_password.submit') }}
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts::auth>
