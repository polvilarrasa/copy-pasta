<?php

declare(strict_types=1);

return [

    'layout' => [
        'search_placeholder' => 'Buscar copy-pastas',
        'publish' => 'Publicar',
        'dashboard' => 'Mi espacio',
        'admin' => 'Administración',
        'login' => 'Entrar',
        'register' => 'Registrarse',
        'logout' => 'Salir',
        'rules' => 'Normas',
        'privacy' => 'Privacidad',
        'cookies' => 'Cookies',
    ],

    'legal' => [
        'draft_notice' => 'Texto provisional pendiente de revisión legal antes del lanzamiento.',
    ],

    'errors' => [
        'back_home' => 'Volver al inicio',
        '403' => [
            'title' => 'No tienes acceso a esta página',
            'body' => 'Puede que necesites iniciar sesión o que tu cuenta no tenga permiso para verla.',
        ],
        '404' => [
            'title' => 'Página no encontrada',
            'body' => 'La página que buscas no existe o se ha movido.',
        ],
    ],

    'sorts' => [
        'random' => 'Aleatorio',
        'top_week' => 'Top semanal',
        'top_month' => 'Top mensual',
        'top_all' => 'Top histórico',
        'new' => 'Nuevos',
    ],

    'feed' => [
        'title' => 'Copy-pastas',
        'sort_label' => 'Ordenar copy-pastas',
        'search_placeholder' => 'Buscar en títulos y cuerpos',
        'shuffle' => 'Barajar',
        'load_more' => 'Cargar más',
        'empty' => 'No hay copy-pastas con estos filtros.',
        'nsfw_show' => 'Mostrar contenido +18',
        'nsfw_hide' => 'Ocultar contenido +18',
    ],

    'nsfw' => [
        'title' => 'Contenido +18',
        'body' => 'Este contenido es solo para mayores de 18 años. Al confirmar, lo recordaremos en este navegador durante un año.',
        'confirm' => 'Soy mayor de 18 años',
        'cancel' => 'Cancelar',
    ],

    'card' => [
        'nsfw_badge' => '+18',
        'reveal' => 'Mostrar contenido',
        'by' => 'por :username',
        'deleted_user' => 'usuario eliminado',
        'score' => ':score puntos',
        'copy' => 'Copiar',
        'share' => 'Compartir',
    ],

    'copy' => [
        'copied' => 'Copiado al portapapeles',
        'link_copied' => 'Enlace copiado',
        'action_failed' => 'No se pudo completar la acción. Inténtalo de nuevo.',
    ],

    'vote' => [
        'up' => 'Votar a favor',
        'down' => 'Votar en contra',
    ],

    'favorite' => [
        'add' => 'Guardar en favoritos',
        'remove' => 'Quitar de favoritos',
    ],

    'folders' => [
        'button' => 'Añadir a carpeta',
        'title' => 'Añadir a carpeta',
        'loading' => 'Cargando carpetas…',
        'empty' => 'Todavía no tienes carpetas.',
        'new_placeholder' => 'Nombre de una carpeta nueva',
        'create' => 'Crear',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'saved' => 'Carpetas actualizadas',
    ],

    'login_modal' => [
        'title' => 'Inicia sesión',
        'vote' => 'Inicia sesión para votar.',
        'favorite' => 'Inicia sesión para guardar copy-pastas en favoritos.',
        'folder' => 'Inicia sesión para guardar copy-pastas en tus carpetas.',
        'confirm' => 'Entrar',
        'cancel' => 'Cancelar',
    ],

    'report' => [
        'button' => 'Reportar',
        'title' => 'Reportar copy-pasta',
        'reason' => 'Motivo',
        'details' => 'Explica el motivo',
        'details_helper' => 'Obligatorio solo si eliges «Otro». Entre 10 y 500 caracteres.',
        'submit' => 'Enviar reporte',
        'cancel' => 'Cancelar',
        'sent' => 'Gracias. Revisaremos el reporte.',
        'failed' => 'No se pudo enviar el reporte. Inténtalo de nuevo.',
        'rate_limited' => 'Has enviado demasiados reportes en la última hora. Inténtalo más tarde.',
    ],

    'notice' => [
        'title' => 'Avisar de un contenido',
        'link' => 'Avisar de un contenido ilegal',
        'description' => 'Cuéntanos qué pasa con «:title». Revisaremos el aviso y te responderemos al email que indiques.',
        'email' => 'Tu email de contacto',
        'reason' => 'Motivo',
        'details' => 'Detalles (opcional)',
        'submit' => 'Enviar aviso',
        'sent' => 'Aviso recibido. Lo revisaremos cuanto antes.',
    ],

    'show' => [
        'edited' => 'Editado :date',
        'hidden_notice' => 'Este copy-pasta está oculto por moderación. Motivo: :reason',
        'unpublished_notice' => 'Este copy-pasta todavía no está publicado.',
        'back' => 'Volver al listado',
    ],

    'og' => [
        'nsfw_description' => 'Contenido para mayores de 18 años.',
    ],

    'rules' => [
        'title' => 'Normas de la comunidad',
        'items' => [
            'Publica solo texto que puedas compartir: nada de datos personales de terceros ni información privada.',
            'Prohibido el acoso, los insultos dirigidos a personas y el discurso de odio.',
            'El contenido sexual con menores se elimina de inmediato y se notifica.',
            'Marca como +18 los copy-pastas con contenido sexual explícito o gráfico.',
            'Los copy-pastas que repitan el mismo texto pueden ser retirados.',
            'Los moderadores pueden ocultar cualquier contenido que incumpla estas normas, con motivo, y el autor recibe un aviso.',
            'Para apelar una ocultación, escribe al equipo desde la dirección de contacto del sitio.',
        ],
    ],

    'privacy' => [
        'title' => 'Política de privacidad',
        'sections' => [
            ['heading' => 'Responsable', 'body' => 'El responsable del tratamiento es el titular del sitio, con los datos de contacto que se publicarán antes del lanzamiento.'],
            ['heading' => 'Datos que tratamos', 'body' => 'Email, nombre de usuario, contraseña cifrada, los copy-pastas y votos que publicas, tus carpetas y tus reportes, además de los registros de moderación.'],
            ['heading' => 'Finalidad y base', 'body' => 'Prestar el servicio de la cuenta, mostrar tu contenido, moderar la comunidad y atender reportes. La base es la ejecución del servicio y, para los emails, la verificación de tu cuenta.'],
            ['heading' => 'Conservación', 'body' => 'Mientras mantengas la cuenta. Los registros de moderación se conservan para garantizar la trazabilidad de las decisiones.'],
            ['heading' => 'Tus derechos', 'body' => 'Puedes acceder, rectificar y suprimir tus datos, y solicitar su portabilidad u oponerte a su tratamiento escribiendo al contacto del sitio.'],
        ],
    ],

    'cookies' => [
        'title' => 'Política de cookies',
        'sections' => [
            ['heading' => 'Cookies técnicas', 'body' => 'La sesión y la protección contra falsificación de formularios necesitan cookies. No se pueden desactivar si quieres usar tu cuenta.'],
            ['heading' => 'Confirmación +18', 'body' => 'Si confirmas que eres mayor de edad, se guarda una cookie durante un año para no volver a preguntarte en este navegador.'],
            ['heading' => 'Sin cookies de terceros', 'body' => 'No usamos cookies de publicidad ni de analítica de terceros.'],
        ],
    ],

];
