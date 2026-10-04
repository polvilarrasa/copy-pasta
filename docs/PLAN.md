# Copy-pastas — Plan de producto e implementación

Oct 4, 2026 · @Pol

## Cómo usar este plan en Claude Code

El plan se ejecuta en 12 fases secuenciales dentro de una misma sesión. Cada fase termina con tests en verde y un commit.

1. Exporta este documento a Markdown y guárdalo en la raíz del repo como `docs/PLAN.md`.
2. Primer prompt: *"Lee docs/PLAN.md entero. Crea CLAUDE.md con el resumen del stack, las convenciones de la sección Calidad y el comando de tests. No escribas código todavía."*
3. Para cada fase: *"Ejecuta la Fase N de docs/PLAN.md. Antes de codificar, lista los ficheros que vas a crear o tocar. Al terminar, ejecuta los tests, marca las tareas completadas en el plan y haz commit con el mensaje `feat(faseN): …`."*
4. No pases a la siguiente fase hasta que se cumplan todos sus criterios de aceptación.
5. Si una decisión del plan choca con la realidad (versión de paquete, API de Filament distinta), Claude Code debe proponer el cambio, actualizar el plan y seguir.

Reglas para Claude Code durante toda la sesión:

- Verificar con `composer show` y la documentación oficial las versiones actuales de Laravel, Filament y Livewire antes de usar APIs concretas. Filament cambia bastante entre majors.
- Toda la lógica de negocio va en Actions (`app/Actions/...`) reutilizables desde Livewire y Filament. Nunca duplicarla en un panel.
- Toda autorización pasa por Policies. Los paneles y componentes no comprueban roles a mano.

## Visión y alcance del MVP

Un foro para descubrir, copiar y compartir copy-pastas, con votos, colecciones personales y moderación ligera. La métrica principal es copias más compartidos por visita, porque copiar es la acción que da valor.

**Dentro del MVP**

- Web pública: feed con orden aleatorio, top semanal, top mensual, top histórico y nuevos. Filtros por etiqueta, búsqueda de texto y toggle NSFW. Página de detalle, copiar al portapapeles y compartir enlace.
- Usuarios registrados: publicar y editar sus copy-pastas, upvote y downvote, favoritos, carpetas y reportes.
- Staff: cola de reportes, ocultar y restaurar copy-pastas, CRUD de etiquetas, gestión de usuarios, reset de contraseña, impersonación y baneo.
- Auth solo con email y contraseña, con verificación de email y recuperación de contraseña.
- Interfaz en español, con todos los textos en ficheros de idioma para añadir otros idiomas después.

**Fuera del MVP** (ver Backlog)

- Comentarios, perfiles públicos con seguidores, notificaciones, login social, API pública, app móvil e historial de versiones de un copy-pasta.

**Glosario**

- *Copy-pasta*: texto publicado con título, cuerpo, etiquetas y flag NSFW.
- *Score*: upvotes menos downvotes.
- *Oculto*: retirado por moderación. Solo lo ven su autor (con aviso) y el staff.
- *Carpeta*: colección privada de copy-pastas de un usuario, propios o ajenos.

## Decisiones técnicas

Un único monolito Laravel 13 con tres superficies: la web pública en Livewire y dos paneles Filament 5, `/app` para usuarios y `/admin` para staff. Las tres comparten modelos, Actions y Policies.

| Pieza | Elección | Motivo |
| --- | --- | --- |
| Framework | Laravel 13, PHP 8.3 o superior | Versión mayor actual; requiere PHP 8.3 ([Laravel News](https://laravel-news.com/laravel-13-released)) |
| Paneles | Filament 5 | Usa Livewire 4; sin cambios funcionales respecto a v4 ([Filament](https://filamentphp.com/content/danharrin-filament-v5-blueprint)) |
| Web pública | Blade + Livewire 4 + Alpine + Tailwind | Mismo stack que Filament |
| Auth | Starter kit oficial de Livewire (Fortify) | Registro, login, verificación de email y recuperación de contraseña ya resueltos |
| Base de datos | PostgreSQL 16 o superior | Búsqueda full-text con `tsvector` + índice GIN |
| Colas y caché | Driver `database` en el MVP; Redis después si hace falta | Menos piezas en el arranque |
| Entorno local | Laravel Sail: PHP, Postgres, Mailpit | Reproducible; Mailpit para probar emails |
| Tests y calidad | Pest, Larastan nivel 6, Pint | Estándar del ecosistema |
| Asistente | Laravel Boost (MCP) en Claude Code | Da al agente acceso a la documentación de la versión instalada y a la base de datos |
| Impersonación | Paquete `stechstudio/filament-impersonate` o `lab404/laravel-impersonate` | Verificar compatibilidad con Filament 5 en la Fase 9 |

**Roles sin paquete de permisos.** Con tres roles fijos basta una columna `role` (enum PHP `Role`: user, moderator, admin) más Policies. `spatie/laravel-permission` solo se añade si aparecen permisos configurables.

**Identificadores.** Los copy-pastas usan ULID como clave pública. La URL es `/c/{ulid}/{slug}`; si el slug no coincide, se redirige con 301.

**Contadores denormalizados.** `upvotes_count`, `downvotes_count`, `score`, `favorites_count` y `copies_count` viven en `copypastas`. Se actualizan dentro de la misma transacción que el voto o favorito, para no hacer agregados en el feed.

**Estructura de código**

- `app/Enums`: Role, ReportReason, ReportStatus, FeedSort, ModerationActionType.
- `app/Actions`: PublishCopypasta, UpdateCopypasta, CastVote, ToggleFavorite, AddToFolder, ReportCopypasta, HideCopypasta, RestoreCopypasta, ResolveReport, BanUser.
- `app/Queries/FeedQuery.php`: un único constructor de consultas para todos los listados.
- `app/Livewire`: componentes de la web pública.
- `app/Filament/App` y `app/Filament/Admin`: un directorio por panel.

## Roles y permisos

Cuatro niveles acumulativos: cada rol puede todo lo del anterior. Admin se diferencia de moderador solo en la gestión de usuarios.

| Capacidad | Anónimo | Usuario | Moderador | Admin |
| --- | --- | --- | --- | --- |
| Ver feed (aleatorio, top semanal, mensual, histórico, nuevos) | Sí | Sí | Sí | Sí |
| Filtrar por etiquetas, buscar texto | Sí | Sí | Sí | Sí |
| Ver NSFW | Tras confirmar +18 (cookie) | Según preferencia de perfil | Sí | Sí |
| Copiar al portapapeles y compartir | Sí | Sí | Sí | Sí |
| Publicar, editar y borrar sus copy-pastas | No | Sí (email verificado) | Sí | Sí |
| Upvote y downvote | No | Sí | Sí | Sí |
| Favoritos y carpetas | No | Sí | Sí | Sí |
| Reportar un copy-pasta | No | Sí | Sí | Sí |
| Acceso a `/app` | No | Sí | Sí | Sí |
| Acceso a `/admin` | No | No | Sí | Sí |
| Revisar reportes, ocultar y restaurar copy-pastas | No | No | Sí | Sí |
| Crear, editar y desactivar etiquetas | No | No | Sí | Sí |
| Ver listado de usuarios | No | No | Sí (solo lectura) | Sí |
| Enviar reset de contraseña, banear, cambiar rol | No | No | No | Sí |
| Impersonar usuarios | No | No | No | Sí, excepto staff |

Reglas adicionales:

- Un usuario baneado no puede iniciar sesión; si tiene sesión abierta, se cierra en la siguiente petición. Su contenido sigue visible salvo que se oculte.
- Un usuario sin email verificado puede votar y guardar, pero no publicar ni reportar.
- Nadie puede votar ni reportar sus propios copy-pastas.
- Un admin no puede cambiar su propio rol, banearse ni impersonar a otro miembro del staff.

## Especificación funcional

Cada bloque describe el comportamiento esperado; los criterios de aceptación de las fases lo convierten en tests.

### Feed

- Órdenes (`FeedSort`): `random`, `top_week`, `top_month`, `top_all`, `new`. Por defecto es `random` en la home.
- `top_week` y `top_month` incluyen solo copy-pastas publicados en los últimos 7 o 30 días, ordenados por `score` descendente y, a igualdad, por `published_at` descendente.
- `random` usa una semilla guardada en sesión, `ORDER BY md5(id || seed)`, para que la paginación no repita elementos. El botón "Barajar" regenera la semilla.
- Filtros combinables: etiquetas (todas las seleccionadas deben estar presentes), texto libre sobre título y cuerpo, y NSFW incluido o excluido.
- Todo el estado del feed va en la query string (`?sort=top_week&tags=a,b&q=...`), así un enlace reproduce la vista.
- Paginación infinita o "Cargar más", con 20 elementos por página.
- Nunca aparecen copy-pastas ocultos, borrados ni con etiquetas desactivadas en exclusiva.

### Tarjeta y detalle de copy-pasta

- La tarjeta muestra título, primeras 6 líneas del cuerpo, etiquetas, badge NSFW, score, autor, fecha relativa y botones de copiar, compartir, votar, favorito y "..." (añadir a carpeta, reportar).
- El NSFW se muestra difuminado hasta un clic aunque el usuario lo tenga activado.
- "Copiar" usa la Clipboard API, muestra un toast y envía un evento que incrementa `copies_count`, limitado a 1 por copy-pasta, visitante y hora.
- "Compartir" usa la Web Share API si existe; si no, copia la URL del detalle.
- La página de detalle tiene etiquetas Open Graph y Twitter Card con título y los primeros 200 caracteres. Si es NSFW, el preview es genérico.
- El cuerpo es texto plano: se respetan saltos de línea y espacios, sin Markdown ni HTML, y siempre escapado.

### Publicar y editar

- Campos: título (5–120 caracteres), cuerpo (10–10.000 caracteres), de 1 a 5 etiquetas activas y checkbox NSFW.
- Al publicar se comprueba duplicado exacto por hash del cuerpo normalizado y se avisa con enlace al existente. No se bloquea.
- El autor puede editar siempre; si edita, se muestra "editado" con `edited_at`. Puede borrar (soft delete).
- Si el autor edita un copy-pasta oculto, sigue oculto; la restauración solo la hace un moderador.

### Votos

- Un voto por usuario y copy-pasta, con valor +1 o -1. Repetir el mismo voto lo retira; votar el contrario lo cambia.
- Actualización optimista en la UI; el servidor devuelve el score real.
- Un anónimo que pulsa votar recibe un modal de "Inicia sesión para votar".

### Favoritos y carpetas

- Al registrarse se crea la carpeta por defecto "Favoritos" (`is_default = true`), que no se puede borrar ni renombrar.
- El botón de corazón añade o quita de Favoritos. `favorites_count` cuenta las entradas en carpetas por defecto.
- El usuario crea carpetas con nombre único por usuario (máximo 50 carpetas) y las reordena.
- Un copy-pasta puede estar en varias carpetas. "Añadir a carpeta" abre un selector con checkboxes y opción de crear una carpeta nueva.
- Las carpetas son privadas en el MVP. Un copy-pasta oculto o borrado aparece como "Contenido retirado" dentro de la carpeta.

### Etiquetas

- Campos: nombre, slug, color (de una paleta fija) y activa sí o no.
- Solo el staff las crea. Desactivar una etiqueta la quita de los filtros y del formulario, pero no de los copy-pastas existentes.

### NSFW

- Anónimo: excluido por defecto. Un toggle en el feed pide confirmación "Soy mayor de 18 años" y guarda una cookie durante 1 año.
- Usuario: preferencia `show_nsfw` en su perfil, desactivada por defecto.
- Un moderador puede marcar o desmarcar NSFW en cualquier copy-pasta; queda en el log de moderación.

### Reportes

- Motivos (`ReportReason`): spam, odio o acoso, contenido sexual con menores, datos personales (doxxing), NSFW sin marcar, otro. "Otro" exige texto de 10–500 caracteres.
- Un usuario solo puede tener un reporte pendiente por copy-pasta.
- Si un copy-pasta acumula 5 reportes pendientes de usuarios distintos, se oculta automáticamente con motivo "Pendiente de revisión" y queda arriba en la cola.
- Los reportes por menores ocultan el copy-pasta de inmediato y notifican por email a los admins.

### Moderación

- La cola agrupa por copy-pasta: número de reportes, motivos, primer y último reporte.
- Acciones sobre un copy-pasta: ocultar (motivo obligatorio), restaurar, marcar NSFW y descartar reportes. Ocultar marca los reportes pendientes como aceptados; descartar los marca como rechazados.
- El autor ve en `/app` que su copy-pasta está oculto y el motivo. Se le envía un email.
- Cada acción de staff se registra en `moderation_actions`: quién, qué, sobre qué, motivo y fecha.

### Gestión de usuarios (admin)

- Enviar enlace de restablecimiento de contraseña con el broker estándar de Laravel. El admin nunca ve ni fija contraseñas.
- Banear o desbanear con motivo; cambiar rol; ver sus copy-pastas, reportes enviados e historial de moderación.
- Impersonar: solo admin, no a staff. Una banda fija en la parte superior muestra "Estás actuando como @usuario · Volver". Inicio y fin quedan en el log.
- Durante la impersonación no se pueden cambiar email ni contraseña del usuario.

## Modelo de datos

Nueve tablas de dominio además de las de Laravel (sessions, jobs, cache, password\_reset\_tokens). Todas las FK con `cascadeOnDelete` salvo donde se indica.

| Tabla | Campos | Índices y restricciones |
| --- | --- | --- |
| `users` | id, username (3–30, único, público), email, password, role (`user`/`moderator`/`admin`), show\_nsfw bool, email\_verified\_at, banned\_at, ban\_reason, timestamps | unique(username), unique(email), index(role) |
| `copypastas` | id ULID, user\_id, title, slug, body text, body\_hash (sha256 del cuerpo normalizado), is\_nsfw, upvotes\_count, downvotes\_count, score, favorites\_count, copies\_count, published\_at, edited\_at, hidden\_at, hidden\_by\_id (nullOnDelete), hidden\_reason, search\_vector (columna generada), timestamps, deleted\_at | GIN(search\_vector); index(published\_at); index(score, published\_at); index(body\_hash); index(user\_id) |
| `tags` | id, name, slug, color, is\_active, timestamps | unique(slug) |
| `copypasta_tag` | copypasta\_id, tag\_id | PK compuesta; index(tag\_id, copypasta\_id) |
| `votes` | id, user\_id, copypasta\_id, value smallint (+1/-1), timestamps | unique(user\_id, copypasta\_id); check(value in (-1, 1)) |
| `folders` | id, user\_id, name, is\_default bool, position int, timestamps | unique(user\_id, name); índice parcial único (user\_id) where is\_default |
| `copypasta_folder` | folder\_id, copypasta\_id, created\_at | PK compuesta; index(copypasta\_id) |
| `reports` | id, copypasta\_id, reporter\_id, reason, details, status (`pending`/`accepted`/`rejected`), resolved\_by\_id (nullOnDelete), resolved\_at, resolution\_note, timestamps | índice parcial único (copypasta\_id, reporter\_id) where status = pending; index(status, created\_at) |
| `moderation_actions` | id, actor\_id (nullOnDelete), action (`ModerationActionType`), subject\_type, subject\_id, reason, meta jsonb, created\_at | index(subject\_type, subject\_id); index(actor\_id) |

Notas:

- `search_vector` = `to_tsvector('simple', unaccent(title || ' ' || body))` con peso A para el título y B para el cuerpo. Se usa el diccionario `simple` porque el contenido mezcla idiomas; requiere la extensión `unaccent` y una función inmutable envoltorio.
- `score = upvotes_count - downvotes_count` se mantiene en la Action de voto, no con triggers, para que sea testeable en PHP.
- Scopes en `Copypasta`: `visible()` (publicado, no oculto, no borrado), `nsfw(bool)`, `withAllTags(array)`, `search(string)`, `sort(FeedSort)`.
- `ModerationActionType`: hide, restore, mark\_nsfw, unmark\_nsfw, dismiss\_reports, ban, unban, change\_role, send\_password\_reset, impersonate\_start, impersonate\_end, tag\_created, tag\_updated.

## Rutas y pantallas

17 pantallas repartidas en tres superficies. Las de la web pública son componentes Livewire de página completa; las de los paneles, Resources y Pages de Filament.

| Superficie | Ruta | Pantalla | Acceso |
| --- | --- | --- | --- |
| Pública | `/` | Feed (aleatorio por defecto) con barra de orden, filtros, búsqueda y toggle NSFW | Todos |
| Pública | `/top/semana`, `/top/mes`, `/top` , `/nuevos` | Alias del feed con orden fijado (SEO) | Todos |
| Pública | `/etiqueta/{slug}` | Feed filtrado por etiqueta | Todos |
| Pública | `/c/{ulid}/{slug}` | Detalle de copy-pasta | Todos (ocultos: autor y staff) |
| Pública | `/normas` | Normas de la comunidad | Todos |
| Pública | `/login`, `/registro`, `/recuperar`, `/verificar-email` | Auth del starter kit | Invitados |
| `/app` | Escritorio | Resumen: mis copy-pastas, votos recibidos, avisos de ocultación | Usuario |
| `/app` | Mis copy-pastas | Resource: listar, crear, editar, borrar; estado visible u oculto con motivo | Usuario |
| `/app` | Carpetas | Resource: crear, renombrar, reordenar, borrar; ver contenido de cada carpeta | Usuario |
| `/app` | Ajustes | Perfil (username), preferencia NSFW, cambio de contraseña y email | Usuario |
| `/admin` | Escritorio | Widgets: reportes pendientes, publicados hoy y 7 días, usuarios nuevos | Staff |
| `/admin` | Cola de reportes | Página agrupada por copy-pasta con acciones de moderación | Staff |
| `/admin` | Copy-pastas | Resource con filtros (oculto, NSFW, etiqueta, autor) y acciones ocultar, restaurar y NSFW | Staff |
| `/admin` | Reportes | Resource: histórico de todos los reportes | Staff |
| `/admin` | Etiquetas | Resource CRUD | Staff |
| `/admin` | Usuarios | Resource; acciones reset, ban, rol, impersonar | Moderador lectura, admin escritura |
| `/admin` | Log de moderación | Resource de solo lectura, filtrable por actor y acción | Staff |

Navegación común de la web pública: logo, buscador, botón "Publicar" (lleva a `/app/copypastas/create` o a login), menú de usuario con enlaces a `/app` y, para staff, a `/admin`. Los paneles tienen un enlace "Volver a la web".

## Fases de implementación

12 fases, de la 0 a la 11. Cada una deja la app funcionando, con tests en verde y un commit. Las fases 0 a 4 dan una web navegable con contenido sembrado; la 8 completa el MVP funcional.

### Fase 0 — Esqueleto del proyecto

- [x] Crear el proyecto con el starter kit oficial de Livewire (Laravel 13) y Pest.
- [x] Configurar Sail con PostgreSQL y Mailpit; `.env.example` completo.
- [x] Instalar Larastan (nivel 6), Pint y Laravel Boost; crear `CLAUDE.md` con comandos y convenciones.
- [x] Localización `es` por defecto (`APP_LOCALE=es`), zona horaria `Europe/Madrid`.
- [x] GitHub Actions: Pint en modo test, Larastan y Pest contra Postgres.

Aceptación: `sail artisan test`, `vendor/bin/phpstan` y `vendor/bin/pint --test` pasan en local y en CI.

### Fase 1 — Usuarios, roles y auth

- [ ] Migración de `users` con username, role, show\_nsfw, banned\_at y ban\_reason; enum `Role`.
- [ ] Registro con username; verificación de email obligatoria para publicar y reportar.
- [ ] Middleware `EnsureUserIsNotBanned`: cierra sesión y muestra el motivo.
- [ ] Gates `access-admin` y `manage-users`; helpers `isStaff()` e `isAdmin()` en el modelo.
- [ ] Seeder con un admin, un moderador y 20 usuarios, con credenciales en `.env.example`.

Aceptación: tests de registro, login, verificación, recuperación de contraseña y usuario baneado expulsado.

### Fase 2 — Modelo de dominio

- [ ] Migraciones de copypastas, tags, copypasta\_tag, votes, folders, copypasta\_folder, reports y moderation\_actions según la sección Modelo de datos.
- [ ] Extensión `unaccent`, columna generada `search_vector` e índice GIN.
- [ ] Modelos con relaciones, casts, enums y scopes; `FeedQuery`.
- [ ] Policies: CopypastaPolicy, FolderPolicy, ReportPolicy, TagPolicy, UserPolicy.
- [ ] Factories y seeder: 15 etiquetas, 300 copy-pastas (10 % NSFW, fechas repartidas en 90 días), votos y favoritos aleatorios.
- [ ] Listener que crea la carpeta "Favoritos" al registrarse un usuario.

Aceptación: tests de cada scope de `FeedQuery` (órdenes, ventanas de top, etiquetas, búsqueda con acentos, exclusión de ocultos) y de cada Policy.

### Fase 3 — Panel `/admin` base

- [ ] Panel Filament `admin` con `canAccessPanel` limitado a staff; tema y marca.
- [ ] TagResource completo.
- [ ] CopypastaResource de solo lectura con filtros; acciones ocultar, restaurar y marcar NSFW vía Actions; registro en `moderation_actions`.
- [ ] ModerationActionResource de solo lectura.

Aceptación: un usuario normal recibe 403 en `/admin`; tests Livewire de las acciones ocultar y restaurar y de su entrada en el log.

### Fase 4 — Web pública: feed y detalle

- [ ] Layout público con Tailwind, cabecera, buscador y footer con enlace a `/normas`.
- [ ] Componente `Feed` con orden, filtros de etiqueta, búsqueda, toggle NSFW y estado en la query string.
- [ ] Orden aleatorio con semilla en sesión y botón "Barajar"; "Cargar más".
- [ ] Componente `CopypastaCard`: copiar (Clipboard API + toast + contador limitado), compartir, NSFW difuminado.
- [ ] Página de detalle con redirección 301 de slug, meta Open Graph y Twitter Card.
- [ ] Modal de confirmación +18 para anónimos con cookie de 1 año.
- [ ] Rutas alias `/top/semana`, `/top/mes`, `/top`, `/nuevos`, `/etiqueta/{slug}`.

Aceptación: tests de feed para anónimos (sin NSFW por defecto, con NSFW tras cookie), paginación aleatoria sin repetidos en 3 páginas, 404 para ocultos y 301 de slug.

### Fase 5 — Panel `/app`: publicar y ajustes

- [ ] Panel Filament `app` para usuarios autenticados y verificados.
- [ ] MyCopypastaResource limitado a los del usuario: formulario con título, cuerpo, etiquetas (máximo 5, solo activas) y NSFW; aviso de duplicado por `body_hash`.
- [ ] Estado oculto visible con motivo; el formulario de edición mantiene la ocultación.
- [ ] Página de ajustes: username, preferencia NSFW, email y contraseña.
- [ ] Botón "Publicar" de la web pública enlazado al formulario.

Aceptación: un usuario no puede ver ni editar copy-pastas ajenos en `/app`; tests de validación y de aviso de duplicado; el feed respeta `show_nsfw`.

### Fase 6 — Votos y favoritos

- [ ] Action `CastVote` transaccional con `lockForUpdate` sobre el copy-pasta; alternar, cambiar y retirar voto.
- [ ] Action `ToggleFavorite` sobre la carpeta por defecto; actualiza `favorites_count`.
- [ ] Botones en tarjeta y detalle con actualización optimista; modal de login para anónimos.
- [ ] Rate limit: 60 votos por minuto por usuario.

Aceptación: tests de contadores tras secuencias de votos (+1, +1 de nuevo, -1), concurrencia básica, no votar lo propio, anónimo recibe redirección a login.

### Fase 7 — Carpetas

- [ ] FolderResource en `/app`: crear, renombrar, reordenar (drag and drop), borrar; Favoritos protegida.
- [ ] Vista de carpeta con su lista de copy-pastas, quitar de la carpeta y placeholder "Contenido retirado".
- [ ] Selector "Añadir a carpeta" en la web pública con checkboxes y creación rápida.
- [ ] Límite de 50 carpetas por usuario.

Aceptación: tests de propiedad (no se accede a carpetas ajenas), Favoritos no borrable ni renombrable, un copy-pasta en varias carpetas.

### Fase 8 — Reportes y moderación

- [ ] Action `ReportCopypasta` con validación de motivo, unicidad de pendiente y rate limit de 10 por hora.
- [ ] Modal de reporte en la web pública.
- [ ] Auto-ocultación a los 5 reportes pendientes de usuarios distintos; ocultación inmediata y email a admins en reportes por menores.
- [ ] Página `ModerationQueue` en `/admin` agrupada por copy-pasta con acciones ocultar, restaurar, marcar NSFW y descartar.
- [ ] ReportResource histórico; badge con el número de pendientes en la navegación.
- [ ] Notificación por email al autor cuando se oculta su copy-pasta.

Aceptación: tests del umbral de auto-ocultación, de la resolución en bloque de reportes, de los emails (Mail::fake) y de que no se pueden reportar copy-pastas propios.

### Fase 9 — Gestión de usuarios

- [ ] UserResource: lectura para moderadores, edición para admins; pestañas con copy-pastas, reportes enviados y log.
- [ ] Acciones: enviar enlace de reset, banear y desbanear con motivo, cambiar rol, con las restricciones de la sección Roles.
- [ ] Impersonación con el paquete elegido (verificar compatibilidad con Filament 5); banda superior en web pública y paneles; bloqueo de cambio de email y contraseña mientras dura.
- [ ] Registro de todas las acciones en `moderation_actions`.

Aceptación: tests de que un moderador no puede banear ni impersonar, un admin no puede impersonar staff ni cambiarse el rol, y la impersonación queda registrada al empezar y al terminar.

### Fase 10 — Endurecimiento

- [ ] Rate limits finales: publicar 10 por hora, registro 5 por hora por IP, contador de copias.
- [ ] Revisión de N+1 con `Model::preventLazyLoading()` en local; eager loading en el feed.
- [ ] Caché de la lista de etiquetas activas y de los conteos del escritorio admin.
- [ ] Cabeceras de seguridad (CSP compatible con Livewire), cookies seguras, HTTPS forzado en producción.
- [ ] `sitemap.xml` con copy-pastas no NSFW, `robots.txt`, páginas 404 y 403 personalizadas.
- [ ] Páginas de privacidad y cookies; textos de las normas.

Aceptación: el feed de la home ejecuta 5 consultas como máximo (test con contador de queries); auditoría de Lighthouse de 90 o más en rendimiento y accesibilidad.

### Fase 11 — Despliegue

- [ ] Dockerfile de producción (PHP-FPM + Nginx o FrankenPHP) y `docker-compose` para staging.
- [ ] Worker de colas y scheduler (`schedule:run`) configurados.
- [ ] Mailer transaccional real (SMTP o API) y dominio con SPF/DKIM.
- [ ] Backups diarios de Postgres y monitorización de errores.
- [ ] Comando `app:create-admin` para crear el primer admin en producción.

Aceptación: despliegue en staging desde CI con migraciones automáticas y checklist de humo (registro, publicar, votar, reportar, ocultar) superado a mano.

## Calidad y convenciones

Estas reglas van íntegras a `CLAUDE.md` en la Fase 0 y aplican a todas las fases.

**Tests**

- Pest con `RefreshDatabase` contra PostgreSQL, nunca SQLite: la búsqueda full-text y los índices parciales dependen de Postgres.
- Cada Action tiene tests unitarios; cada Policy, un test por rol; cada pantalla Filament, al menos un test Livewire de acceso y de su acción principal.
- Factories con estados legibles: `->nsfw()`, `->hidden()`, `->publishedDaysAgo(10)`, `->moderator()`, `->banned()`.

**Código**

- `declare(strict_types=1)`, tipos de retorno en todo, enums en lugar de strings mágicos.
- Controladores y componentes finos: validan, autorizan y llaman a una Action.
- Ninguna cadena visible al usuario en el código: todo en `lang/es/*.php`.
- Migraciones nunca editadas tras el commit de su fase; los cambios van en migraciones nuevas.
- Commits convencionales por fase: `feat(fase4): feed público con filtros`.

**Seguridad**

- El cuerpo de un copy-pasta se imprime siempre con `{{ }}` y `whitespace-pre-wrap`; nunca `{!! !!}`.
- Toda acción mutadora autorizada con Policy y limitada con `RateLimiter`.
- Impersonación, cambios de rol y baneos solo vía Actions que escriben en `moderation_actions`.

**Rendimiento**

- `Model::preventLazyLoading()` y `preventSilentlyDiscardingAttributes()` activos fuera de producción.
- El feed nunca agrega votos al vuelo; usa los contadores denormalizados.
- Índices revisados con `EXPLAIN ANALYZE` sobre el seeder de 300 copy-pastas antes de cerrar la Fase 4.

## Backlog post-MVP

Ideas fuera del alcance, ordenadas por valor estimado. Ninguna requiere rehacer el modelo de datos.

| Idea | Motivo | Impacto en el modelo |
| --- | --- | --- |
| Perfil público `/u/{username}` con sus copy-pastas | Atribución y descubrimiento | Ninguno |
| Carpetas públicas compartibles por enlace | Colecciones temáticas virales | Campo `is_public` y `share_token` en folders |
| Apelación de ocultación por el autor | Reduce fricción con moderadores | Tabla `appeals` |
| Copy-pastas "plantilla" con variables (`{nombre}`) que se rellenan antes de copiar | Uso diferencial frente a un pastebin | Ninguno: se parsea el cuerpo |
| Login social (Google, GitHub, Discord) | Menos fricción en el registro | Tabla `social_accounts` (Socialite) |
| Comentarios | Comunidad | Tabla `comments` + reportes polimórficos |
| Notificaciones in-app (votos, ocultaciones) | Retención | Tabla `notifications` de Laravel |
| API pública de lectura | Bots de Discord o Telegram | Ninguno |
| Multidioma de la interfaz (catalán, inglés) | Alcance | Ninguno: ya está en ficheros `lang` |
