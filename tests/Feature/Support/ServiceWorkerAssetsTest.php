<?php

declare(strict_types=1);

use App\Support\ServiceWorkerAssets;

function writeViteManifest(array $manifest): string
{
    $path = tempnam(sys_get_temp_dir(), 'vite-manifest');
    file_put_contents($path, json_encode($manifest));

    return $path;
}

test('precarga la página offline, el logo, la hoja de estilos y solo las fuentes latinas que necesita', function (): void {
    $path = writeViteManifest([
        'resources/css/app.css' => [
            'file' => 'assets/app-AAA.css',
            'assets' => [
                'assets/bricolage-grotesque-latin-wght-normal-X1.woff2',
                'assets/bricolage-grotesque-latin-ext-wght-normal-X2.woff2',
                'assets/jetbrains-mono-latin-400-normal-X3.woff2',
                'assets/jetbrains-mono-latin-400-normal-X4.woff',
                'assets/jetbrains-mono-latin-500-normal-X5.woff2',
                'assets/jetbrains-mono-greek-400-normal-X6.woff2',
            ],
        ],
        'resources/js/app.js' => ['file' => 'assets/app-BBB.js'],
    ]);

    expect((new ServiceWorkerAssets($path))->precache())->toBe([
        '/sin-conexion',
        '/favicon.svg',
        '/build/assets/app-AAA.css',
        '/build/assets/bricolage-grotesque-latin-wght-normal-X1.woff2',
        '/build/assets/jetbrains-mono-latin-400-normal-X3.woff2',
    ]);
});

test('la versión cambia con el contenido del manifiesto de Vite y es estable si no cambia', function (): void {
    $first = writeViteManifest(['resources/css/app.css' => ['file' => 'assets/app-AAA.css']]);
    $same = writeViteManifest(['resources/css/app.css' => ['file' => 'assets/app-AAA.css']]);
    $other = writeViteManifest(['resources/css/app.css' => ['file' => 'assets/app-CCC.css']]);

    expect((new ServiceWorkerAssets($first))->version())->toBe((new ServiceWorkerAssets($same))->version())
        ->and((new ServiceWorkerAssets($first))->version())->not->toBe((new ServiceWorkerAssets($other))->version());
});

test('sin build la versión es dev y solo se precarga la página offline y el logo', function (): void {
    $assets = new ServiceWorkerAssets('/ruta/que/no/existe.json');

    expect($assets->version())->toBe('dev')
        ->and($assets->precache())->toBe(['/sin-conexion', '/favicon.svg']);
});
