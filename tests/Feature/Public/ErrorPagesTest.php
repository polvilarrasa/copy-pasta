<?php

declare(strict_types=1);

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

test('una ruta inexistente responde con la página 404 propia', function (): void {
    $this->get('/esta-pagina-no-existe')
        ->assertNotFound()
        ->assertSee('Página no encontrada');
});

test('la página 403 propia existe y explica el acceso denegado', function (): void {
    expect(view('errors.403')->render())->toContain('No tienes acceso a esta página');
});

test('las páginas de privacidad, cookies y normas están publicadas como borrador', function (): void {
    foreach (['/privacidad', '/cookies', '/normas'] as $uri) {
        $this->get($uri)->assertOk()->assertSee('pendiente de revisión legal');
    }
});

test('cada código de error tiene su página con el diseño y no la genérica de Laravel', function (int $status, string $title): void {
    Route::middleware('web')->get('/_prueba/error', fn () => abort($status));

    $this->get('/_prueba/error')
        ->assertStatus($status)
        ->assertSee($title)
        ->assertSee((string) $status)
        ->assertSee('<pre aria-hidden="true"', false);
})->with([
    [403, 'No tienes acceso a esta página'],
    [404, 'Página no encontrada'],
    [419, 'La sesión ha caducado'],
    [429, 'Vas demasiado rápido'],
    [500, 'Algo ha fallado'],
    [503, 'Volvemos enseguida'],
]);

test('la página 419 ofrece recargar volviendo a la página anterior', function (): void {
    $this->withSession(['_previous' => ['url' => url('/nuevos')]]);

    Route::middleware('web')->get('/_prueba/error', fn () => abort(419));

    $this->get('/_prueba/error')
        ->assertStatus(419)
        ->assertSee('data-test="error-reload"', false)
        ->assertSee('href="'.url('/nuevos').'"', false)
        ->assertSee(__('public.errors.reload'));
});

test('las páginas 500 y 503 se renderizan sin consultar la base de datos ni la sesión', function (int $status): void {
    DB::enableQueryLog();
    $session = Mockery::spy(Session::class);
    app()->instance('session.store', $session);

    $html = view('errors.'.$status)->render();

    expect($html)
        ->toContain(__('public.errors.'.$status.'.title'))
        ->not->toContain('csrf-token')
        ->not->toContain('livewire')
        ->and(DB::getQueryLog())->toBe([]);

    $session->shouldNotHaveReceived('token');
    $session->shouldNotHaveReceived('get');
})->with([500, 503]);

test('el modo mantenimiento responde con la página 503 propia', function (): void {
    $this->artisan('down')->assertSuccessful();

    try {
        $this->get('/')->assertStatus(503)->assertSee('Volvemos enseguida');
    } finally {
        $this->artisan('up');
    }
});
