<!DOCTYPE html>
@php
    $currentUser = auth()->user();
    $publishUrl = match (true) {
        $currentUser === null => route('login'),
        $currentUser->hasVerifiedEmail() => url('/app/copypastas/create'),
        default => route('verification.notice'),
    };
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title ?? null])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <x-impersonation-banner />
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">{{ config('app.name') }}</a>

            <form method="GET" action="{{ route('home') }}" role="search" class="order-last w-full sm:order-none sm:flex-1">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('public.layout.search_placeholder') }}"
                    aria-label="{{ __('public.layout.search_placeholder') }}"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-zinc-900 focus:outline-none"
                >
            </form>

            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ $publishUrl }}"
                   class="rounded-lg bg-zinc-900 px-3 py-2 font-medium text-white hover:bg-zinc-700">
                    {{ __('public.layout.publish') }}
                </a>

                @auth
                    @can('access-admin')
                        <a href="{{ url('/admin') }}" class="px-2 py-2 text-zinc-600 hover:text-zinc-900">{{ __('public.layout.admin') }}</a>
                    @endcan
                    <a href="{{ url('/app') }}" class="px-2 py-2 text-zinc-600 hover:text-zinc-900">{{ __('public.layout.dashboard') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-2 py-2 text-zinc-600 hover:text-zinc-900">{{ __('public.layout.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-2 py-2 text-zinc-600 hover:text-zinc-900">{{ __('public.layout.login') }}</a>
                    <a href="{{ route('register') }}" class="px-2 py-2 text-zinc-600 hover:text-zinc-900">{{ __('public.layout.register') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-3xl px-4 py-8 text-center text-xs text-zinc-500">
        <a href="{{ route('normas') }}" class="underline hover:text-zinc-900">{{ __('public.layout.rules') }}</a>
        <a href="{{ route('privacy') }}" class="underline hover:text-zinc-900">{{ __('public.layout.privacy') }}</a>
        <a href="{{ route('cookies') }}" class="underline hover:text-zinc-900">{{ __('public.layout.cookies') }}</a>
    </footer>

    <div
        x-data="{ show: false, message: '' }"
        x-on:toast.window="message = $event.detail; show = true; setTimeout(() => show = false, 2500)"
        x-show="show"
        x-cloak
        role="status"
        aria-live="polite"
        class="fixed inset-x-0 bottom-4 z-50 flex justify-center px-4"
    >
        <div class="rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white shadow-lg" x-text="message"></div>
    </div>

    <div
        x-data="{ open: false, message: '' }"
        x-on:login-required.window="message = $event.detail; open = true"
        x-show="open"
        x-cloak
        role="dialog"
        aria-modal="true"
        aria-labelledby="login-modal-title"
        class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-900/50 px-4"
        x-on:keydown.escape.window="open = false"
    >
        <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl" x-on:click.outside="open = false">
            <h2 id="login-modal-title" class="text-lg font-semibold">{{ __('public.login_modal.title') }}</h2>
            <p class="mt-2 text-sm text-zinc-600" x-text="message"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">
                    {{ __('public.login_modal.cancel') }}
                </button>
                <a href="{{ route('login') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                    {{ __('public.login_modal.confirm') }}
                </a>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
