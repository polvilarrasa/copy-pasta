<x-layouts::auth :title="__('auth.login.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.login.title')" :description="__('auth.login.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('auth.login.email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="grid gap-2">
                <x-ui.password-input
                    name="password"
                    :label="__('auth.login.password')"
                    required
                    autocomplete="current-password"
                    :placeholder="__('auth.login.password')"
                />

                @if (Route::has('password.request'))
                    <a class="justify-self-end text-sm font-semibold text-ink" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('auth.login.forgot_password') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <x-ui.checkbox name="remember" :label="__('auth.login.remember')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('auth.login.submit') }}
                </x-ui.button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-muted">
            <span>{{ __('auth.login.no_account') }}</span>
            <a class="font-semibold text-ink" href="{{ route('register') }}" wire:navigate>{{ __('auth.login.sign_up') }}</a>
        </div>
    </div>
</x-layouts::auth>
