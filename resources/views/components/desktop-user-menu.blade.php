<x-ui.user-menu :user="auth()->user()" {{ $attributes }}>
    <div class="flex items-center gap-2 px-2 py-1.5">
        <x-avatar :user="auth()->user()" />
        <div class="grid flex-1 text-start leading-tight">
            <p class="truncate text-sm font-bold text-ink">{{ auth()->user()->username }}</p>
            <p class="truncate text-sm text-muted">{{ auth()->user()->email }}</p>
        </div>
    </div>

    <x-ui.menu-item href="{{ route('copypastas.mine') }}" wire:navigate>
        <x-lucide-file-text class="size-4" aria-hidden="true" />
        {{ __('ui.mine') }}
    </x-ui.menu-item>

    <x-ui.menu-item href="{{ route('folders.index') }}" wire:navigate>
        <x-lucide-folder class="size-4" aria-hidden="true" />
        {{ __('ui.folders') }}
    </x-ui.menu-item>

    <x-ui.menu-item href="{{ route('stats.show') }}" wire:navigate>
        <x-lucide-bar-chart-2 class="size-4" aria-hidden="true" />
        {{ __('ui.stats') }}
    </x-ui.menu-item>

    <x-ui.menu-item href="{{ route('profile.edit') }}" wire:navigate>
        <x-lucide-settings class="size-4" aria-hidden="true" />
        {{ __('ui.settings') }}
    </x-ui.menu-item>

    {{-- Only when the browser offered the install prompt (beforeinstallprompt); resources/js/pwa.js keeps the state. --}}
    <div
        x-data="{ available: window.copypastasInstall?.available() ?? false }"
        x-on:pwa-install-state.window="available = window.copypastasInstall.available()"
        x-show="available"
        x-cloak
    >
        <x-ui.menu-item x-on:click="window.copypastasInstall.prompt()" data-test="install-app">
            <x-lucide-download class="size-4" aria-hidden="true" />
            {{ __('pwa.install.menu') }}
        </x-ui.menu-item>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <x-ui.menu-item type="submit" class="w-full" data-test="logout-button">
            <x-lucide-log-out class="size-4" aria-hidden="true" />
            {{ __('ui.sign_out') }}
        </x-ui.menu-item>
    </form>
</x-ui.user-menu>
