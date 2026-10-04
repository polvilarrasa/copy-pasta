<?php

declare(strict_types=1);

use App\Models\Copypasta;

test('el sitemap lista copy-pastas visibles y no NSFW, con sus URL canónicas', function (): void {
    $visible = Copypasta::factory()->create(['title' => 'Visible de prueba']);
    Copypasta::factory()->nsfw()->create();
    Copypasta::factory()->hidden()->create();
    Copypasta::factory()->unpublished()->create();
    $deleted = Copypasta::factory()->create();
    $deleted->delete();

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and($response->getContent())->toContain(route('copypastas.show', [$visible, $visible->slug]))
        ->and(substr_count($response->getContent(), '<url>'))->toBe(3 + 1);
});

test('robots.txt bloquea las zonas privadas y apunta al sitemap', function (): void {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->getContent())
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /app')
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});
