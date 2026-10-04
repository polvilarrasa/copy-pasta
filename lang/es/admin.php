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
    ],

    'filters' => [
        'hidden' => 'Visibilidad',
        'hidden_yes' => 'Solo ocultos',
        'hidden_no' => 'Solo visibles',
        'nsfw' => 'NSFW',
        'nsfw_yes' => 'Solo NSFW',
        'nsfw_no' => 'Excluir NSFW',
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
