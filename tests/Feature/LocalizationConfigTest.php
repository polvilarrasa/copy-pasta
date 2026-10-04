<?php

declare(strict_types=1);

test('el idioma por defecto de la aplicación es español', function (): void {
    expect(config('app.locale'))->toBe('es');
    expect(config('app.fallback_locale'))->toBe('es');
});

test('la zona horaria por defecto es Europe/Madrid', function (): void {
    expect(config('app.timezone'))->toBe('Europe/Madrid');
});
