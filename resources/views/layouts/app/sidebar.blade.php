<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @themeAttributes>
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased" x-data="{ sidebarOpen: false }">
        <x-impersonation-banner />

        <div class="lg:flex">
            <div
                x-show="sidebarOpen"
                x-cloak
                x-on:click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-scrim lg:hidden"
            ></div>

            <aside
                x-on:keydown.escape.window="sidebarOpen = false"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 start-0 z-50 flex w-72 shrink-0 flex-col gap-4 border-e border-border bg-surface p-4 transition-transform lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0 lg:self-start"
            >
                <div class="flex items-center justify-between">
                    <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    <button type="button" x-on:click="sidebarOpen = false" class="rounded-lg p-2 text-muted lg:hidden" aria-label="{{ __('ui.close') }}">
                        <x-lucide-x class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <nav class="flex flex-col gap-1">
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif
                        class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-surface-2 text-ink' : 'text-muted hover:bg-surface-2 hover:text-ink' }}"
                    >
                        <x-lucide-home class="size-5" aria-hidden="true" />
                        {{ __('Dashboard') }}
                    </a>
                </nav>

                <div class="flex-1"></div>

                <nav class="flex flex-col gap-1">
                    <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-muted hover:bg-surface-2 hover:text-ink">
                        <x-lucide-folder-git-2 class="size-5" aria-hidden="true" />
                        {{ __('Repository') }}
                    </a>
                    <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-muted hover:bg-surface-2 hover:text-ink">
                        <x-lucide-book-open-text class="size-5" aria-hidden="true" />
                        {{ __('Documentation') }}
                    </a>
                </nav>

                <div class="hidden lg:block">
                    <x-desktop-user-menu />
                </div>
            </aside>

            <div class="flex min-h-screen flex-1 flex-col">
                <header class="flex items-center gap-2 border-b border-border bg-surface p-3 lg:hidden">
                    <button type="button" x-on:click="sidebarOpen = true" class="rounded-lg p-2 text-ink" aria-label="{{ __('ui.showcase.menu') }}">
                        <x-lucide-menu class="size-5" aria-hidden="true" />
                    </button>

                    <div class="flex-1"></div>

                    <x-desktop-user-menu />
                </header>

                {{ $slot }}
            </div>
        </div>

        <x-ui.toast />

        @livewireScripts
    </body>
</html>
