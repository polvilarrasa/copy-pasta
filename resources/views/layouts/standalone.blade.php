{{-- A page that touches neither the database nor the session: no user, no CSRF token, no Livewire. The offline page and
     the 500 and 503 error pages use it, because they must render when everything else is down. The theme follows the
     system (prefers-color-scheme), since the user's own choice lives in the session. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />
    <meta name="theme-color" content="{{ config('pwa.theme_color') }}" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="{{ config('pwa.theme_color_dark') }}" media="(prefers-color-scheme: dark)">

    <title>{{ $title }} - {{ config('pwa.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-bg text-ink antialiased">
    <main class="flex min-h-svh flex-col items-center justify-center gap-6 px-4 py-10">
        <a href="/" class="flex items-center gap-2 text-md text-ink">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent text-on-accent">
                <x-app-logo-icon class="size-5 fill-current" />
            </span>
            {{ config('pwa.name') }}
        </a>

        {{ $slot }}
    </main>
</body>
</html>
