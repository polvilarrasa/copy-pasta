<?php

declare(strict_types=1);

use App\Support\OgImageRenderer;
use App\Support\OgImageRenderFailed;
use App\Support\UnicodeText;

/**
 * These tests run the real pango-view, which Sail, CI and the production image install.
 */
function sampleTitle(): string
{
    return 'Mañana será otro día, ¿qué más da? 😂🔥';
}

function sampleBody(): string
{
    return implode("\n", [
        'Había una vez un pingüino con ñoñería y canción: áéíóú ÁÉÍÓÚ üÜ ¡¿ñ?!',
        'Familia 👨‍👩‍👧‍👦 arcoíris 🏳️‍🌈 piel 👍🏽 España 🇪🇸 corazón ❤️ teclas 1️⃣2️⃣',
        'مرحبا بالعالم، هذا نص من اليمين إلى اليسار',
        'שלום עולם, זה טקסט מימין לשמאל',
        'Mezcla: Hola مرحبا mundo שלום fin. Y un párrafo bastante largo para comprobar que el ajuste de línea funciona.',
        '日本語のテキスト、中文文本，한국어 텍스트',
        'Línea de más que debe quedar fuera con puntos suspensivos',
    ]);
}

test('el texto de la prueba (acentos, ñ, emojis compuestos, RTL y CJK) se dibuja en un PNG de 1200 × 630', function (): void {
    $png = app(OgImageRenderer::class)->render(sampleTitle(), sampleBody());

    [$width, $height, $type] = getimagesizefromstring($png);

    expect($width)->toBe(1200)->and($height)->toBe(630)->and($type)->toBe(IMAGETYPE_PNG);
});

test('el texto del usuario es texto plano: el marcado y los caracteres de shell no se interpretan ni rompen la imagen', function (): void {
    $png = app(OgImageRenderer::class)->render('<b>Hola & "$(touch /tmp/pwned)" `id` <i', "<span foreground='red'>x</span> --markup -o /tmp/pwned.png\n; rm -rf / #");

    expect(getimagesizefromstring($png))->toMatchArray([0 => 1200, 1 => 630])
        ->and(file_exists('/tmp/pwned'))->toBeFalse()
        ->and(file_exists('/tmp/pwned.png'))->toBeFalse();
});

test('un texto enorme o con zalgo sigue produciendo una imagen del tamaño de la tarjeta', function (): void {
    $zalgo = 'Z'.str_repeat("\u{0300}\u{0301}\u{0302}\u{0303}\u{0316}\u{0317}", 150).'algo';

    $png = app(OgImageRenderer::class)->render(str_repeat('Título largo ', 60), str_repeat($zalgo.' '.str_repeat('palabra ', 50)."\n", 40));

    expect(getimagesizefromstring($png))->toMatchArray([0 => 1200, 1 => 630]);
});

test('un título vacío o solo de caracteres invisibles no rompe la imagen', function (): void {
    $png = app(OgImageRenderer::class)->render("\u{202E}\u{200B}", '');

    expect(getimagesizefromstring($png))->toMatchArray([0 => 1200, 1 => 630]);
});

test('la imagen genérica tiene el tamaño de la tarjeta', function (): void {
    expect(getimagesizefromstring(app(OgImageRenderer::class)->renderGeneric()))->toMatchArray([0 => 1200, 1 => 630]);
});

test('un proceso que supera el límite de tiempo se aborta con OgImageRenderFailed', function (): void {
    $script = tempnam(sys_get_temp_dir(), 'slow-pango-');
    file_put_contents($script, "#!/bin/sh\nsleep 5\n");
    chmod($script, 0755);

    try {
        $started = microtime(true);

        expect(fn () => (new OgImageRenderer(pangoView: $script, timeout: 1))->render('Título', 'Cuerpo'))
            ->toThrow(OgImageRenderFailed::class, 'límite');

        expect(microtime(true) - $started)->toBeLessThan(4.0);
    } finally {
        @unlink($script);
    }
});

test('un pango-view que falla se convierte en OgImageRenderFailed', function (): void {
    expect(fn () => (new OgImageRenderer(pangoView: 'false'))->render('Título', 'Cuerpo'))
        ->toThrow(OgImageRenderFailed::class);
});

test('antes de dibujar se neutralizan los controles de dirección y los de ancho cero, menos el unión de emojis', function (): void {
    $text = "a\u{202E}b\u{2066}c\u{200B}d\u{200C}e\u{FEFF}f 👨\u{200D}👩";

    expect(UnicodeText::forRendering($text))->toBe("abcdef 👨\u{200D}👩");
});
