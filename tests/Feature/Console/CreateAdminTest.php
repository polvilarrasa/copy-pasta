<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

afterEach(fn () => putenv('ADMIN_PASSWORD'));

test('app:create-admin crea un administrador verificado sin terminal, con la contraseña del entorno', function (): void {
    putenv('ADMIN_PASSWORD=contraseña-segura-2026');

    $this->artisan('app:create-admin', [
        '--username' => 'primer_admin',
        '--email' => 'admin@example.com',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->username)->toBe('primer_admin')
        ->and(Hash::check('contraseña-segura-2026', $admin->password))->toBeTrue();
});

test('app:create-admin no crea nada si falta la contraseña en el entorno', function (): void {
    putenv('ADMIN_PASSWORD');

    $this->artisan('app:create-admin', [
        '--username' => 'sin_clave',
        '--email' => 'sin-clave@example.com',
        '--no-interaction' => true,
    ])->assertFailed();

    expect(User::query()->where('email', 'sin-clave@example.com')->exists())->toBeFalse();
});

test('app:create-admin rechaza una contraseña de menos de doce caracteres', function (): void {
    putenv('ADMIN_PASSWORD=corta');

    $this->artisan('app:create-admin', [
        '--username' => 'clave_corta',
        '--email' => 'corta@example.com',
        '--no-interaction' => true,
    ])->assertFailed();

    expect(User::query()->where('email', 'corta@example.com')->exists())->toBeFalse();
});

test('app:create-admin no duplica usuarios existentes', function (): void {
    putenv('ADMIN_PASSWORD=contraseña-segura-2026');
    User::factory()->create(['email' => 'repetido@example.com']);

    $this->artisan('app:create-admin', [
        '--username' => 'otro_nombre',
        '--email' => 'repetido@example.com',
        '--no-interaction' => true,
    ])->assertFailed();
});

test('el primer admin creado por comando es el propietario y los siguientes no', function (): void {
    putenv('ADMIN_PASSWORD=contraseña-segura-2026');

    $this->artisan('app:create-admin', ['--username' => 'primer_admin', '--email' => 'primero@example.com', '--no-interaction' => true])
        ->assertSuccessful();
    $this->artisan('app:create-admin', ['--username' => 'segundo_admin', '--email' => 'segundo@example.com', '--no-interaction' => true])
        ->assertSuccessful();

    expect(User::query()->where('email', 'primero@example.com')->sole()->is_owner)->toBeTrue()
        ->and(User::query()->where('email', 'segundo@example.com')->sole()->is_owner)->toBeFalse();
});
