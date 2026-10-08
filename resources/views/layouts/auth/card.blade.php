<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @themeAttributes>
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-ink" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-3xl border border-border bg-surface shadow-day">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        <x-ui.toast />

        @livewireScripts
    </body>
</html>
