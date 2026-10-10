<x-layouts::auth :title="__('auth.register.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.register.title')" :description="__('auth.register.description')" />

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
                :label="__('auth.login.email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
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
                <x-ui.button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('auth.register.submit') }}
                </x-ui.button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-muted">
            <span>{{ __('auth.register.has_account') }}</span>
            <a class="font-semibold text-ink" href="{{ route('login') }}" wire:navigate>{{ __('auth.register.log_in') }}</a>
        </div>
    </div>
</x-layouts::auth>
