<?php

declare(strict_types=1);

return [

    'models' => [
        'copypasta' => 'Copy-pasta',
        'my_copypastas' => 'Mis copy-pastas',
    ],

    'fields' => [
        'title' => 'Título',
        'body' => 'Cuerpo',
        'body_helper' => 'Texto plano: se respetan los saltos de línea y no se admite Markdown ni HTML.',
        'tags' => 'Etiquetas',
        'tags_helper' => 'Entre 1 y :max etiquetas activas.',
        'is_nsfw' => 'NSFW (+18)',
        'status' => 'Estado',
        'score' => 'Score',
        'published_at' => 'Publicado',
        'hidden_notice' => 'Moderación',
    ],

    'status' => [
        'visible' => 'Visible',
        'hidden' => 'Oculto',
    ],

    'show' => [
        'hidden_notice' => 'Oculto por moderación. Motivo: :reason',
    ],

    'errors' => [
        'tags_count' => 'Elige entre 1 y :max etiquetas.',
        'tags_inactive' => 'Alguna etiqueta elegida ya no está disponible.',
    ],

    'duplicate' => [
        'title' => 'Ya existe un copy-pasta igual',
        'body' => 'Tu copy-pasta se ha publicado, pero el cuerpo coincide con uno existente.',
        'view' => 'Ver el existente',
    ],

    'profile' => [
        'username' => 'Nombre de usuario',
        'show_nsfw' => 'Mostrar contenido +18 en el feed',
        'show_nsfw_helper' => 'Si está desactivado, el feed oculta los copy-pastas +18 salvo que confirmes la edad en la web.',
    ],

];
