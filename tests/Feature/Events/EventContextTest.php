<?php

declare(strict_types=1);

use App\Support\EventContext;
use Illuminate\Http\Request;

test('acepta el origen, la posición y la referencia con el formato esperado', function (): void {
    $request = Request::create('/', 'POST', ['source' => 'top_week', 'position' => 4, 'ref' => 'aB3dE9fG']);

    expect(EventContext::fromRequest($request))->toBe([
        'source' => 'top_week',
        'position' => 4,
        'ref' => 'aB3dE9fG',
    ]);
});

test('lee el contexto de la query de la página de detalle', function (): void {
    $request = Request::create('/c/x?from=random&pos=7&ref=aB3dE9fG');

    expect(EventContext::fromRequest($request))->toBe([
        'source' => 'random',
        'position' => 7,
        'ref' => 'aB3dE9fG',
    ]);
});

test('descarta los valores que no tienen el formato esperado', function (): void {
    $request = Request::create('/', 'POST', [
        'source' => 'DROP TABLE',
        'position' => 1001,
        'ref' => '<script>',
    ]);

    expect(EventContext::fromRequest($request))->toBe([]);
});
