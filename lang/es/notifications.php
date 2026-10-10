<?php

declare(strict_types=1);

return [
    'removed' => 'Contenido retirado',

    'milestone' => [
        'text' => '«:title» ha llegado a :milestones.',
        'copies' => ':count copias',
        'upvotes' => ':count upvotes',
        'and' => ' y ',
    ],
    'copypasta_hidden' => 'Tu copy-pasta «:title» se ha ocultado por moderación.',
    'copypasta_restored' => 'Tu copy-pasta «:title» vuelve a estar visible.',
    'report_accepted' => 'Hemos aceptado tu reporte. Gracias por ayudar a mantener la comunidad.',
    'trusted_promotion' => 'Ya eres usuario de confianza: tus reportes pesan más.',
    'achievement_unlocked' => 'Has conseguido el logro «:name».',
    'folder_made_private' => 'El equipo de moderación ha hecho privada tu carpeta «:name». Motivo: :reason',

    'types' => [
        'milestone' => [
            'label' => 'Hitos de tus copy-pastas',
            'description' => 'Cuando uno llega a 10, 100 o 1.000 copias o upvotes.',
        ],
        'report_accepted' => [
            'label' => 'Reportes aceptados',
            'description' => 'Cuando el equipo da la razón a un reporte tuyo.',
        ],
        'trusted_promotion' => [
            'label' => 'Ascenso a usuario de confianza',
            'description' => 'Cuando pasas a ser usuario de confianza.',
        ],
        'achievement_unlocked' => [
            'label' => 'Logros desbloqueados',
            'description' => 'Cuando consigues un logro nuevo.',
        ],
        'copypasta_hidden' => [
            'label' => 'Copy-pasta oculto',
            'description' => 'Cuando moderación oculta uno de tus copy-pastas.',
        ],
        'folder_made_private' => [
            'label' => 'Carpeta hecha privada',
            'description' => 'Cuando moderación hace privada una de tus carpetas públicas.',
        ],
        'copypasta_restored' => [
            'label' => 'Copy-pasta restaurado',
            'description' => 'Cuando moderación vuelve a mostrar uno de tus copy-pastas.',
        ],
    ],

    'bell' => [
        'label' => 'Notificaciones',
        'label_unread' => '{1} Notificaciones, :count sin leer|[2,*] Notificaciones, :count sin leer',
        'more_than_99' => '99+',
        'mark_all' => 'Marcar leídas',
        'tabs' => 'Filtrar notificaciones',
        'all' => 'Todas',
        'unread' => 'Sin leer',
        'view_all' => 'Ver todas',
        'unread_dot' => 'Sin leer',
        'empty' => 'No tienes notificaciones.',
        'empty_unread' => 'No tienes notificaciones sin leer.',
        'loading' => 'Cargando…',
    ],

    'page' => [
        'title' => 'Notificaciones',
        'description' => 'Te avisamos de los hitos de tus copy-pastas y de las decisiones de moderación.',
        'mark_all' => 'Marcar todas como leídas',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
        'page_of' => 'Página :page de :last',
        'preferences' => 'Preferencias',
    ],

    'errors' => [
        'rate_limited' => 'Demasiadas acciones seguidas. Espera un momento.',
    ],
];
