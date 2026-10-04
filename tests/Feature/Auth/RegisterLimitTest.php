<?php

declare(strict_types=1);

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;

test('una misma IP registra como máximo cinco cuentas por hora', function (): void {
    foreach (range(1, CreateNewUser::MAX_REGISTRATIONS_PER_HOUR) as $index) {
        $this->post('/register', [
            'username' => 'miembro'.$index,
            'email' => 'miembro'.$index.'@example.com',
            'password' => 'contraseña-segura-123',
            'password_confirmation' => 'contraseña-segura-123',
        ])->assertRedirect();

        auth()->logout();
    }

    $this->post('/register', [
        'username' => 'miembro-extra',
        'email' => 'extra@example.com',
        'password' => 'contraseña-segura-123',
        'password_confirmation' => 'contraseña-segura-123',
    ])->assertStatus(429);

    expect(User::query()->where('email', 'extra@example.com')->exists())->toBeFalse();
});
