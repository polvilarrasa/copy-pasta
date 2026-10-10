<x-layouts::public :title="__('public.publish.title_create')">
    @if (auth()->user()->hasVerifiedEmail())
        <livewire:copypasta-form />
    @else
        <section class="mx-auto w-full max-w-md space-y-6 px-4 py-16 text-center">
            <h1 class="text-2xl font-extrabold text-ink">{{ __('public.publish.unverified_title') }}</h1>
            <p class="text-base text-muted">{{ __('auth.verify_email.body') }}</p>

            @if (session('status') === 'verification-link-sent')
                <p class="text-base font-semibold text-ok">{{ __('auth.verify_email.resent') }}</p>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" variant="primary" class="w-full">
                    {{ __('auth.verify_email.resend') }}
                </x-ui.button>
            </form>
        </section>
    @endif
</x-layouts::public>
