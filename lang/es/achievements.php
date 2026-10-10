<?php

declare(strict_types=1);

/*
 | Each description says exactly what its metric measures (see App\Enums\AchievementMetric). Adding an achievement
 | means a case in App\Enums\Achievement plus its name and description under `items` and, if it gives a title, the
 | title under `titles`.
 */
return [
    'items' => [
        'first_paste' => [
            'name' => 'Primera pegada',
            'description' => 'Tener 1 copy-pasta publicado y visible.',
        ],
        'habitual_paster' => [
            'name' => 'Pegador habitual',
            'description' => 'Tener 10 copy-pastas publicados y visibles.',
        ],
        'paste_factory' => [
            'name' => 'Fábrica de pastas',
            'description' => 'Tener 100 copy-pastas publicados y visibles.',
        ],
        'first_applause' => [
            'name' => 'Primer aplauso',
            'description' => 'Tener 1 upvote recibido de una cuenta verificada con 72 horas o más. Los upvotes retirados no cuentan.',
        ],
        'well_received' => [
            'name' => 'Bien recibido',
            'description' => 'Tener 10 upvotes recibidos de cuentas verificadas con 72 horas o más. Los retirados no cuentan.',
        ],
        'public_favorite' => [
            'name' => 'Favorito del público',
            'description' => 'Tener 100 upvotes recibidos de cuentas verificadas con 72 horas o más. Los retirados no cuentan.',
        ],
        'legend' => [
            'name' => 'Leyenda',
            'description' => 'Tener 1.000 upvotes recibidos de cuentas verificadas con 72 horas o más. Los retirados no cuentan.',
        ],
        'copied' => [
            'name' => 'Copiado',
            'description' => 'Sumar 10 copias de tus copy-pastas hechas por otras personas.',
        ],
        'viral' => [
            'name' => 'Viral',
            'description' => 'Sumar 100 copias de tus copy-pastas hechas por otras personas.',
        ],
        'internet_heritage' => [
            'name' => 'Patrimonio de internet',
            'description' => 'Sumar 1.000 copias de tus copy-pastas hechas por otras personas.',
        ],
        'trending' => [
            'name' => 'En tendencia',
            'description' => 'Haber tenido un copy-pasta tuyo en el top 10 semanal.',
        ],
        'first_folder' => [
            'name' => 'Primera carpeta',
            'description' => 'Haber creado 1 carpeta propia. Favoritos no cuenta.',
        ],
        'collector' => [
            'name' => 'Coleccionista',
            'description' => 'Tener 50 copy-pastas guardados en Favoritos.',
        ],
        'vigilante' => [
            'name' => 'Vigilante',
            'description' => 'Tener 1 reporte aceptado por moderación.',
        ],
        'guardian' => [
            'name' => 'Guardián',
            'description' => 'Tener 10 reportes aceptados por moderación.',
        ],
        'sentinel' => [
            'name' => 'Centinela',
            'description' => 'Tener 50 reportes aceptados por moderación.',
        ],
        'critic' => [
            'name' => 'Crítico',
            'description' => 'Tener 100 votos emitidos activos. Los votos retirados no cuentan.',
        ],
        'messenger' => [
            'name' => 'Mensajero',
            'description' => 'Conseguir 1 visita por tus enlaces compartidos. Cuenta una por visitante y día, y nunca las tuyas ni las de bots.',
        ],
        'loudspeaker' => [
            'name' => 'Altavoz',
            'description' => 'Conseguir 100 visitas por tus enlaces compartidos. Cuenta una por visitante y día, y nunca las tuyas ni las de bots.',
        ],
        'megaphone' => [
            'name' => 'Megáfono',
            'description' => 'Conseguir 1.000 visitas por tus enlaces compartidos. Cuenta una por visitante y día, y nunca las tuyas ni las de bots.',
        ],
        'veteran' => [
            'name' => 'Veterano',
            'description' => 'Tener la cuenta desde hace 365 días.',
        ],
        'night_owl' => [
            'name' => 'Noctámbulo',
            'description' => 'Haber publicado un copy-pasta entre las 3:00 y las 3:59, hora de Madrid.',
        ],
        'dynamite' => [
            'name' => 'Dinamita',
            'description' => 'Conseguir que un copy-pasta tuyo reciba 100 copias de otras personas en 24 horas.',
        ],
    ],

    'titles' => [
        'first_paste' => 'Recién pegado',
        'habitual_paster' => 'Pegador habitual',
        'paste_factory' => 'Maestro del Ctrl+V',
        'well_received' => 'Aplaudido',
        'public_favorite' => 'Favorito del público',
        'legend' => 'Leyenda del foro',
        'viral' => 'Viral',
        'internet_heritage' => 'Patrimonio de internet',
        'trending' => 'En tendencia',
        'collector' => 'Coleccionista',
        'guardian' => 'Guardián',
        'sentinel' => 'Centinela',
        'loudspeaker' => 'Altavoz',
        'megaphone' => 'Megáfono',
        'veteran' => 'Veterano',
        'night_owl' => 'Noctámbulo',
        'dynamite' => 'Dinamita',
    ],

    'rarity' => [
        'less_than_one' => '<1 %',
        'percent' => ':value %',
        'label' => 'Lo tiene el :rarity de las cuentas',
    ],

    'profile' => [
        'title' => 'Logros',
        'earned' => 'Conseguidos',
        'pending' => 'Pendientes',
        'secrets' => 'Secretos',
        'active_title' => 'Título activo',
        'unlocked_on' => 'Conseguido el :date',
        'progress' => ':current de :max',
        'progress_label' => 'Progreso de «:name»',
        'empty' => 'Todavía no ha conseguido ningún logro.',
        'empty_own' => 'Todavía no has conseguido ningún logro.',
        'secret_name' => '???',
        'secret_description' => 'Logro secreto. Se desbloquea solo.',
        'secrets_earned' => '{0} Ningún logro secreto conseguido|{1} 1 logro secreto conseguido|[2,*] :count logros secretos conseguidos',
    ],

    'settings' => [
        'title' => 'Título',
        'description' => 'Elige el título que se muestra junto a tu nombre. Solo puedes usar los de logros que ya has conseguido.',
        'none' => 'Ninguno',
        'none_description' => 'No mostrar ningún título.',
        'empty' => 'Todavía no has conseguido ningún logro con título.',
        'saved' => 'Título guardado.',
        'invalid' => 'Ese título no está disponible para tu cuenta.',
        'legend' => 'Título junto a tu nombre',
    ],

    'admin' => [
        'tab' => 'Logros',
        'empty' => 'Este usuario no ha conseguido ningún logro.',
        'achievement' => 'Logro',
        'unlocked_at' => 'Conseguido',
        'state' => 'Estado',
        'active' => 'Activo',
        'revoked' => 'Revocado',
        'revoked_at' => 'Revocado el',
        'revoked_by' => 'Revocado por',
        'revoke' => 'Revocar',
        'revoke_heading' => 'Revocar logro',
        'revoke_description' => 'El logro dejará de mostrarse y no volverá a concederse automáticamente. Si era su título activo, se quita.',
        'revoke_submit' => 'Revocar',
        'restore' => 'Restaurar',
        'restore_heading' => 'Restaurar logro',
        'restore_description' => 'El usuario recupera el logro. No se le notifica y su título no se vuelve a fijar solo.',
        'restore_submit' => 'Restaurar',
        'revoked_notice' => 'Logro revocado.',
        'restored_notice' => 'Logro restaurado.',
    ],
];
