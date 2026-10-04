<?php

declare(strict_types=1);

use App\Http\Controllers\Public\NsfwConfirmationController;

test('confirmar +18 guarda una cookie de un año y vuelve a la ruta indicada', function (): void {
    $response = $this->post(route('nsfw.confirm'), ['redirect' => '/?nsfw=1']);

    $cookie = collect($response->headers->getCookies())
        ->firstWhere(fn ($cookie): bool => $cookie->getName() === NsfwConfirmationController::COOKIE);

    $response->assertRedirect('/?nsfw=1');

    expect($cookie->getExpiresTime() - time())->toBeGreaterThan(364 * 86400);
});

test('no redirige a destinos externos', function (): void {
    $this->post(route('nsfw.confirm'), ['redirect' => 'https://ejemplo.test/'])
        ->assertRedirect('/');

    $this->post(route('nsfw.confirm'), ['redirect' => '//ejemplo.test/'])
        ->assertRedirect('/');
});
