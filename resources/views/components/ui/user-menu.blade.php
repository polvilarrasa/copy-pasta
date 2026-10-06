@props(['user'])

<x-ui.dropdown :label="__('ui.user_menu')" {{ $attributes }}>
    <x-slot:trigger>
        <span class="flex min-h-11 items-center gap-2 rounded-full bg-surface-2 py-1 pe-3 ps-1 text-sm font-semibold text-ink">
            <x-avatar :user="$user" />
            <span class="max-w-40 truncate">{{ $user->displayName() }}</span>
        </span>
    </x-slot:trigger>
    {{ $slot }}
</x-ui.dropdown>
