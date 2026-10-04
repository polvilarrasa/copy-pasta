# Copy-pastas — Plan de producto e implementación

Oct 4, 2026 · @Pol

## Cómo usar este plan en Claude Code

El plan se ejecuta en 13 fases secuenciales (0 a 12) dentro de una misma sesión. Cada fase termina con tests en verde y un commit.

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
- Verificar a mano el email de un miembro, quitar la verificación y reenviar el correo de verificación (máximo tres veces por hora y miembro).
- Crear usuarios desde el admin con rol elegido y contraseña temporal generada. El usuario debe cambiarla antes de usar cualquier panel.
- Borrado y restauración lógicos. Un usuario borrado no puede entrar; su contenido se mantiene.

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

13 fases, de la 0 a la 12. Cada una deja la app funcionando, con tests en verde y un commit. Las fases 0 a 4 dan una web navegable con contenido sembrado; la 8 completa el MVP funcional.

### Fase 0 — Esqueleto del proyecto

- [x] Crear el proyecto con el starter kit oficial de Livewire (Laravel 13) y Pest.
- [x] Configurar Sail con PostgreSQL y Mailpit; `.env.example` completo.
- [x] Instalar Larastan (nivel 6), Pint y Laravel Boost; crear `CLAUDE.md` con comandos y convenciones.
- [x] Localización `es` por defecto (`APP_LOCALE=es`), zona horaria `Europe/Madrid`.
- [x] GitHub Actions: Pint en modo test, Larastan y Pest contra Postgres.

Aceptación: `sail artisan test`, `vendor/bin/phpstan` y `vendor/bin/pint --test` pasan en local y en CI.

### Fase 1 — Usuarios, roles y auth

- [x] Migración de `users` con username, role, show\_nsfw, banned\_at y ban\_reason; enum `Role`.
- [x] Registro con username; verificación de email obligatoria para publicar y reportar.
- [x] Middleware `EnsureUserIsNotBanned`: cierra sesión y muestra el motivo.
- [x] Gates `access-admin` y `manage-users`; helpers `isStaff()` e `isAdmin()` en el modelo.
- [x] Seeder con un admin, un moderador y 20 usuarios, con credenciales en `.env.example`.

Aceptación: tests de registro, login, verificación, recuperación de contraseña y usuario baneado expulsado.

**Desviaciones de esta fase:**

- El starter kit de Livewire trae un campo `name` en `users` que **no existe** en el modelo de datos de este documento (solo `username`). Se ha eliminado `name` y sustituido por `username` (3–30 caracteres, `^[a-zA-Z0-9_]+$`, único) en migración, modelo, registro, ajustes de perfil y seeders/factories. Afecta a `app/Models/User.php`, `app/Concerns/ProfileValidationRules.php`, `app/Actions/Fortify/CreateNewUser.php` y las vistas de registro/perfil/menú de usuario.
- "Baneado no puede iniciar sesión" se implementó como un callback `Fortify::authenticateUsing()` que rechaza el login si `banned_at` no es nulo (antes de establecer sesión). "Se cierra en la siguiente petición" se cubre con el middleware `EnsureUserIsNotBanned` añadido al grupo `web`, para el caso de un usuario al que se banea mientras ya tenía sesión abierta.
- Alcance de `lang/es` limitado a los textos nuevos de esta fase (`lang/es/auth.php`: etiqueta de usuario y mensajes de baneo). Las pantallas de Fortify ya existentes (login, 2FA, reset de contraseña, ajustes) siguen en inglés — traducirlas por completo es trabajo de i18n aparte, no un entregable de la Fase 1, y se abordará junto a la web pública (Fase 4) para no tocar a medias.
- Añadido `config/seed.php` (nuevo, no listado originalmente) para exponer `SEED_ADMIN_EMAIL/PASSWORD` y `SEED_MODERATOR_EMAIL/PASSWORD` sin llamar a `env()` directamente en el seeder.

### Fase 2 — Modelo de dominio

- [x] Migraciones de copypastas, tags, copypasta\_tag, votes, folders, copypasta\_folder, reports y moderation\_actions según la sección Modelo de datos.
- [x] Extensión `unaccent`, columna generada `search_vector` e índice GIN.
- [x] Modelos con relaciones, casts, enums y scopes; `FeedQuery`.
- [x] Policies: CopypastaPolicy, FolderPolicy, ReportPolicy, TagPolicy, UserPolicy.
- [x] Factories y seeder: 15 etiquetas, 300 copy-pastas (10 % NSFW, fechas repartidas en 90 días), votos y favoritos aleatorios.
- [x] Listener que crea la carpeta "Favoritos" al registrarse un usuario.

Aceptación: tests de cada scope de `FeedQuery` (órdenes, ventanas de top, etiquetas, búsqueda con acentos, exclusión de ocultos) y de cada Policy.

**Desviaciones de esta fase:**

- `FeedSort::New` se llama `FeedSort::Newest` en PHP. `new` es palabra reservada y no es un nombre de caso válido de forma segura. El valor de backing sigue siendo `'new'`, así que `?sort=new` no cambia.
- `body_hash` se calcula en un mutator de `body` (`Copypasta::body()`), no en un hook `saving`. `DatabaseSeeder` usa `WithoutModelEvents`, que desactivaba el hook y dejaba el hash a `null` durante el seed. Un mutator no depende de eventos. La normalización (`squish` + `lower`) vive en `Copypasta::normalizeBody()`.
- `TagPolicy` y `UserPolicy` no reciben instancia de `Tag` en `update`/`delete` cuando la regla no depende de ella (todo el staff gestiona todas las etiquetas). Las llamadas pueden seguir pasando la instancia sin problema.
- Los usuarios creados con factories no disparan `Registered`, así que `CopypastaSeeder` crea su carpeta "Favoritos" antes de añadir favoritos.
- Los índices parciales únicos (`folders` por `is_default`, `reports` por `pending`) y el `CHECK` de `votes.value` se crean con `DB::statement`, porque el constructor de migraciones de Laravel no los soporta.
- Se añade `copypastas.user_id` y `published_at` a los `#[Fillable]` del modelo para poder crear copy-pastas con autor y fecha desde factories y seeders.

### Fase 3 — Panel `/admin` base

- [x] Panel Filament `admin` con `canAccessPanel` limitado a staff; tema y marca.
- [x] TagResource completo (sin borrado, ver desviaciones).
- [x] CopypastaResource de solo lectura con filtros; acciones ocultar, restaurar y marcar NSFW vía Actions; registro en `moderation_actions`.
- [x] ModerationActionResource de solo lectura.

Aceptación: un usuario normal recibe 403 en `/admin`; tests Livewire de las acciones ocultar y restaurar y de su entrada en el log.

Desviaciones de la Fase 3 respecto al plan original:

- **Login del panel**: no hay login propio de Filament (`->login()` omitido). El panel reutiliza el login de Fortify: un invitado en `/admin` es redirigido a `/login`. Así hay una sola pantalla de acceso.
- **Tema**: el panel usa el tema por defecto de Filament con color primario y nombre de marca. Un tema Vite propio (`resources/css/filament/admin/theme.css`) queda pendiente; no aporta nada en esta fase y obligaría a compilar para los tests.
- **Sin borrado de etiquetas**: el CRUD de etiquetas no incluye borrar. El enum `ModerationActionType` no tiene `tag_deleted`, y la especificación habla de desactivar, no de borrar.
- **Color de etiqueta de paleta fija**: el color se elige de `App\Enums\TagColor`, no es texto libre.
- **Policies `viewAny`**: `CopypastaPolicy` y `TagPolicy` no tenían `viewAny` y Filament lo exige para listar. Se añaden, junto con `ModerationActionPolicy`.
- **Acceso al panel**: `User` implementa `FilamentUser` y `HasName`. El acceso lo decide `canAccessPanel()`: staff no baneado, solo en el panel `admin`. Fase 5 ampliará esto para `/app`.


### Fase 4 — Web pública: feed y detalle

- [x] Layout público con Tailwind, cabecera, buscador y footer con enlace a `/normas`.
- [x] Componente `Feed` con orden, filtros de etiqueta, búsqueda, toggle NSFW y estado en la query string.
- [x] Orden aleatorio con semilla en sesión y botón "Barajar"; "Cargar más".
- [x] Componente `CopypastaCard`: copiar (Clipboard API + toast + contador limitado), compartir, NSFW difuminado.
- [x] Página de detalle con redirección 301 de slug, meta Open Graph y Twitter Card.
- [x] Modal de confirmación +18 para anónimos con cookie de 1 año.
- [x] Rutas alias `/top/semana`, `/top/mes`, `/top`, `/nuevos`, `/etiqueta/{slug}`.

Aceptación: tests de feed para anónimos (sin NSFW por defecto, con NSFW tras cookie), paginación aleatoria sin repetidos en 3 páginas, 404 para ocultos y 301 de slug.

**Desviaciones de la Fase 4:**

- **Tarjeta como componente Blade, no Livewire.** `CopypastaCard` es `<x-copypasta-card>` con Alpine. Un componente Livewire por tarjeta obligaría a rehidratar 20 modelos en cada petición y rompería el objetivo de 5 consultas de la Fase 10. Los componentes Livewire quedan para el feed y los filtros.
- **Copiar cuenta por petición HTTP.** Copiar envía `POST /c/{copypasta}/copia` (throttle de 120/min) y la Action `RecordCopypastaCopy` aplica el límite de una copia por copy-pasta, visitante y hora con la caché. Es una Action nueva que no estaba en la lista de Estructura.
- **Detalle sin Livewire.** `/c/{ulid}/{slug?}` es un controlador (`CopypastaController`) que autoriza con `CopypastaPolicy::view`, redirige con 301 si el slug no coincide y devuelve una vista Blade con meta tags. Los ocultos devuelven 404 a visitantes; el autor y el staff los ven con el motivo.
- **Copy-pastas sin etiquetas.** `FeedQuery` excluye solo los que tienen etiquetas y todas están desactivadas. Los que no tienen ninguna siguen visibles, porque las factories de la Fase 2 no asignan etiquetas.
- **Filtros.** Las etiquetas desactivadas se ignoran en `?tags=`, así que un enlace antiguo no devuelve vacío por error.
- **Toggle NSFW solo para anónimos.** Los usuarios registrados se rigen por `show_nsfw` (ajustes de la Fase 5) y el staff ve NSFW siempre.
- **Orden estable.** `Copypasta::scopeSort` añade `id` como último desempate. Sin él, la paginación de los tops podía repetir o perder elementos cuando empatan score y fecha.
- **`/normas` provisional.** Enlace del footer funcional con texto de marcador; el contenido real llega en la Fase 10.
- **`welcome.blade.php` eliminada.** Era la página del starter kit; `/` es ahora el feed. `ExampleTest` sigue pasando porque `home` existe.
- **`preventLazyLoading` activo fuera de producción.** Se adelanta de la Fase 10 porque la sección Rendimiento lo exige desde ya y el feed se diseñó con eager loading.
- **Botón "Publicar" provisional.** Lleva al login para invitados y al panel de usuario para autenticados hasta que exista `/app/copypastas/create` (Fase 5).
- **Índices revisados.** Con el seeder de 300 copy-pastas, `EXPLAIN ANALYZE` de las consultas del feed (aleatorio, top semanal, búsqueda y nuevos) queda por debajo de 1 ms usando los índices existentes. No se añade índice nuevo; el `Seq Scan` sobre `copypasta_tag` es aceptable con 910 filas y conviene revisarlo al crecer.
- **Compilación de assets.** `npm run build` falla dentro de Sail porque `node_modules` se instaló en macOS y `vite-plus` no encuentra su binario de Linux. Se compila con `node node_modules/.bin/vite build` usando Node 22 en el host.
- **Pendiente para fases futuras.** Los botones de votar, favorito y "..." de la tarjeta llegan en las Fases 6, 7 y 8. El criterio de 5 consultas por home se comprobará en la Fase 10.


### Fase 5 — Panel `/app`: publicar y ajustes

- [x] Panel Filament `app` para usuarios autenticados y verificados.
- [x] MyCopypastaResource limitado a los del usuario: formulario con título, cuerpo, etiquetas (máximo 5, solo activas) y NSFW; aviso de duplicado por `body_hash`.
- [x] Estado oculto visible con motivo; el formulario de edición mantiene la ocultación.
- [x] Página de ajustes: username, preferencia NSFW, email y contraseña.
- [x] Botón "Publicar" de la web pública enlazado al formulario.

Aceptación: un usuario no puede ver ni editar copy-pastas ajenos en `/app`; tests de validación y de aviso de duplicado; el feed respeta `show_nsfw`.

**Desviaciones de la Fase 5:**

- **Regla del panel de admin modificada (Fase 3).** `User::canAccessPanel()` ahora abre `/app` a cualquier miembro verificado y no baneado, también a staff. El test `ningún usuario accede a un panel distinto de admin` codificaba la regla antigua; se ha sustituido por `el panel de usuario se abre a miembros verificados…`, que comprueba la nueva regla. Se ha modificado en vez de eliminarlo.
- **Baneo en los paneles.** `EnsureUserIsNotBanned` estaba solo en el grupo `web`, y los paneles de Filament no lo usan: un baneado recibía 403 en lugar de cerrar sesión. Se añade a `AdminPanelProvider` y `AppPanelProvider`, justo después de `StartSession`, porque el `Authenticate` de Filament comprueba `canAccessPanel` antes que cualquier middleware posterior.
- **Acceso al recurso sin tocar la Policy.** `CopypastaPolicy::viewAny` sigue reservado al staff (lo fija un test de la Fase 2). `MyCopypastaResource::canViewAny()` comprueba verificado y no baneado, y `getEloquentQuery()` limita a los del autor.
- **Slug del recurso.** Filament deriva `my-copypastas` del nombre plural; se fija `copypastas` para que las rutas sean `/app/copypastas`.
- **Ajustes con la página de perfil de Filament.** `app/Filament/App/Pages/Auth/EditProfile.php` extiende la base de Filament con username, preferencia NSFW, email y contraseña. Las páginas `/settings` del starter (perfil, seguridad, 2FA, passkeys) siguen existiendo, así que email y contraseña aparecen en dos sitios.
- **Email nuevo, verificación nueva.** Cambiar el email borra `email_verified_at`, envía la verificación y redirige a `verification.notice`. Sin esto, un email no verificado podría publicar.
- **Duplicados tras publicar.** Un copy-pasta con cuerpo repetido se publica igual y se muestra una notificación con enlace al existente. `FindDuplicateCopypasta` ignora los ocultos.
- **Etiquetas.** El formulario solo ofrece etiquetas activas y una regla `exists` con `is_active` rechaza las demás. `UpdateCopypasta` conserva las etiquetas que se desactivaron después de publicar, para no perderlas al guardar.
- **Reglas de username.** `ProfileValidationRules` pasa la columna a `Rule::unique` (sin ella Laravel generaba `data.username` dentro de Filament) y corrige el tipo de retorno de `Rule::unique`, que es `Stringable`.
- **Factory de usuarios.** Los usernames generados contenían puntos, que la regla de la Fase 1 no admite; se sustituyen por guiones bajos.
- **Escritorio de `/app` vacío.** El dashboard de Filament es un placeholder hasta que existan widgets de resumen.


### Fase 6 — Votos y favoritos

- [x] Action `CastVote` transaccional con `lockForUpdate` sobre el copy-pasta; alternar, cambiar y retirar voto.
- [x] Action `ToggleFavorite` sobre la carpeta por defecto; actualiza `favorites_count`.
- [x] Botones en tarjeta y detalle con actualización optimista; modal de login para anónimos.
- [x] Rate limit: 60 votos por minuto por usuario.

Aceptación: tests de contadores tras secuencias de votos (+1, +1 de nuevo, -1), concurrencia básica, no votar lo propio, anónimo recibe redirección a login.

**Desviaciones de la Fase 6:**

- **Botones con JSON y Alpine, no Livewire por tarjeta.** Misma razón que en la Fase 4: un componente por tarjeta rehidrataría modelos en cada petición. Votar y guardar llaman a `POST /c/{copypasta}/voto` y `POST /c/{copypasta}/favorito`, que devuelven el estado real.
- **Estado del visitante en una sola consulta.** `Copypasta::scopeWithViewerState()` añade `my_vote` e `is_favorite` como subconsultas, así que el feed y el detalle no hacen consultas por tarjeta. Es una lectura de la fila del visitante, no un agregado de votos, así que respeta la regla de contadores denormalizados.
- **El cliente envía el voto que pulsa y el servidor decide.** Repetir el mismo voto lo retira y el contrario lo cambia en `CastVote`. El cliente se actualiza antes y revierte si la petición falla.
- **Anónimos.** El cliente muestra el modal "Inicia sesión para votar" sin enviar nada, y el servidor redirige a login si llega una petición sin sesión.
- **Concurrencia.** `CastVote` y `ToggleFavorite` bloquean la fila del copy-pasta con `lockForUpdate` y actualizan los contadores con deltas. El test de concurrencia comprueba que la consulta usa `FOR UPDATE` y que los contadores coinciden con el recuento real tras varios votantes. No hay pruebas con procesos paralelos.
- **Límites.** Votar, 60 por minuto por usuario (limitador `votes`). Favoritos, 120 por minuto (`throttle:120,1`); el plan no lo fijaba y lo pongo igual que la copia.
- **Permisos.** `CopypastaPolicy::vote` y `favorite` exigen copy-pasta publicado, no oculto y ajeno. Los miembros sin email verificado pueden votar y guardar, como dice la sección de Roles.
- **Carpeta por defecto.** Se añade `Folder::DEFAULT_NAME` y el listener lo usa, para que el nombre "Favoritos" esté en un solo sitio. `ToggleFavorite` crea la carpeta si el usuario no la tiene.
- **Detalle.** `CopypastaController` vuelve a leer el copy-pasta con el estado del visitante antes de renderizar.


### Fase 7 — Carpetas

- [x] FolderResource en `/app`: crear, renombrar, reordenar (drag and drop), borrar; Favoritos protegida.
- [x] Vista de carpeta con su lista de copy-pastas, quitar de la carpeta y placeholder "Contenido retirado".
- [x] Selector "Añadir a carpeta" en la web pública con checkboxes y creación rápida.
- [x] Límite de 50 carpetas por usuario.

Aceptación: tests de propiedad (no se accede a carpetas ajenas), Favoritos no borrable ni renombrable, un copy-pasta en varias carpetas.

**Desviaciones de la Fase 7:**

- **Actions de carpetas.** El plan solo nombraba `AddToFolder`. Se añaden `CreateFolder`, `RenameFolder`, `DeleteFolder`, `RemoveFromFolder` y `SyncCopypastaFolders`, para que cada mutación tenga su Action reutilizable desde Filament y desde la web pública.
- **Favoritos y contador.** Añadir o quitar la carpeta por defecto desde el selector mueve `favorites_count` igual que el corazón. `ToggleFavorite` usa ahora `Folder::ensureDefaultFor()`, el mismo helper que el listener de registro y el selector.
- **Selector en JSON y Alpine, no Livewire.** Mismo motivo que en la Fase 6: un componente por tarjeta rehidrataría modelos. Endpoints `GET`, `PUT` y `POST /c/{copypasta}/carpetas`; la creación rápida crea la carpeta y la añade en una transacción.
- **Orden de rutas.** Las rutas del selector se declaran antes de `/c/{copypasta}/{slug?}`, porque el slug opcional capturaría `GET /c/{id}/carpetas` y respondería con un 301.
- **Sin FormRequest.** La validación del selector va inline en el controlador, como en `CopypastaVoteController`, en lugar de crear `app/Http/Requests`.
- **Policy por `user_id`.** `FolderPolicy` compara `$folder->user_id` en vez de `$folder->user`, porque `preventLazyLoading` lanzaba al cargar la relación desde el selector.
- **Permisos de carpeta.** `FolderPolicy::addCopypasta` exige copy-pasta publicado y no oculto, y `removeCopypasta` solo al dueño. Quitar siempre es posible, incluso si el copy-pasta luego se oculta.
- **Ocultos y borrados.** Siguen en la carpeta con placeholder "Contenido retirado" (sin título ni cuerpo). La relación del relation manager usa `withoutGlobalScopes([SoftDeletingScope::class])` para incluir los borrados.
- **Reordenar.** Filament escribe `position` y `beforeReordering` comprueba que todas las claves sean carpetas del usuario. Favoritos se puede reordenar; solo se protegen renombrar y borrar, como pide la especificación.
- **Límite de 50.** Se aplica en `CreateFolder` (la fuente de verdad) y como regla en el formulario, para mostrar el error junto al campo.
- **Límites de petición.** El selector usa `throttle:60,1`. Las acciones de carpeta de Filament no tenían `RateLimiter` propio, igual que las de `/app` en la Fase 5. Resuelto después de la Fase 11: el trait `LimitsFolderChanges` limita a 60 cambios por minuto y usuario en `CreateFolder`, `RenameFolder`, `DeleteFolder`, `AddToFolder` y `RemoveFromFolder`. `SyncCopypastaFolders` cuenta un cambio por cada carpeta que realmente modifica.
- **Cobertura sin prueba de UI.** La acción "Quitar de la carpeta" del relation manager no se ejecuta en el harness de Livewire de Filament 5 (`callAction` no monta la acción en un relation manager). Está cubierta por `RemoveFromFolderTest` (lógica y contador) y en `FolderResourceTest` se comprueba que la acción existe. Pendiente de comprobar en el navegador antes de la Fase 10.
- **Sin migraciones.** `folders` y `copypasta_folder` ya tenían `position`, la unicidad por usuario, el índice parcial de Favoritos y la clave compuesta.


### Fase 8 — Reportes y moderación

- [x] Action `ReportCopypasta` con validación de motivo, unicidad de pendiente y rate limit de 10 por hora.
- [x] Modal de reporte en la web pública.
- [x] Auto-ocultación a los 5 reportes pendientes de usuarios distintos; ocultación inmediata y email a admins en reportes por menores.
- [x] Página `ModerationQueue` en `/admin` agrupada por copy-pasta con acciones ocultar, restaurar, marcar NSFW y descartar.
- [x] ReportResource histórico; badge con el número de pendientes en la navegación.
- [x] Notificación por email al autor cuando se oculta su copy-pasta.

Aceptación: tests del umbral de auto-ocultación, de la resolución en bloque de reportes, de los emails (Mail::fake) y de que no se pueden reportar copy-pastas propios.

**Desviaciones de la Fase 8:**

- **Auto-ocultación deja los reportes pendientes.** La especificación dice que el copy-pasta "queda arriba en la cola", y solo es posible si sus reportes siguen pendientes para que el staff los revise. Ocultar a mano sí los marca como aceptados, como pide el plan.
- **Acción compartida de ocultación.** `ConcealCopypasta` escribe el estado, resuelve (o no) los pendientes, registra en `moderation_actions` (actor nulo en la automática) y avisa al autor tras confirmar la transacción. `HideCopypasta` y `ReportCopypasta` la usan, para no duplicar la lógica.
- **Límite de 10 reportes por hora dentro de la Action.** Va con `RateLimiter::attempt` y lanza `ThrottleRequestsException` (429), así se cumple también fuera de la ruta pública.
- **Emails como Mailables en cola.** `CopypastaHiddenMail` (autor) y `ReportedMinorAlertMail` (admins verificados). Se comprueban con `Mail::fake()` y `assertQueued`. El aviso de menores no llega a moderadores ni a admins sin email verificado.
- **Solo se reportan copy-pastas visibles.** Un copy-pasta oculto o sin publicar no admite reportes (`ReportPolicy::create`). La Policy compara `user_id` para no cargar la relación.
- **Descartar reportes.** `DismissCopypastaReports` y `CopypastaPolicy::dismissReports`; el log `dismiss_reports` solo se escribe si hubo reportes pendientes que descartar.
- **Relación `pendingReports`.** `Copypasta::pendingReports()` filtra por estado pendiente; la cola la usa con `whereHas`, `with` y `withCount`/`withMin`/`withMax` sin closures, lo que permite a PHPStan tipar la consulta.
- **Recarga del autor.** `ConcealCopypasta` usa `load('user')`, no `loadMissing`: la cola carga el autor con `user:id,username` y el correo se quedaba sin dirección. Lo detectó el test de ocultación desde la cola.
- **Badge en la cola.** El número de pendientes va en la navegación de la página `ModerationQueue`, no en `ReportResource`, que es histórico de solo lectura.
- **Acciones duplicadas.** Ocultar, restaurar y NSFW en la cola repetían las de `CopypastasTable`. Resuelto después de la Fase 11: `CopypastaModerationActions` (junto a `CopypastasTable`) define las tres y `actor()`; la cola añade solo "descartar", que no está en la tabla.
- **Pendiente de comprobar en navegador.** El modal de reporte y el botón de la tarjeta están cubiertos por los tests de backend y el build de assets, pero no los he probado clicando en el navegador.

### Fase 9 — Gestión de usuarios

- [x] UserResource: lectura para moderadores, edición para admins; pestañas con copy-pastas, reportes enviados y log.
- [x] Acciones: enviar enlace de reset, banear y desbanear con motivo, cambiar rol, con las restricciones de la sección Roles.
- [x] Impersonación con el paquete elegido (verificar compatibilidad con Filament 5); banda superior en web pública y paneles; bloqueo de cambio de email y contraseña mientras dura.
- [x] Registro de todas las acciones en `moderation_actions`.

Aceptación: tests de que un moderador no puede banear ni impersonar, un admin no puede impersonar staff ni cambiarse el rol, y la impersonación queda registrada al empezar y al terminar.

**Desviaciones de la Fase 9:**

- **Paquete de impersonación.** Se usa `lab404/laravel-impersonate` 1.7.8, aprobado por ti. Es independiente de Filament, así que la misma banda y la misma lógica sirven en la web y en los paneles. No se publica su configuración: se usan los valores por defecto. `stechstudio/filament-impersonate` quedó descartado porque sus requisitos publicados solo mencionan Filament.
- **Acciones de usuario.** Además de las del plan se añaden `UnbanUser`, `ChangeUserRole`, `SendPasswordReset`, `ImpersonateUser` y `StopImpersonating`. `UserModerationActions` comparte las acciones entre la tabla y la vista; el Policy gana `unban` y `sendPasswordReset`.
- **Pestañas.** Son los relation managers de la vista (Filament los muestra como pestañas): copy-pastas, reportes enviados e historial. El historial muestra las acciones *sobre* el usuario (baneo, rol, reset, impersonación). Las acciones que el staff hizo *como* actor se consultan en el log general, que ya filtra por actor.
- **Impersonación registrada.** `ImpersonateUser` registra el inicio con el admin como actor; `StopImpersonating` registra el fin con el mismo actor y el usuario como sujeto.
- **Bloqueo durante la impersonación.** Solo se bloquea el email y la contraseña, como pide la especificación: en ajustes (`profile` y `security`) y en el perfil de `/app`. El campo de email aparece deshabilitado y el servidor rechaza el cambio. El nombre de usuario sigue editable. Los cambios de 2FA y passkeys no están bloqueados; lo dejo como pregunta abierta.
- **Banda.** Va en la web pública, en el layout autenticado de ajustes, y como render hook `BODY_START` en `/admin` y `/app`.
- **Baneo.** Lo rechaza el `authenticateUsing` de Fortify y, con sesión abierta, el middleware `EnsureUserIsNotBanned` cierra la sesión en la siguiente petición. Ya existían; los tests lo comprueban.
- **Admin sobre otros admins.** La Policy permite a un admin banear o cambiar el rol de otro staff, pero no a sí mismo, como indica el plan. Impersonar sigue prohibido a staff y a baneados.
- **Tests de bloqueo.** El harness de Livewire captura el 403 de `abort_if` y no lo relanza. Los tests comprueban que email y contraseña no cambian en la base de datos, en vez de esperar una excepción.
- **Pendiente de comprobar en navegador.** La banda, el botón "Actuar como" y las pestañas no los he probado clicando en el navegador. Los tests cubren el backend, las rutas y la presencia de la banda.


- **Ampliación posterior a la Fase 11.** Se añaden verificación manual, creación de usuarios con contraseña temporal y borrado lógico, con estas decisiones:
  - **Contraseña temporal.** Se genera con 16 caracteres sin símbolos y se muestra una sola vez en una notificación persistente. No se envía por email. `must_change_password` obliga a cambiarla antes de cualquier panel (`EnsurePasswordIsChanged`, también en el grupo `web`). El cambio se hace en `/contrasena-temporal`. Cualquier cambio de contraseña limpia el flag desde el modelo, salvo que se fije en el mismo guardado.
  - **Cuentas creadas por admin** se marcan con email verificado, porque el admin responde de ellas.
  - **Borrado lógico.** `SoftDeletes` en `users`. Los copy-pastas, reportes y entradas de log siguen apuntando al usuario y se muestra su nombre con `withTrashed()` en las relaciones. Un copy-pasta de un usuario borrado sigue visible con su autor. Si prefieres ocultarlos, es un cambio aparte.
  - **Borrado de cuenta propia** (ajustes) sigue siendo definitivo (`forceDelete`), como dice el texto de la pantalla.
  - **Tipos de log nuevos:** `user_created`, `user_deleted`, `user_restored`, `email_verified`, `email_unverified` y `verification_resent`. El campo `action` es un string, así que no hace falta cambiar el esquema.
  - **Sin 2FA ni cierre de sesiones desde admin**, como se decidió.

### Fase 10 — Endurecimiento

- [x] Rate limits finales: publicar 10 por hora, registro 5 por hora por IP, contador de copias.
- [x] Revisión de N+1 con `Model::preventLazyLoading()` en local; eager loading en el feed.
- [x] Caché de la lista de etiquetas activas y de los conteos del escritorio admin.
- [x] Cabeceras de seguridad (CSP compatible con Livewire), cookies seguras, HTTPS forzado en producción.
- [x] `sitemap.xml` con copy-pastas no NSFW, `robots.txt`, páginas 404 y 403 personalizadas.
- [x] Páginas de privacidad y cookies; textos de las normas.

Aceptación: el feed de la home ejecuta 5 consultas como máximo (test con contador de queries); auditoría de Lighthouse de 90 o más en rendimiento y accesibilidad.

**Estado de la aceptación:** el test de consultas del feed pasa (4 consultas como invitado y como miembro, máximo 5). Lighthouse **no está cumplido**: accesibilidad 100, pero rendimiento 64 con el servidor local, que no comprime. Ver la desviación de rendimiento.

**Desviaciones de la Fase 10:**

- **Límites en las Actions.** Publicar (10 por hora por autor) va en `PublishCopypasta`, y el registro (5 por hora por IP) en `CreateNewUser`, porque Fortify no admite middleware por ruta de registro. Así se aplican también fuera de la página.
- **Contador de copias.** Ya cumplía lo pedido (una copia por visitante y hora, más throttle de 120/min); no se cambia.
- **CSP con `unsafe-eval`.** Alpine y Livewire necesitan `unsafe-eval` en la build estándar. Migrar a `@alpinejs/csp` permitiría quitarlo; queda para el backlog. Las fuentes ya van en local, así que la política es `'self'` más lo anterior.
- **Cabeceras también en los paneles.** Las rutas de Filament no pasan por el grupo `web`, así que `SecurityHeaders` se añade a los dos paneles. Las redirecciones de autenticación de los paneles no las llevan; las páginas renderizadas sí.
- **Widgets del escritorio admin.** La Fase 3 los planificó y no existían. Se crean ahora (`ModerationOverviewWidget`), con los conteos cacheados 5 minutos, que es lo que la caché del plan necesitaba.
- **Caché de etiquetas con atributos planos.** `ListActiveTags` cachea atributos y los rehidrata con `Tag::hydrate()`, con clave versionada. La primera versión cacheaba modelos serializados, que al leerlos de un store real devolvían `__PHP_Incomplete_Class` y daban 500 en la home. Los tests usaban el store `array` y no lo detectaron; ahora hay un test de ida y vuelta por el store `file`.
- **Sitemap y robots sin paquete.** `/sitemap.xml` lista copy-pastas visibles y no NSFW, con tope de 50.000 URLs por el protocolo, y se cachea una hora. `/robots.txt` se genera con la URL de la app.
- **Legales como borrador.** Privacidad, cookies y normas están escritas en español y marcadas en pantalla como pendientes de revisión legal. No son textos definitivos.
- **Enlaces `unsafe` y HTTPS.** En producción se fuerza `https` en las URL y las cookies son seguras mediante `SESSION_SECURE_COOKIE`, documentado en `.env.example`. El valor real se fija en el despliegue (Fase 11).
- **Script de Livewire diferido.** El `livewire.js` del `<head>`/`body` bloqueaba el primer render (Lighthouse lo marcó con 6 s de bloqueo). Se añade `defer` con `useScriptTagAttributes`. `app.js` es un módulo de Vite en el `<head>`, así que sigue ejecutándose antes.
- **Errores de Alpine heredados de las Fases 7 y 8.** Los componentes del selector de carpetas, del modal de reporte y de las acciones de tarjeta usaban en la plantilla variables que no estaban expuestas como propiedades (`messages`, `copypastaId`, `authenticated`, `loginRequiredFolderMessage`). Las consola lo mostraba como `messages is not defined` y el botón "Añadir a carpeta" nunca abría el modal. Lo detecté al probarlo en el navegador, no con los tests PHP.

**Rendimiento de Lighthouse (pendiente).** Medido en la home como invitado, con la configuración móvil de Lighthouse:

- Accesibilidad: 100.
- Rendimiento: 64. El servidor de Sail no comprime: `livewire.js` pesa 595 KB y el CSS 400 KB sin comprimir. Con gzip serían 125 KB y 39 KB, según lo calculado con `gzip -9`.
- Pendiente: habilitar compresión en la imagen de producción (Fase 11) y repetir la auditoría. Si sigue por debajo de 90, el siguiente paso es reducir JS (la build de Livewire es fija) y el CSS.


### Fase 11 — Despliegue

- [x] Dockerfile de producción (PHP-FPM + Nginx o FrankenPHP) y `docker-compose` para staging.
- [x] Worker de colas y scheduler (`schedule:run`) configurados.
- [ ] Mailer transaccional real (SMTP o API) y dominio con SPF/DKIM.
- [x] Backups diarios de Postgres y monitorización de errores.
- [x] Comando `app:create-admin` para crear el primer admin en producción.

Aceptación: despliegue en staging desde CI con migraciones automáticas y checklist de humo (registro, publicar, votar, reportar, ocultar) superado a mano.

**Estado de la aceptación:** la imagen se construye y arranca con Postgres en red: migraciones automáticas al arrancar, `/up` responde, `/robots.txt` dinámico, cabeceras de seguridad, redirección de `/admin`, compresión (CSS de 400 KB a 46 KB transferidos) y `app:create-admin` en español. El despliegue a staging desde CI y el checklist de humo **no están hechos**: necesitan el host o plataforma de staging y el remoto del repositorio, que aún no existen. Mailer real y SPF/DKIM quedan a cargo del operador.

**Desviaciones de la Fase 11:**

- **Dockerfile en etapas.** Los estilos importan Flux y escanean vistas dentro de `vendor/`, así que el stage de assets necesita las dependencias de Composer. Node va sobre Debian (`bookworm-slim`) porque el `package-lock.json` trae los binarios nativos de `vite-plus` para glibc y no para musl (Alpine).
- **`public/robots.txt` eliminado.** Era el fichero por defecto del starter kit y sombreaba la ruta dinámica en producción: el servidor lo sirve antes que PHP. Los tests no lo detectaron porque el cliente de pruebas no pasa por el servidor; lo comprobé con la imagen.
- **Workflow de despliegue sin acciones de terceros.** Construye la imagen y la publica en GHCR con comandos directos, porque no puedo verificar los SHAs de las acciones de terceros. El despliegue por SSH solo corre si existe la variable `STAGING_HOST`.
- **TLS.** `SERVER_NAME` admite un dominio (HTTPS automático) o `:8080` detrás de un proxy que termine TLS. Con `APP_ENV=production` las URL salen en `https`.
- **Contraseña del admin fuera de la caché de configuración.** `app:create-admin` lee `ADMIN_PASSWORD` con `getenv()`, no con `config()`: `config:cache` la escribiría en disco.
- **Locale de la imagen.** `APP_LOCALE=es` en el Dockerfile; sin él, los mensajes del comando salían como claves.
- **Validación en español.** Añadido `lang/es/validation.php` con las reglas de Laravel traducidas y los nombres de campo de los formularios. Lo escribí a mano en lugar de añadir `laravel-lang`, para no introducir una dependencia nueva sin aprobación. Cubierto por `tests/Feature/Validation/SpanishValidationMessagesTest.php`.
- **Test intermitente corregido.** `ModerationOverviewWidgetTest` comprobaba `assertDontSee('3')`, que coincidía con cualquier número de la página. Ahora compara el valor cacheado.
- **Backups y monitorización.** Los backups quedan en un volumen local con 14 días de retención; la copia fuera del servidor y los logs a un agregador quedan a cargo del operador. Sin Sentry, como acordamos.

### Fase 12 — Correcciones antes de producción

Cubre los críticos y altos de la [auditoría del MVP](AUDITORIA.md), que tiene el detalle de cada hallazgo (C1–C5, A1–A6, M1–M10) y la verificación contra el código. Orden de ejecución: C1, 12.1, 12.2a, 12.2b, 12.3 y 12.4, con un commit por bloque. Ya incorpora las decisiones tomadas en esa auditoría.

#### 12.1 — Proceso y datos

- [x] Subir el repo a un remoto privado y dejar CI en verde (C1).
- [x] Action `DeleteOwnAccount`: retira votos y favoritos con deltas, anonimiza el usuario y mantiene sus copy-pastas visibles como "usuario eliminado" (C2).
- [x] FK de `copypastas.user_id`, `votes.user_id` y `reports.reporter_id` a `restrictOnDelete` (C2).
- [ ] Comando `app:recalculate-counters` que recalcula todos los contadores desde las tablas reales; ejecutarlo una vez en cada entorno con datos (C2). El comando y sus tests están hechos; falta la ejecución en staging y producción.
- [x] Anonimización programada de usuarios con borrado lógico de más de 30 días (M9).

Aceptación: tras borrar una cuenta con votos, favoritos, copy-pastas y reportes, los contadores de los copy-pastas afectados coinciden con un recuento real y los copy-pastas de otros siguen en sus carpetas.

**Desviaciones de la Fase 12.1:**

- **Email y contraseña sustituidos, no anulados.** `users.email` y `users.password` son `NOT NULL`, y Fortify compara hashes. La cuenta anonimizada recibe `eliminado-{id}@anonimo.invalid` y una contraseña aleatoria de 64 caracteres sin migración de columnas.
- **Cuenta anonimizada con `deleted_at` y `anonymized_at`.** No puede iniciar sesión, y su username y email quedan libres. Un admin no puede restaurarla (`UserPolicy::restore`).
- **"usuario eliminado" en la ficha y en el feed.** `User::displayName()` lo resuelve. Las consultas públicas cargan `anonymized_at`. El panel admin muestra el username anonimizado (`eliminado-{id}`) a propósito, para identificar la cuenta.
- **Traza en el log.** `ModerationActionType::UserAnonymized`. El autoborrado queda con el propio miembro como actor; la purga automática, sin actor.
- **Borrado propio por policy.** `UserPolicy::deleteOwnAccount`; la acción la autoriza igual que el resto.

#### 12.2a — Impersonación y staff

- [ ] Middleware `BlockDuringImpersonation` en 2FA, passkeys y borrado de cuenta, con un test por ruta. El bloqueo es por diseño y no depende de `password.confirm` ni de `current_password` (C3).
- [ ] Limpiar `auth.password_confirmed_at` al iniciar y terminar una impersonación (`ImpersonateUser`, `StopImpersonating`), con un test de que `password.confirm` falla durante la impersonación (N1).
- [ ] Impersonación con caducidad de 30 minutos y registro del fin al expirar.
- [ ] 2FA obligatorio para acceder a `/admin` (A2).
- [ ] Protección del último admin y flag de propietario para promover o degradar admins (A2).

Aceptación: un admin impersonando no puede cambiar ningún factor de autenticación ni borrar la cuenta, aunque haya confirmado su contraseña antes; ningún flujo deja la plataforma sin admins.

#### 12.2b — Usuarios

- [ ] Sustituir la contraseña temporal por invitación con enlace firmado de 72 horas que verifica el email; eliminar `must_change_password` y sus piezas (A1). Hasta que esté hecho, el admin conoce la contraseña de los usuarios que crea.
- [ ] Unificar ajustes en `/settings` y mover allí la preferencia NSFW con confirmación +18 (M2, M6).
- [ ] Abrir `/app` a usuarios no verificados y restringir con Policies; método `viewOwn` en lugar de la comprobación manual (M1, M7).
- [ ] Ocultar el email de los usuarios a los moderadores (M10).

Aceptación: un admin no puede conocer la contraseña de otro usuario; los ajustes de cuenta se editan desde un único sitio.

#### 12.3 — Moderación

- [ ] Nuevo rol `trusted` ("usuario de confianza") en el enum `Role`, entre `user` y `moderator`. No da acceso a `/admin`. Revisar las comparaciones de rol que asumen solo tres valores (C4).
- [ ] Los admins asignan y retiran el rol desde la ficha de usuario, con registro en el log. El escritorio admin sugiere candidatos: al menos 10 reportes resueltos y un 80 % aceptados (C4).
- [ ] Antigüedad mínima de 72 horas para reportar (C4).
- [ ] Reportes ponderados: 1 punto un usuario normal, 3 un usuario de confianza. Auto-ocultación a los 5 puntos (C4).
- [ ] El motivo "menores" solo auto-oculta si reporta un usuario de confianza o staff. En los demás casos, prioridad máxima en la cola y alerta inmediata a los admins (C4).
- [ ] Pérdida temporal del derecho a reportar tras 3 reportes rechazados en 30 días (C4).
- [ ] Test de descartar reportes sobre un auto-ocultado; si queda huérfano, restaurarlo en la misma Action (A3).
- [ ] Tabla `copypasta_revisions`; cada reporte guarda la revisión vista; la cola muestra el diff (A5).
- [ ] Edición libre con historial visible: "editado" en el detalle abre las versiones anteriores (A5).
- [ ] Formulario de aviso para anónimos con email, que entra en la cola (A6).
- [ ] Email de ocultación con motivo y vía de recurso (A6).

Aceptación: una cuenta de menos de 72 horas no puede reportar; un reporte por menores de un usuario que no es de confianza no oculta pero sí alerta; el moderador ve la versión reportada aunque el autor haya editado.

#### 12.4 — Infraestructura y calidad

- [ ] Redis para caché, sesiones y rate limits, como un servicio más del compose (M4).
- [ ] Semilla del orden aleatorio en la query string y columna `random_key` indexada; `EXPLAIN` con 200.000 copy-pastas (M3).
- [ ] Tests de navegador de 6 flujos en CI: copiar, votar, guardar en carpeta, reportar, ocultar e impersonar (A4).
- [ ] VPS en la UE con Ubuntu LTS: acceso solo por clave SSH, cortafuegos con 22, 80 y 443 abiertos, actualizaciones de seguridad automáticas.
- [ ] `compose.production.yaml` a partir del de staging: app, worker, scheduler, Postgres y Redis, con HTTPS automático vía `SERVER_NAME` y el dominio. `RUN_MIGRATIONS=true` solo en el servicio `app`; worker y scheduler sin esa variable; la migración se ejecuta con `migrate --isolated --force` (M5 descartado tras la verificación; esto es endurecimiento).
- [ ] Workflow de despliegue apuntando al VPS con una variable de host de producción.
- [ ] Mailer transaccional con SPF, DKIM y DMARC en el dominio (C5).
- [ ] Backups diarios de Postgres copiados a un almacenamiento externo compatible con S3, con prueba de restauración (C5).
- [ ] Checklist de humo y Lighthouse sobre el VPS.

Aceptación: el VPS desplegado desde CI con los 6 tests de navegador en verde; restauración de un backup probada; el feed aleatorio con 200.000 filas responde en menos de 50 ms.

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
