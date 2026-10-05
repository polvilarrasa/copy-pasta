<?php

declare(strict_types=1);

use App\Support\UnicodeText;

test('quita los caracteres de control de dirección del título', function (): void {
    expect(UnicodeText::cleanTitle("Hola\u{202E}mundo\u{202C}"))->toBe('Holamundo')
        ->and(UnicodeText::cleanTitle("\u{2066}Aislado\u{2069}"))->toBe('Aislado');
});

test('quita los caracteres de ancho cero y el BOM del título', function (): void {
    expect(UnicodeText::cleanTitle("a\u{200B}b\u{200C}c\u{FEFF}d"))->toBe('abcd');
});

test('conserva acentos, ñ y emojis simples', function (): void {
    expect(UnicodeText::cleanTitle('Acción ñandú 🔥'))->toBe('Acción ñandú 🔥');
});

test('recorta los espacios de los extremos', function (): void {
    expect(UnicodeText::cleanTitle("  \u{200B} Título \n"))->toBe('Título');
});

test('un título compuesto solo de caracteres invisibles queda vacío', function (): void {
    expect(UnicodeText::cleanTitle("\u{202E}\u{200B}\u{FEFF}"))->toBe('');
});
