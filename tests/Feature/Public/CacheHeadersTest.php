<?php

declare(strict_types=1);

use App\Mail\UserInvitationMail;
use App\Models\User;

test('las páginas con datos sensibles no se guardan nunca', function (string $uri): void {
    $this->actingAs(User::factory()->create())
        ->get($uri)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
})->with(['/settings/profile', '/settings/appearance', '/settings/notifications', '/estadisticas', '/notificaciones']);

test('la página de invitación no se guarda nunca', function (): void {
    $invited = User::factory()->unverified()->create();
    preg_match('/href="([^"]*\/invitacion\/[^"]*)"/', (new UserInvitationMail($invited))->render(), $matches);

    $this->get(html_entity_decode($matches[1]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('las demás páginas de un miembro son privadas y se revalidan, pero no llevan no-store', function (string $uri): void {
    $response = $this->actingAs(User::factory()->create())->get($uri)->assertOk();

    expect($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('no-cache'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBeFalse()
        ->and($response->headers->has('Pragma'))->toBeFalse();
})->with(['/', '/mis-copypastas', '/carpetas', '/nuevos']);

test('las páginas con componentes Livewire no heredan el no-store que Livewire pone por su cuenta', function (): void {
    $response = $this->get('/')->assertOk()->assertSee('wire:id', false);

    expect($response->headers->hasCacheControlDirective('no-store'))->toBeFalse();
});

test('el feed de un visitante no lleva no-store', function (): void {
    $response = $this->get('/')->assertOk();

    expect($response->headers->hasCacheControlDirective('no-store'))->toBeFalse();
});

test('cerrar sesión pide al navegador vaciar su caché HTTP', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect()
        ->assertHeader('Clear-Site-Data', '"cache"');
});

test('el resto de respuestas no piden vaciar la caché', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/mis-copypastas')
        ->assertHeaderMissing('Clear-Site-Data');
});
