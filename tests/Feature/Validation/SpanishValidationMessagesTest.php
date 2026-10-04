<?php

declare(strict_types=1);

test('los errores de validación de un formulario salen en español', function (): void {
    $response = $this->post(route('register.store'), [
        'username' => 'johndoe',
        'email' => '',
        'password' => 'password',
        'password_confirmation' => 'distinta',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'El campo email es obligatorio.',
        'password' => 'La confirmación de contraseña no coincide.',
    ]);
});
