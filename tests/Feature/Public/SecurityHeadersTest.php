<?php

declare(strict_types=1);
use App\Models\User;

test('las respuestas web llevan cabeceras de seguridad y una CSP que permite a Livewire', function (): void {
    $response = $this->get('/')->assertOk();

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'")
        ->toContain("frame-ancestors 'none'")
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

test('las páginas de los paneles también llevan cabeceras de seguridad', function (): void {
    $this->actingAs(User::factory()->moderator()->create())
        ->get('/admin')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY');

    $this->actingAs(User::factory()->create())
        ->get('/app')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
