<?php

declare(strict_types=1);

return [

    'layout' => [
        'search_placeholder' => 'Buscar copy-pastas',
        'publish' => 'Publicar',
        'admin' => 'Administración',
        'login' => 'Entrar',
        'register' => 'Registrarse',
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
        'for_you' => 'Para ti',
        'for_you_refresh' => 'Actualizar',
        'for_you_refreshed' => 'Lista actualizada.',
        'for_you_empty' => 'Todavía no hay nada que enseñarte. Vuelve en un rato.',
    ],

    'favorites' => [
        'title' => 'Tus etiquetas',
        'edit' => 'Editar favoritas',
        'empty' => 'Aún no has elegido favoritas.',
        'choose' => 'Elegir favoritas',
    ],

    'welcome' => [
        'title' => '¿Qué te hace gracia?',
        'intro' => 'Elige tus etiquetas favoritas y montamos tu pestaña :for_you. Puedes cambiarlas cuando quieras.',
        'edit_title' => 'Tus etiquetas favoritas',
        'edit_intro' => 'Cambia las etiquetas con las que montamos tu pestaña Para ti.',
        'chosen' => '{0} Ninguna elegida|{1} 1 elegida|[2,*] :count elegidas',
        'minimum' => 'Mínimo :min',
        'tags_label' => 'Etiquetas disponibles',
        'choose_more' => '{1} Elige 1 más|[2,*] Elige :count más',
        'see_feed' => 'Ver mi feed',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'skip' => 'Saltar por ahora',
        'hint' => 'Podrás editar tus favoritas desde el feed.',
        'min_tags' => 'Elige al menos :min etiquetas.',
    ],

    'dismiss' => [
        'button' => 'No me interesa',
        'done' => 'Listo, no volverás a verlo.',
        'undo' => 'Deshacer',
        'undone' => 'Copy-pasta recuperado.',
    ],

    'featured' => [
        'date' => ':date',
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

    'mine' => [
        'title' => 'Mis copy-pastas',
        'empty_title' => 'Aún no has publicado nada',
        'empty_body' => 'Publica tu primera copy-pasta y aparecerá aquí.',
        'empty_cta' => 'Publicar una copy-pasta',
        'copies' => 'Copias',
        'saved' => 'Guardados',
        'view' => 'Ver',
        'edit' => 'Editar',
        'delete' => 'Borrar',
    ],

    'publish' => [
        'title_create' => 'Publicar copy-pasta',
        'title_edit' => 'Editar copy-pasta',
        'cancel' => 'Cancelar',
        'submit_create' => 'Publicar',
        'submit_edit' => 'Guardar cambios',
        'preview_title' => 'Vista previa',
        'preview_live' => 'en vivo',
        'preview_help' => 'Así se verá en el feed: título, primeras 6 líneas y etiquetas. El texto completo se ve en el detalle.',
        'unverified_title' => 'Verifica tu email para publicar',
        'delete' => 'Eliminar copy-pasta',
        'delete_confirm' => 'Se borrará el copy-pasta. Esta acción no se puede deshacer.',
    ],

    'folder' => [
        'index_title' => 'Carpetas',
        'index_empty_title' => 'Aún no tienes carpetas',
        'index_empty_body' => 'Crea una carpeta para guardar tus copy-pastas favoritos.',
        'description_label' => 'Descripción',
        'description_hint' => 'Hasta 280 caracteres. La ve quien abra la carpeta si es pública.',
        'edit' => 'Editar',
        'back' => 'Mis carpetas',
        'search_label' => 'Buscar en la carpeta',
        'search_placeholder' => 'Buscar en esta carpeta',
        'empty_body' => 'Usa el botón Guardar en cualquier copy-pasta y aparecerá aquí.',
        'empty_cta' => 'Explorar el feed',
        'count' => '{0} Sin copy-pastas|{1} 1 copy-pasta|[2,*] :count copy-pastas',
        'move_up' => 'Subir',
        'move_down' => 'Bajar',
    ],

    'share_image' => [
        'toggle' => 'Compartir como imagen',
        'hint' => 'Para stories, chats y hilos donde el texto no cabe.',
        'canvas_label' => 'Vista previa de la imagen',
        'style' => 'Estilo',
        'styles' => [
            'midnight' => 'Medianoche',
            'light' => 'Claro',
            'lime' => 'Lima',
        ],
        'download' => 'Descargar imagen',
        'share' => 'Compartir imagen',
        'nsfw_title' => 'Este copy-pasta es para mayores de 18 años',
        'nsfw_body' => 'La imagen incluirá su título y sus primeras líneas, sin difuminar. Genérala solo si quieres compartirlo así.',
        'nsfw_confirm' => 'Generar la imagen',
        'shared' => 'Imagen compartida.',
        'downloaded' => 'Imagen descargada.',
        'failed' => 'No se pudo generar la imagen.',
        'deleted_author' => 'Usuario eliminado',
        'brand' => 'copy-pastas',
    ],

    'public_folder' => [
        'by' => 'Carpeta de',
        'default_description' => 'Una carpeta de copy-pastas de :name.',
        'empty_title' => 'Esta carpeta no tiene nada que mostrar',
        'empty_body' => 'Todavía no hay copy-pastas visibles en ella.',
        'profile_title' => 'Carpetas públicas',
        'profile_count' => '{0} Sin copy-pastas visibles|{1} 1 copy-pasta|[2,*] :count copy-pastas',
        'public_badge' => 'Pública',
        'public_label' => 'Carpeta pública',
        'public_hint' => 'Cualquiera con el enlace puede verla: nombre, descripción y los copy-pastas visibles.',
        'public_url' => 'Enlace público',
        'make_public' => 'Hacer pública',
        'make_private' => 'Hacer privada',
        'now_public' => 'La carpeta es pública.',
        'now_private' => 'La carpeta vuelve a ser privada y su enlace deja de funcionar.',
        'share' => 'Compartir carpeta',
        'share_title' => 'Para compartirla, hay que hacerla pública',
        'share_body' => 'Cualquiera con el enlace podrá ver el nombre, la descripción y los copy-pastas visibles de «:name». Puedes volver a hacerla privada cuando quieras.',
        'share_confirm' => 'Hacerla pública y compartir',
        'link_copied' => 'Enlace de la carpeta copiado.',
    ],

    'profile' => [
        'member_since' => 'Miembro desde :month de :year',
        'counter_published' => 'Publicadas',
        'counter_copies' => 'Copias',
        'counter_upvotes' => 'Upvotes',
        'counters_label' => 'Contadores del perfil',
        'tab_top' => 'Top',
        'tab_new' => 'Nuevos',
        'tabs_label' => 'Ordenar copy-pastas del perfil',
        'empty_title' => 'Todavía no ha publicado nada',
        'empty_body' => 'Cuando publique un copy-pasta visible, aparecerá aquí.',
    ],

    'stats' => [
        'title' => 'Mis estadísticas',
        'private_badge' => 'Privado',
        'range' => 'Últimos 30 días · :from – :to',
        'updated' => 'Actualizado hace :time',
        'kpi' => [
            'copies' => 'Copias',
            'votes' => 'Votos netos',
            'saves' => 'Guardados',
            'shares' => 'Compartidos',
        ],
        'vs_previous' => 'vs. 30 días antes',
        'no_previous_data' => '—',
        'chart_title' => ':metric por día',
        'chart_description' => ':metric registradas cada día entre el :from y el :to.',
        'chart_day' => 'Día',
        'readout_total' => 'Total: :value :unit',
        'readout_day' => ':day · :value :unit',
        'visits' => 'Visitas',
        'referred_visits' => 'Visitas traídas por tus enlaces',
        'referred_visits_hint' => 'En los últimos 30 días. Cuenta una por visitante y día; no cuentan las tuyas.',
        'top_tags_title' => 'Etiquetas con más copias',
        'top_tags_empty' => 'Todavía no hay copias etiquetadas.',
        'best_title' => 'Mejor copy-pasta',
        'growing_title' => 'El que más ha crecido esta semana',
        'no_data' => 'Todavía no hay datos suficientes.',
        'table_title' => 'Tus mejores copy-pastas',
        'table_title_column' => 'Título',
        'table_copies_column' => 'Copias',
        'table_votes_column' => 'Votos',
        'table_saves_column' => 'Guardados',
        'table_empty' => 'Todavía no has publicado nada.',
        'reliability_title' => 'Fiabilidad como reportador',
        'reliability_trusted' => 'Ya eres un usuario de confianza.',
        'reliability_summary' => ':accepted de :resolved reportes resueltos aceptados (:percentage %).',
        'reliability_none' => 'Todavía no has resuelto ningún reporte.',
        'reliability_progress' => 'Para ser candidato a usuario de confianza necesitas al menos 10 reportes resueltos y un 80 % aceptados. El rol lo asigna el staff: cumplir los requisitos no lo garantiza.',
        'reliability_resolved_progress' => ':resolved de 10 reportes resueltos',
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
        'tagline' => 'Descubre, copia y comparte los copy-pastas de internet.',
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
            ['heading' => 'Datos que tratamos', 'body' => 'Email, nombre de usuario, contraseña cifrada, los copy-pastas y votos que publicas, tus carpetas y tus reportes, además de los registros de moderación y los eventos de uso descritos más abajo.'],
            ['heading' => 'Finalidad y base', 'body' => 'Prestar el servicio de la cuenta, mostrar tu contenido, moderar la comunidad y atender reportes. La base es la ejecución del servicio y, para los emails, la verificación de tu cuenta. Las estadísticas de uso se basan en el interés legítimo de mejorar el servicio.'],
            ['heading' => 'Registro de uso', 'body' => 'Registramos qué acciones haces en el sitio: ver un copy-pasta, copiarlo, compartirlo, votarlo, guardarlo en una carpeta, reportarlo y buscar, junto con la lista de la que venías. Sirve para ordenar el feed, calcular estadísticas y, más adelante, mostrar logros. Si no tienes cuenta, no guardamos tu IP ni usamos cookies: solo un identificador que cambia cada día y que no permite seguirte de un día a otro. Si buscas con una cuenta, guardamos el texto buscado, truncado a 100 caracteres.'],
            ['heading' => 'Conservación', 'body' => 'Mientras mantengas la cuenta. Los eventos de uso se borran a los 13 meses. Al borrar tu cuenta, tus eventos se conservan sin vincularlos a ti. Las estadísticas por copy-pasta y día no contienen datos personales. Los registros de moderación se conservan para garantizar la trazabilidad de las decisiones.'],
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
