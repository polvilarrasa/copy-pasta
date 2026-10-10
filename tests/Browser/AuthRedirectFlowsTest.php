<?php

declare(strict_types=1);

use App\Models\User;

test('un miembro que entra desde la pantalla de login acaba en el feed', function (): void {
    $member = User::factory()->create();

    visit('/login')
        ->fill('email', $member->email)
        ->fill('password', 'password')
        ->press('@login-button')
        ->assertPathIs('/');
});

test('un admin con 2FA entra en /admin tras el login', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    signInInBrowser($admin);

    visit('/admin')->assertPathIs('/admin');
});

test('un admin sin 2FA llega a confirmar la contraseña para configurarlo', function (): void {
    $admin = User::factory()->admin()->create();

    visit('/login')
        ->fill('email', $admin->email)
        ->fill('password', 'password')
        ->press('@login-button')
        ->assertPathIs(parse_url(route('password.confirm'), PHP_URL_PATH));
});
