<?php

declare(strict_types=1);

return [

    'models' => [
        'copypasta' => 'Copy-pasta',
        'my_copypastas' => 'Mis copy-pastas',
        'folder' => 'Carpeta',
        'folders' => 'Carpetas',
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
        'folder_name' => 'Nombre',
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
        'publish_rate_limited' => 'Has publicado demasiados copy-pastas en la última hora. Inténtalo más tarde.',
        'folder_rate_limited' => 'Has hecho demasiados cambios en tus carpetas en el último minuto. Espera un momento.',
    ],

    'duplicate' => [
        'title' => 'Ya existe un copy-pasta igual',
        'body' => 'Tu copy-pasta se ha publicado, pero el cuerpo coincide con uno existente.',
        'view' => 'Ver el existente',
    ],

    'folders' => [
        'contents' => 'Copy-pastas de la carpeta',
        'empty' => 'Esta carpeta está vacía.',
        'create' => 'Nueva carpeta',
        'rename' => 'Renombrar',
        'delete' => 'Borrar carpeta',
        'delete_confirm' => 'Se borrará la carpeta, no los copy-pastas que contiene.',
        'remove' => 'Quitar de la carpeta',
        'remove_confirm' => 'El copy-pasta se quitará de esta carpeta. No se borra.',
        'removed_content' => 'Contenido retirado',
        'default_badge' => 'Predeterminada',
        'count' => 'Copy-pastas',
        'status' => [
            'visible' => 'Visible',
            'hidden' => 'Oculto',
            'deleted' => 'Borrado',
        ],
        'errors' => [
            'limit' => 'Puedes tener hasta :max carpetas.',
            'name_taken' => 'Ya tienes una carpeta con ese nombre.',
        ],
    ],

    'profile' => [
        'username' => 'Nombre de usuario',
        'show_nsfw' => 'Mostrar contenido +18 en el feed',
        'nsfw_age_confirm' => 'Confirmo que soy mayor de 18 años y quiero ver contenido para adultos.',
        'show_nsfw_helper' => 'Si está desactivado, el feed oculta los copy-pastas +18 salvo que confirmes la edad en la web.',
    ],

];
