<?php

return [

    /*
    | Open Graph preview images. The disk must serve its files publicly at an absolute URL, because WhatsApp, X and the
    | rest fetch `og:image` from outside. Production uses an S3 compatible disk with its own domain.
    */

    'disk' => env('OG_IMAGES_DISK', 'public'),

    /*
    | Turned off in the test suite, where nothing renders unless a test asks for it.
    */

    'enabled' => (bool) env('OG_IMAGES_ENABLED', true),

    /*
    | Seconds a single pango-view process may run. A render that exceeds it falls back to the generic image.
    */

    'timeout' => (int) env('OG_RENDER_TIMEOUT', 10),

    'pango_view' => env('OG_PANGO_VIEW', 'pango-view'),

];
