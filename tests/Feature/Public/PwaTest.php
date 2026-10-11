<?php

declare(strict_types=1);

use App\Support\ServiceWorkerAssets;
use Illuminate\Support\Facades\DB;

test('el manifest es JSON válido con lo que exige la instalabilidad', function (): void {
    $response = $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    $manifest = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray([
        'name' => 'copy-pastas',
        'short_name' => 'copy-pastas',
        'lang' => 'es',
        'start_url' => '/?source=pwa',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#F3F2FF',
        'theme_color' => '#F3F2FF',
    ]);

    $icons = collect($manifest['icons']);

    expect($icons->firstWhere('sizes', '192x192'))->toMatchArray(['purpose' => 'any', 'type' => 'image/png'])
        ->and($icons->where('sizes', '512x512')->pluck('purpose')->sort()->values()->all())->toBe(['any', 'maskable']);

    foreach ($icons as $icon) {
        [$width, $height] = getimagesize(public_path($icon['src'])) ?: [0, 0];

        expect(sprintf('%dx%d', $width, $height))->toBe($icon['sizes']);
    }
});

test('el manifest declara el share target hacia /publicar con title y text', function (): void {
    $manifest = $this->get('/manifest.webmanifest')->json();

    expect($manifest['share_target'])->toBe([
        'action' => '/publicar',
        'method' => 'GET',
        'params' => ['title' => 'title', 'text' => 'text'],
    ]);
});

test('el manifest, el service worker y la página offline no inician sesión ni ponen cookies', function (string $uri): void {
    $response = $this->get($uri)->assertOk();

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('Content-Security-Policy'))->toContain("worker-src 'self'");
})->with(['/manifest.webmanifest', '/sw.js', '/sin-conexion']);

test('/sw.js se sirve con el tipo correcto, alcance raíz y sin caché', function (): void {
    $this->get('/sw.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/javascript; charset=utf-8')
        ->assertHeader('Service-Worker-Allowed', '/')
        ->assertHeader('Cache-Control', 'no-cache, private');
});

test('el service worker lleva la versión del build y precarga la página offline', function (): void {
    $assets = app(ServiceWorkerAssets::class);
    $script = (string) $this->get('/sw.js')->getContent();

    expect($script)
        ->toContain('copypastas-')
        ->toContain(json_encode($assets->version()))
        ->toContain(json_encode($assets->precache()))
        ->toContain('"\/sin-conexion"');
});

test('el service worker no toca POST, Livewire, /admin ni /notificaciones y nunca guarda páginas', function (): void {
    $script = (string) $this->get('/sw.js')->getContent();

    expect($script)
        ->toContain("request.method !== 'GET'")
        ->toContain("['/livewire', '/admin', '/notificaciones']")
        ->toContain("request.mode === 'navigate'")
        ->toContain("url.pathname.startsWith('/build/')");
});

test('con el kill switch /sw.js es un worker que se desregistra y las páginas dejan de registrarlo', function (): void {
    config(['pwa.kill_switch' => true]);

    $script = (string) $this->get('/sw.js')->assertOk()->getContent();

    expect($script)
        ->toContain('registration.unregister()')
        ->toContain('caches.delete')
        ->not->toContain('PRECACHE');

    $this->get('/')->assertDontSee('name="service-worker"', false);
});

test('sin el kill switch las páginas anuncian el worker, el manifest y los colores del tema', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="service-worker" content="/sw.js">', false)
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<meta name="theme-color" content="#F3F2FF" media="(prefers-color-scheme: light)">', false)
        ->assertSee('<meta name="theme-color" content="#0E0D1A" media="(prefers-color-scheme: dark)">', false);
});

test('la página offline es estática: sin consultas, sin CSRF y con el botón Reintentar', function (): void {
    // Livewire remembers across requests, in a test process, that a component rendered; a real request starts clean.
    \Livewire\trigger('flush-state');

    DB::enableQueryLog();

    $this->get('/sin-conexion')
        ->assertOk()
        ->assertSee('Sin conexión')
        ->assertSee('Reintentar')
        ->assertDontSee('csrf-token', false)
        ->assertDontSee('livewire', false);

    expect(DB::getQueryLog())->toBe([]);
});
