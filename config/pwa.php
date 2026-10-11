<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Web app manifest
    |--------------------------------------------------------------------------
    |
    | The colors are the light and dark `bg` tokens of the design system (docs/design/tokens.css). The manifest can only
    | carry one, so it takes the light one; the two <meta name="theme-color"> tags in the page follow the color scheme.
    |
    */

    'name' => 'copy-pastas',

    'theme_color' => '#F3F2FF',

    'theme_color_dark' => '#0E0D1A',

    /*
    |--------------------------------------------------------------------------
    | Service worker kill switch
    |--------------------------------------------------------------------------
    |
    | With PWA_KILL_SWITCH=true, /sw.js serves a worker that clears every cache, unregisters itself and does nothing
    | else, and the pages stop registering a worker and unregister any they find. See "Kill switch del service worker"
    | in docs/PLAN-V2.md.
    |
    */

    'kill_switch' => (bool) env('PWA_KILL_SWITCH', false),

    /*
    |--------------------------------------------------------------------------
    | Precached fonts
    |--------------------------------------------------------------------------
    |
    | Name prefixes of the .woff2 files the offline page needs (Spanish text and the ASCII art). The service worker
    | precaches the assets of the stylesheet that start with one of them; every other font is cached when it is first
    | used.
    |
    */

    'precache_fonts' => [
        'bricolage-grotesque-latin-wght-normal',
        'jetbrains-mono-latin-400-normal',
    ],

];
