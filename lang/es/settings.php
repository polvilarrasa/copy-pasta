<?php

declare(strict_types=1);

return [

    'nav' => [
        'title' => 'Ajustes',
        'description' => 'Gestiona tu perfil y los ajustes de tu cuenta',
        'profile' => 'Perfil',
        'security' => 'Seguridad',
        'appearance' => 'Apariencia',
    ],

    'profile' => [
        'title' => 'Perfil',
        'description' => 'Actualiza tu nombre y tu email',
        'email' => 'Email',
        'save' => 'Guardar',
        'unverified_email' => 'Tu dirección de email no está verificada.',
        'resend_verification' => 'Haz clic aquí para reenviar el email de verificación.',
        'verification_resent' => 'Te hemos enviado un nuevo enlace de verificación a tu email.',
        'updated' => 'Perfil actualizado.',
    ],

    'security' => [
        'title' => 'Seguridad',
        'update_password' => [
            'title' => 'Actualizar contraseña',
            'description' => 'Asegúrate de que tu cuenta usa una contraseña larga y aleatoria para estar segura',
            'current_password' => 'Contraseña actual',
            'new_password' => 'Nueva contraseña',
            'confirm_password' => 'Repite la contraseña',
            'save' => 'Guardar',
            'updated' => 'Contraseña actualizada.',
        ],
        'two_factor' => [
            'title' => 'Autenticación en dos pasos',
            'description' => 'Gestiona los ajustes de tu autenticación en dos pasos',
            'enabled_body' => 'Te pediremos un código seguro y aleatorio al iniciar sesión, que puedes obtener desde la aplicación TOTP de tu teléfono.',
            'disabled_body' => 'Al activar la autenticación en dos pasos, te pediremos un código seguro al iniciar sesión. Este código se obtiene desde una aplicación TOTP en tu teléfono.',
            'disable' => 'Desactivar 2FA',
            'enable' => 'Activar 2FA',
        ],
        'passkeys' => [
            'title' => 'Passkeys',
            'description' => 'Gestiona tus passkeys para iniciar sesión sin contraseña',
            'empty_title' => 'Todavía no tienes passkeys',
            'empty_body' => 'Añade una passkey para iniciar sesión sin contraseña',
            'added' => 'Añadida :time',
            'last_used' => 'Usada por última vez :time',
            'remove' => 'Eliminar passkey',
            'remove_confirm' => 'Seguro que quieres eliminar la passkey «:name»? No podrás volver a usarla para iniciar sesión.',
        ],
    ],

    'appearance' => [
        'title' => 'Apariencia',
        'description' => 'Actualiza los ajustes de apariencia de tu cuenta',
        'light' => 'Claro',
        'dark' => 'Oscuro',
        'system' => 'Sistema',
    ],

    'delete_account' => [
        'title' => 'Eliminar cuenta',
        'description' => 'Elimina tu cuenta y todos sus recursos',
        'submit' => 'Eliminar cuenta',
        'confirm_title' => '¿Seguro que quieres eliminar tu cuenta?',
        'confirm_body' => 'Al eliminar tu cuenta, se eliminarán también todos sus recursos y datos. Escribe tu contraseña para confirmar que quieres eliminar tu cuenta de forma permanente.',
        'password' => 'Contraseña',
        'submit_confirm' => 'Eliminar cuenta',
    ],

    'two_factor_setup' => [
        'enable_title' => 'Activar la autenticación en dos pasos',
        'enable_description' => 'Para terminar de activarla, escanea el código QR o introduce la clave manual en tu aplicación de autenticación.',
        'verify_title' => 'Verifica el código de autenticación',
        'verify_description' => 'Escribe el código de 6 dígitos de tu aplicación de autenticación.',
        'enabled_title' => 'Autenticación en dos pasos activada',
        'enabled_description' => 'La autenticación en dos pasos ya está activa. Escanea el código QR o introduce la clave manual en tu aplicación de autenticación.',
        'continue' => 'Continuar',
        'close' => 'Cerrar',
        'back' => 'Atrás',
        'confirm' => 'Confirmar',
        'manual_key' => 'o, introduce el código manualmente',
        'copy' => 'Copiar',
    ],

    'recovery_codes' => [
        'title' => 'Códigos de recuperación de 2FA',
        'description' => 'Los códigos de recuperación te permiten volver a entrar si pierdes el acceso a tu dispositivo de 2FA. Guárdalos en un gestor de contraseñas seguro.',
        'view' => 'Ver códigos de recuperación',
        'hide' => 'Ocultar códigos de recuperación',
        'regenerate' => 'Regenerar códigos',
        'helper' => 'Cada código de recuperación solo se puede usar una vez y se elimina después de usarlo. Si necesitas más, pulsa Regenerar códigos.',
    ],

    'passkeys' => [
        'not_supported' => 'Las passkeys no están disponibles en este navegador.',
        'add' => 'Añadir passkey',
        'name_label' => 'Nombre de la passkey',
        'name_placeholder' => 'Por ejemplo, MacBook Pro, iPhone',
        'name_helper' => 'Ponle un nombre a esta passkey para identificarla más tarde.',
        'register' => 'Registrar passkey',
        'registering' => 'Registrando…',
        'cancel' => 'Cancelar',
    ],
];
