<?php

declare(strict_types=1);

use App\Support\TextDiff;

test('un texto sin cambios es todo igual', function (): void {
    expect(TextDiff::words('hola mundo', 'hola  mundo'))->toBe([['type' => 'equal', 'text' => 'hola mundo']]);
});

test('marca las palabras añadidas y las quitadas', function (): void {
    expect(TextDiff::words('uno dos tres', 'uno cuatro tres'))->toBe([
        ['type' => 'equal', 'text' => 'uno'],
        ['type' => 'delete', 'text' => 'dos'],
        ['type' => 'insert', 'text' => 'cuatro'],
        ['type' => 'equal', 'text' => 'tres'],
    ]);
});

test('un texto vacío aparece entero como añadido o quitado', function (): void {
    expect(TextDiff::words('', 'nuevo'))->toBe([['type' => 'insert', 'text' => 'nuevo']])
        ->and(TextDiff::words('viejo', ''))->toBe([['type' => 'delete', 'text' => 'viejo']]);
});
