<?php

declare(strict_types=1);

return [

    'reason_required' => 'Indica un motivo para esta acción.',

    'models' => [
        'copypasta' => 'Copy-pasta',
        'copypastas' => 'Copy-pastas',
        'tag' => 'Etiqueta',
        'tags' => 'Etiquetas',
        'moderation_action' => 'Acción de moderación',
        'user' => 'Usuario',
        'users' => 'Usuarios',
        'moderation_actions' => 'Log de moderación',
    ],

    'navigation' => [
        'content' => 'Contenido',
        'moderation' => 'Moderación',
    ],

    'tag_colors' => [
        'red' => 'Rojo',
        'orange' => 'Naranja',
        'amber' => 'Ámbar',
        'green' => 'Verde',
        'teal' => 'Turquesa',
        'blue' => 'Azul',
        'indigo' => 'Índigo',
        'purple' => 'Morado',
        'pink' => 'Rosa',
        'gray' => 'Gris',
    ],

    'fields' => [
        'name' => 'Nombre',
        'slug' => 'Slug',
        'slug_helper' => 'Se genera a partir del nombre si se deja vacío.',
        'color' => 'Color',
        'is_active' => 'Activa',
        'title' => 'Título',
        'author' => 'Autor',
        'tags' => 'Etiquetas',
        'is_nsfw' => 'NSFW',
        'is_hidden' => 'Oculto',
        'score' => 'Score',
        'published_at' => 'Publicado',
        'created_at' => 'Creado',
        'actor' => 'Staff',
        'action' => 'Acción',
        'subject' => 'Sobre',
        'reason' => 'Motivo',
        'username' => 'Nombre de usuario',
        'email' => 'Email',
        'role' => 'Rol',
        'banned' => 'Baneado desde',
        'status' => 'Estado',
    ],

    'roles' => [
        'user' => 'Usuario',
        'moderator' => 'Moderador',
        'admin' => 'Admin',
    ],

    'filters' => [
        'hidden' => 'Visibilidad',
        'hidden_yes' => 'Solo ocultos',
        'hidden_no' => 'Solo visibles',
        'nsfw' => 'NSFW',
        'nsfw_yes' => 'Solo NSFW',
        'nsfw_no' => 'Excluir NSFW',
        'banned' => 'Baneo',
        'banned_yes' => 'Solo baneados',
        'banned_no' => 'Solo activos',
    ],

    'users' => [
        'tabs' => [
            'copypastas' => 'Copy-pastas',
            'reports_sent' => 'Reportes enviados',
            'moderation_log' => 'Historial de moderación',
        ],
        'empty' => [
            'copypastas' => 'Este usuario no ha publicado copy-pastas.',
            'reports_sent' => 'Este usuario no ha enviado reportes.',
            'moderation_log' => 'No hay acciones de moderación sobre este usuario.',
        ],
        'copypasta_status' => [
            'visible' => 'Visible',
            'hidden' => 'Oculto',
            'deleted' => 'Borrado',
        ],
        'actions' => [
            'ban' => 'Banear',
            'ban_heading' => 'Banear usuario',
            'ban_submit' => 'Banear',
            'unban' => 'Desbanear',
            'change_role' => 'Cambiar rol',
            'change_role_heading' => 'Cambiar rol del usuario',
            'send_reset' => 'Enviar enlace de reset',
            'send_reset_confirm' => 'Se enviará el enlace estándar para restablecer la contraseña al email del usuario. Nadie ve ni fija la contraseña desde aquí.',
            'send_reset_sent' => 'Enlace de restablecimiento enviado.',
            'send_reset_failed' => 'No se pudo enviar el enlace. Puede que se haya pedido hace poco.',
            'impersonate' => 'Actuar como',
            'impersonate_confirm' => 'Verás la web como este usuario. Mientras dure, no podrás cambiar su email ni su contraseña.',
        ],
    ],

    'actions' => [
        'hide' => 'Ocultar',
        'hide_heading' => 'Ocultar copy-pasta',
        'hide_submit' => 'Ocultar',
        'restore' => 'Restaurar',
        'restore_heading' => 'Restaurar copy-pasta',
        'mark_nsfw' => 'Marcar NSFW',
        'unmark_nsfw' => 'Quitar NSFW',
    ],

    'moderation_actions' => [
        'hide' => 'Ocultar',
        'restore' => 'Restaurar',
        'mark_nsfw' => 'Marcar NSFW',
        'unmark_nsfw' => 'Quitar NSFW',
        'dismiss_reports' => 'Descartar reportes',
        'ban' => 'Baneo',
        'unban' => 'Desbaneo',
        'change_role' => 'Cambio de rol',
        'send_password_reset' => 'Reset de contraseña',
        'impersonate_start' => 'Inicio de impersonación',
        'impersonate_end' => 'Fin de impersonación',
        'tag_created' => 'Etiqueta creada',
        'tag_updated' => 'Etiqueta editada',
    ],

];
