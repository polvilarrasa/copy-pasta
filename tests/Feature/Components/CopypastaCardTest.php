<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

function renderCard(string $attributes = ''): string
{
    return Blade::render(
        '<x-ui.copypasta-card title="Carta" body="Hola" author="lola.exe" :author-hue="200" '.$attributes.' />',
    );
}

test('shows the template badge only for template copy-pastas', function (): void {
    expect(renderCard(':template="true"'))->toContain('Plantilla')
        ->and(renderCard())->not->toContain('Plantilla');
});

test('blurs sensitive content until the reader reveals it', function (): void {
    $html = renderCard(':nsfw="true"');

    expect($html)->toContain('blur-md')
        ->and($html)->toContain('Contenido sensible')
        ->and(renderCard())->not->toContain('blur-md');
});

test('shows the reason chip with the tag the reader likes', function (): void {
    $html = renderCard(":reason=\"['name' => 'humor', 'color' => 't2']\"");

    expect($html)->toContain('Porque te gusta')
        ->and($html)->toContain('#humor');
});

test('abbreviates scores from one thousand upwards with a decimal comma', function (): void {
    expect(renderCard(':score="1800"'))->toContain('1,8k')
        ->and(renderCard(':score="42"'))->toContain('>42<');
});

test('marks the featured copy-pasta of the day with its bar', function (): void {
    expect(renderCard(':featured="true" featured-date="5 oct"'))
        ->toContain('Copy-pasta del día')
        ->toContain('5 oct');
});
