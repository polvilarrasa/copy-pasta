<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    /**
     * The web app manifest. Share target: Android offers the app in the share sheet and opens /publicar with the shared
     * text in `title` and `text`; the form fills itself in from them.
     */
    public function __invoke(): JsonResponse
    {
        $manifest = [
            'id' => '/',
            'name' => config('pwa.name'),
            'short_name' => config('pwa.name'),
            'description' => __('pwa.description'),
            'lang' => 'es',
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => config('pwa.theme_color'),
            'theme_color' => config('pwa.theme_color'),
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'share_target' => [
                'action' => route('copypastas.create', absolute: false),
                'method' => 'GET',
                'params' => ['title' => 'title', 'text' => 'text'],
            ],
        ];

        return response()->json($manifest, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
