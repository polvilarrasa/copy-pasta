<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="{{ route('pwa.manifest', absolute: false) }}">
<meta name="theme-color" content="{{ config('pwa.theme_color') }}" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="{{ config('pwa.theme_color_dark') }}" media="(prefers-color-scheme: dark)">
@unless (config('pwa.kill_switch'))
    <meta name="service-worker" content="{{ route('pwa.service-worker', absolute: false) }}">
@endunless

@vite(['resources/css/app.css', 'resources/js/app.js'])
