<?php

declare(strict_types=1);

use App\Models\User;

test('un admin sin 2FA activo es enviado a configurarlo antes de entrar al panel', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('security.edit'));
});

test('un moderador sin 2FA activo también es enviado a configurarlo', function (): void {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->get('/admin')
        ->assertRedirect(route('security.edit'));
});

test('un admin con 2FA activo entra al panel', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});
