<x-ui.user-menu :user="auth()->user()" {{ $attributes }}>
    <div class="flex items-center gap-2 px-2 py-1.5">
        <x-avatar :user="auth()->user()" />
        <div class="grid flex-1 text-start leading-tight">
            <p class="truncate text-sm font-bold text-ink">{{ auth()->user()->username }}</p>
            <p class="truncate text-sm text-muted">{{ auth()->user()->email }}</p>
        </div>
    </div>

    <x-ui.menu-item href="{{ route('profile.edit') }}" wire:navigate>
        <x-lucide-settings class="size-4" aria-hidden="true" />
        {{ __('Settings') }}
    </x-ui.menu-item>

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <x-ui.menu-item type="submit" class="w-full" data-test="logout-button">
            <x-lucide-log-out class="size-4" aria-hidden="true" />
            {{ __('Log out') }}
        </x-ui.menu-item>
    </form>
</x-ui.user-menu>
