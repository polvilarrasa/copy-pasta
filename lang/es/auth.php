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

    'login' => [
        'title' => 'Inicia sesión en tu cuenta',
        'description' => 'Escribe tu email y tu contraseña para entrar',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'remember' => 'Recuérdame',
        'forgot_password' => '¿Has olvidado tu contraseña?',
        'submit' => 'Iniciar sesión',
        'no_account' => '¿Todavía no tienes cuenta?',
        'sign_up' => 'Regístrate',
        'passkey' => 'Entrar con una passkey',
        'passkey_authenticating' => 'Autenticando…',
        'or_email' => 'O continúa con tu email',
    ],

    'register' => [
        'title' => 'Crea tu cuenta',
        'description' => 'Escribe tus datos para crear tu cuenta',
        'password' => 'Contraseña',
        'password_confirmation' => 'Repite la contraseña',
        'submit' => 'Crear cuenta',
        'has_account' => '¿Ya tienes cuenta?',
        'log_in' => 'Inicia sesión',
    ],

    'forgot_password' => [
        'title' => 'Recupera tu contraseña',
        'description' => 'Escribe tu email para recibir un enlace de recuperación',
        'submit' => 'Enviar enlace de recuperación',
        'back_to' => 'O vuelve a',
        'log_in' => 'iniciar sesión',
    ],

    'reset_password' => [
        'title' => 'Restablece tu contraseña',
        'description' => 'Escribe tu nueva contraseña',
        'email' => 'Email',
        'submit' => 'Restablecer contraseña',
    ],

    'confirm_password' => [
        'title' => 'Confirma tu contraseña',
        'description' => 'Esta es una zona segura de la aplicación. Confirma tu contraseña antes de continuar.',
        'passkey' => 'Confirmar con una passkey',
        'passkey_confirming' => 'Confirmando…',
        'or_password' => 'O confirma con tu contraseña',
        'submit' => 'Confirmar',
    ],

    'verify_email' => [
        'title' => 'Verificación de email',
        'body' => 'Verifica tu dirección de email haciendo clic en el enlace que te hemos enviado.',
        'resent' => 'Te hemos enviado un nuevo enlace de verificación a la dirección que indicaste al registrarte.',
        'resend' => 'Reenviar email de verificación',
        'log_out' => 'Cerrar sesión',
    ],

    'two_factor_challenge' => [
        'title' => 'Código de autenticación',
        'description' => 'Escribe el código de autenticación de tu aplicación.',
        'recovery_title' => 'Código de recuperación',
        'recovery_description' => 'Confirma el acceso a tu cuenta escribiendo uno de tus códigos de recuperación de emergencia.',
        'continue' => 'Continuar',
        'or' => 'o puedes',
        'use_recovery' => 'entrar con un código de recuperación',
        'use_code' => 'entrar con un código de autenticación',
    ],
];
