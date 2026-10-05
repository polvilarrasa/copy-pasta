<?php

declare(strict_types=1);

return [

    'username' => 'Nombre de usuario',
    'username_placeholder' => 'Tu nombre de usuario',

    'banned' => 'Tu cuenta ha sido suspendida. Motivo: :reason',
    'banned_no_reason' => 'Tu cuenta ha sido suspendida.',
    'register_rate_limited' => 'Se han creado demasiadas cuentas desde tu conexión. Inténtalo más tarde.',

    'invitation' => [
        'mail' => [
            'subject' => 'Tu invitación a Copy-pastas',
            'heading' => 'Bienvenido a Copy-pastas',
            'body' => 'Un administrador ha creado tu cuenta. Elige tu contraseña para empezar a usarla.',
            'button' => 'Elegir contraseña',
            'expires' => 'El enlace caduca en :hours horas y solo sirve hasta que elijas tu contraseña.',
        ],
        'title' => 'Elige tu contraseña',
        'description' => 'Al guardarla confirmamos que este email es tuyo.',
        'password' => 'Contraseña',
        'password_confirmation' => 'Repite la contraseña',
        'submit' => 'Guardar y continuar',
        'accepted' => 'Contraseña guardada. Ya puedes iniciar sesión.',
    ],

    'username_cooldown' => 'Solo puedes cambiar tu usuario una vez cada :days días.',
];
