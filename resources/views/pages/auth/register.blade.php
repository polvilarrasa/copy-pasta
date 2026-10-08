<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Username -->
            <x-ui.input
                name="username"
                :label="__('auth.username')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                :placeholder="__('auth.username_placeholder')"
            />

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <x-ui.password-input
                name="password"
                :label="__('Password')"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <!-- Confirm Password -->
            <x-ui.password-input
                name="password_confirmation"
                :label="__('Confirm password')"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <div class="flex items-center justify-end">
                <x-ui.button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </x-ui.button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-muted">
            <span>{{ __('Already have an account?') }}</span>
            <a class="font-semibold text-ink" href="{{ route('login') }}" wire:navigate>{{ __('Log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
