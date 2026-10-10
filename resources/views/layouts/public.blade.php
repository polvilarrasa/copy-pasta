<!DOCTYPE html>
@php
    $currentUser = auth()->user();
    $publishUrl = match (true) {
        $currentUser === null => route('login'),
        $currentUser->hasVerifiedEmail() => url('/app/copypastas/create'),
        default => route('verification.notice'),
    };
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @themeAttributes>
<head>
    @include('partials.head', ['title' => $title ?? null])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')
    @livewireStyles
</head>
<body class="min-h-screen bg-bg text-ink antialiased">
    <x-impersonation-banner />
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-3 px-4 py-3">
            <x-app-logo href="{{ route('home') }}" />

            <form method="GET" action="{{ route('home') }}" role="search" class="order-last w-full sm:order-none sm:flex-1">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('public.layout.search_placeholder') }}"
                    aria-label="{{ __('public.layout.search_placeholder') }}"
                    class="h-12 w-full rounded-lg border border-border bg-surface px-3.5 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus"
                >
            </form>

            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ $publishUrl }}" class="flex min-h-11 items-center rounded-lg bg-accent px-4 font-bold text-on-accent">
                    {{ __('public.layout.publish') }}
                </a>

                @auth
                    @can('access-admin')
                        <a href="{{ url('/admin') }}" class="flex min-h-11 items-center px-2 font-semibold text-muted hover:text-ink">{{ __('public.layout.admin') }}</a>
                    @endcan
                    <a href="{{ url('/app') }}" class="flex min-h-11 items-center px-2 font-semibold text-muted hover:text-ink">{{ __('public.layout.dashboard') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-11 items-center px-2 font-semibold text-muted hover:text-ink">{{ __('public.layout.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="flex min-h-11 items-center px-2 font-semibold text-muted hover:text-ink">{{ __('public.layout.login') }}</a>
                    <a href="{{ route('register') }}" class="flex min-h-11 items-center px-2 font-semibold text-muted hover:text-ink">{{ __('public.layout.register') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-3xl px-4 py-8 text-center text-xs text-muted">
        <a href="{{ route('normas') }}" class="underline hover:text-ink">{{ __('public.layout.rules') }}</a>
        <a href="{{ route('privacy') }}" class="underline hover:text-ink">{{ __('public.layout.privacy') }}</a>
        <a href="{{ route('cookies') }}" class="underline hover:text-ink">{{ __('public.layout.cookies') }}</a>
    </footer>

    <x-ui.toast />

    <div
        x-data="{ open: false, message: '' }"
        x-on:login-required.window="message = $event.detail; open = true"
        x-show="open"
        x-cloak
        role="dialog"
        aria-modal="true"
        aria-labelledby="login-modal-title"
        class="fixed inset-0 z-40 flex items-center justify-center bg-scrim px-4"
        x-on:keydown.escape.window="open = false"
    >
        <div class="w-full max-w-sm rounded-3xl border border-border bg-surface p-5 text-ink shadow-pop" x-on:click.outside="open = false">
            <h2 id="login-modal-title" class="text-xl font-extrabold">{{ __('public.login_modal.title') }}</h2>
            <p class="mt-3 text-base text-muted" x-text="message"></p>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" x-on:click="open = false">
                    {{ __('public.login_modal.cancel') }}
                </x-ui.button>
                <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-lg bg-accent px-4 font-bold text-on-accent">
                    {{ __('public.login_modal.confirm') }}
                </a>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
