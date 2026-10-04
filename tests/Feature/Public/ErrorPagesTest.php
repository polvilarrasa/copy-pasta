<?php

declare(strict_types=1);

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
