<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * What the service worker needs to know about the current build: a version that changes with every build, and the
 * files to precache so the offline page renders without the network.
 */
class ServiceWorkerAssets
{
    private const STYLESHEET = 'resources/css/app.css';

    /**
     * @param  string|null  $manifestPath  The Vite manifest; the build's own by default.
     */
    public function __construct(private ?string $manifestPath = null) {}

    /**
     * Short hash of the Vite manifest, so the cache name changes whenever any asset does. `dev` when there is no build.
     */
    public function version(): string
    {
        $path = $this->manifestPath();

        return is_file($path) ? substr((string) md5_file($path), 0, 10) : 'dev';
    }

    /**
     * Root-relative URLs: the offline page, its stylesheet and fonts, and the logo.
     *
     * @return list<string>
     */
    public function precache(): array
    {
        $urls = [route('pwa.offline', absolute: false), '/favicon.svg'];

        $entry = $this->manifest()[self::STYLESHEET] ?? null;

        if (! is_array($entry)) {
            return $urls;
        }

        $urls[] = '/build/'.$entry['file'];

        foreach ($entry['assets'] ?? [] as $asset) {
            $name = basename((string) $asset);

            if (Str::endsWith($name, '.woff2') && Str::startsWith($name, config('pwa.precache_fonts'))) {
                $urls[] = '/build/'.$asset;
            }
        }

        return $urls;
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = $this->manifestPath();

        if (! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest) ? $manifest : [];
    }

    private function manifestPath(): string
    {
        return $this->manifestPath ?? public_path('build/manifest.json');
    }
}
