<x-layouts::auth :title="__('auth.verify_email.title')">
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center text-base text-muted">
            {{ __('auth.verify_email.body') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center text-base font-semibold text-ok">
                {{ __('auth.verify_email.resent') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" variant="primary" class="w-full">
                    {{ __('auth.verify_email.resend') }}
                </x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('auth.verify_email.log_out') }}
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts::auth>
