@php
    $links = [
        'profile.edit' => __('settings.nav.profile'),
        'security.edit' => __('settings.nav.security'),
        'appearance.edit' => __('settings.nav.appearance'),
        'notifications.edit' => __('settings.nav.notifications'),
        'title.edit' => __('settings.nav.user_title'),
    ];
@endphp

<div class="flex items-start max-md:flex-col">
    <nav class="me-10 w-full max-w-56 pb-4" aria-label="{{ __('settings.nav.title') }}">
        <ul class="space-y-1">
            @foreach ($links as $route => $label)
                <li>
                    <a
                        href="{{ route($route) }}"
                        wire:navigate
                        @if (request()->routeIs($route)) aria-current="page" @endif
                        class="flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold {{ request()->routeIs($route) ? 'bg-surface-2 text-ink' : 'text-muted hover:bg-surface-2 hover:text-ink' }}"
                    >
                        {{ $label }}
                    </a>
                </li>
            @endforeach
            <li>
                <a
                    href="{{ route('welcome', ['modo' => 'editar']) }}"
                    wire:navigate
                    data-test="settings-favorite-tags"
                    class="flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-muted hover:bg-surface-2 hover:text-ink"
                >
                    {{ __('settings.nav.favorite_tags') }}
                </a>
            </li>
        </ul>
    </nav>

    <div class="w-full border-t border-border md:hidden"></div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg text-ink">{{ $heading ?? '' }}</h2>
        <p class="text-base text-muted">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
