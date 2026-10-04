<?php

declare(strict_types=1);

return [

    'auto_hidden_reason' => 'Pendiente de revisión',

    'impersonation' => [
        'banner' => 'Estás actuando como @:username',
        'leave' => 'Volver',
        'already_impersonating' => 'Ya estás actuando como otro usuario.',
        'not_impersonating' => 'No hay ninguna impersonación activa.',
    ],

    'reasons' => [
        'spam' => 'Spam',
        'hate_or_harassment' => 'Odio o acoso',
        'sexual_content_minors' => 'Contenido sexual con menores',
        'personal_data' => 'Datos personales (doxxing)',
        'nsfw_unmarked' => 'NSFW sin marcar',
        'other' => 'Otro',
    ],

    'statuses' => [
        'pending' => 'Pendiente',
        'accepted' => 'Aceptado',
        'rejected' => 'Descartado',
    ],

    'errors' => [
        'duplicate_pending' => 'Ya tienes un reporte pendiente para este copy-pasta.',
        'details_length' => 'Describe el motivo con entre 10 y 500 caracteres.',
        'rate_limited' => 'Has enviado demasiados reportes en la última hora. Inténtalo más tarde.',
    ],

    'queue' => [
        'title' => 'Cola de reportes',
        'navigation' => 'Cola de reportes',
        'empty' => 'No hay reportes pendientes.',
        'reports' => 'Reportes',
        'reasons' => 'Motivos',
        'first_reported' => 'Primer reporte',
        'last_reported' => 'Último reporte',
        'hidden_badge' => 'Oculto',
        'visible_badge' => 'Visible',
        'dismiss' => 'Descartar reportes',
        'dismiss_confirm' => 'Los reportes pendientes se marcarán como descartados.',
        'dismissed' => 'Reportes descartados',
    ],

    'log' => [
        'title' => 'Histórico de reportes',
        'navigation' => 'Reportes',
        'model' => 'Reporte',
        'models' => 'Reportes',
        'copypasta' => 'Copy-pasta',
        'reporter' => 'Reportado por',
        'reason' => 'Motivo',
        'status' => 'Estado',
        'created_at' => 'Enviado',
        'resolved_by' => 'Resuelto por',
        'resolved_at' => 'Resuelto',
    ],

    'mail' => [
        'hidden' => [
            'subject' => 'Tu copy-pasta ":title" está oculto',
            'heading' => 'Tu copy-pasta está oculto',
            'body' => 'El copy-pasta ":title" ha sido ocultado por moderación y ya no aparece en el sitio.',
            'reason' => 'Motivo',
            'footer' => 'Puedes revisarlo desde tu espacio de usuario.',
            'button' => 'Ir a mis copy-pastas',
        ],
        'minors' => [
            'subject' => 'Reporte urgente: contenido sexual con menores',
            'heading' => 'Reporte urgente',
            'body' => 'Se ha recibido un reporte de contenido sexual con menores sobre el copy-pasta ":title". Ya está oculto y necesita revisión.',
            'button' => 'Abrir la cola de reportes',
        ],
    ],

];
