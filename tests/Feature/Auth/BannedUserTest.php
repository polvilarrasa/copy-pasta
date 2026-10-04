<?php

declare(strict_types=1);

use App\Models\User;

test('un usuario baneado no puede iniciar sesión', function (): void {
    $user = User::factory()->banned('Spam reiterado')->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrorsIn('email');
    $this->assertGuest();
});

test('un usuario al que se banea mientras tiene sesión abierta es expulsado en la siguiente petición', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->forceFill([
        'banned_at' => now(),
        'ban_reason' => 'Contenido inapropiado',
    ])->save();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    $this->assertGuest();
});

test('un usuario baneado sin motivo recibe el mensaje genérico de suspensión', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->forceFill(['banned_at' => now()])->save();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    expect(session('status'))->toBe(__('auth.banned_no_reason'));
});
