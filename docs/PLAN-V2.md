# Copy-pastas V2 — Plan de producto e implementación

Oct 4, 2026 · @Pol

La V2 convierte el MVP en un producto: identidad visual propia, un área de usuario de consumo fuera de Filament, motivos para volver (estadísticas, logros, notificaciones) y un feed personalizado. Sale a producción junto con el MVP, en 11 fases que continúan la numeración del plan original (13 a 23), y una fase final de lanzamiento (24).

## Cómo usar este plan

Este plan se ejecuta cuando la Fase 12 esté completa, incluido el bloque 12.5 de registro de eventos, del que dependen las estadísticas, los logros y el feed personalizado.

- Guárdalo en el repo como `docs/PLAN-V2.md`. `docs/PLAN.md` sigue siendo la referencia del MVP y de la Fase 12.
- La Fase 14 necesita el resultado de Claude Design en `docs/design/`: capturas de las pantallas y la lista de tokens. La sección "Brief para Claude Design" dice qué pedir.
- El flujo es el mismo que en el MVP: una fase por vez, lista de ficheros antes de codificar, tests de aceptación, CI en verde, tareas marcadas en este fichero y un commit por fase.

Reglas para Claude Code en toda la V2:

- Filament queda solo para `/admin`. Toda la interfaz nueva para usuarios va en la web pública con Livewire y Blade.
- Desde la Fase 14, solo se usan los componentes y tokens del sistema de diseño. Nada de colores ni tamaños sueltos en las vistas.
- Las reglas del MVP siguen vigentes: lógica en Actions, autorización en Policies, textos en `lang/es`.
- Toda acción nueva de usuario registra su evento con `RecordEvent`.
- Cada flujo nuevo de interfaz tiene al menos un test de navegador.
- Las rutas nuevas bajo `/c/{ulid}/...` se declaran antes de `/c/{copypasta}/{slug?}`, por el problema que apareció en la Fase 7.

## Visión, alcance y decisiones

El MVP funciona pero parece una herramienta interna: estética básica, área de usuario con aspecto de panel de administración, y un feed igual para todos. La V2 ataca esas tres cosas y añade razones para volver.

**Dentro de la V2**

- Sistema de diseño propio con modo claro y oscuro, aplicado a la web y al tema de `/admin`.
- Área de usuario en la web pública: mis copy-pastas, publicar, carpetas con su contenido y ajustes.
- Perfil público con logros y títulos, y estadísticas privadas.
- Notificaciones dentro de la app.
- Feed "Para ti", selección de etiquetas al registrarse, "no me interesa" y copy-pasta del día.
- Imágenes Open Graph, compartir como imagen y carpetas públicas.
- Plantillas con variables y variantes de un copy-pasta.
- PWA instalable.
- Endurecimiento básico de Unicode y, con prioridad baja, soporte avanzado de ASCII art.

**Fuera de la V2:** comentarios, apelaciones, login social, API pública, multidioma, subida de avatares, seguir a usuarios y recomendaciones por embeddings. Ver Backlog.

**Decisiones tomadas**

1. El área de usuario sale de Filament. El panel `/app` desaparece y sus URLs redirigen a las nuevas.
2. Tras el login, el staff va a `/admin` y el resto a la página que pedía o al feed.
3. La señal más fuerte de interés es copiar, por delante de votar.
4. Los logros son reconocimiento: ningún logro da permisos.
5. Los avatares se generan (iniciales y color); no hay subida de imágenes, que requeriría moderar imágenes.
6. El soporte avanzado de Unicode y ASCII art es lo último de la V2.
7. **Enlaces compartidos.** El `?ref=` usa `users.share_code`, un código estable por usuario. Sustituye al código aleatorio por clic de la Fase 12.5 (`RecordCopypastaShare`). Los anónimos no tienen código, así que sus visitas no se atribuyen.
8. **Upvotes netos.** Las estadísticas y `copypasta_daily_stats.upvotes` cuentan votos netos. Los eventos de voto guardan `previous` y `next` en `context`, y la agregación suma deltas. Es la primera tarea de la Fase 16. Sin producción no hace falta recalcular eventos antiguos: se resiembra.
9. **Claves foráneas.** `events.user_id` y `events.copypasta_id` con `ON DELETE SET NULL` son excepción documentada: un evento es un hecho que sobrevive a la cuenta o al copy-pasta. `copypasta_daily_stats.copypasta_id` pasa a `restrictOnDelete` en una migración nueva.
10. **Afinidad "guardar".** Solo suma +3 el botón de Favoritos (`favorite_add`). Meter un copy-pasta en una carpeta propia (`folder_add`) no suma afinidad.
11. **Sin Flux.** Se elimina la librería Flux. El sistema de diseño se construye con componentes Blade propios y Alpine (con el plugin oficial `@alpinejs/focus` para modales y desplegables). Motivos: el diseño será propio y Flux tiene un estilo difícil de adaptar; varios componentes necesarios son de Flux Pro; y las pantallas que lo usan ya se rediseñan en la Fase 14. El gráfico de estadísticas es un SVG generado en el servidor, sin librería de gráficos.

## Especificación funcional

Cada bloque describe el comportamiento esperado; las fases lo convierten en tareas y tests.

### Área de usuario

- Todo vive en la web pública con el mismo layout que el feed. El menú de usuario lleva a cada sección y, para el staff, a `/admin`. `/admin` tiene un enlace visible de vuelta a la web.
- **Mis copy-pastas:** lista con estado (visible u oculto con su motivo) y métricas por fila (score, copias, guardados). Acciones: ver, editar, borrar.
- **Publicar y editar:** formulario Livewire con título, cuerpo con contador, de 1 a 5 etiquetas activas, NSFW y, desde la Fase 21, plantilla. Vista previa de la tarjeta en vivo y aviso de duplicado como en el MVP.
- **Carpetas:** rejilla con nombre, número de copy-pastas y vista previa del primero. Crear, renombrar, reordenar y borrar; Favoritos sigue protegida.
- **Detalle de carpeta:** un feed con las tarjetas normales (copiar, votar) más "quitar de la carpeta". Los ocultos y borrados se ven como "Contenido retirado".
- **Ajustes:** los unificados en `/settings` en el bloque 12.2b, con el diseño nuevo.

### Perfil público

- `/u/{username}` muestra avatar generado, título elegido, fecha de alta, contadores públicos (publicados, copias recibidas, upvotes recibidos), logros, sus copy-pastas (top y nuevos) y sus carpetas públicas.
- Los copy-pastas de cuentas borradas muestran "usuario eliminado", sin enlace.
- El username se puede cambiar una vez cada 30 días. El anterior redirige al nuevo durante 90 días y nadie más puede usarlo en ese tiempo.

### Estadísticas privadas

- Totales: publicados, copias recibidas, guardados por otros, visitas, score, upvotes y downvotes.
- Gráfico de 30 días con copias, votos y visitas por día, desde `copypasta_daily_stats`.
- Mejor copy-pasta, el que más ha crecido en 7 días y las 3 etiquetas con más copias.
- Fiabilidad como reportador y progreso hacia usuario de confianza.

### Notificaciones

- Notificaciones de base de datos de Laravel. Campana con contador en la cabecera y página `/notificaciones` con marcar como leídas.
- Tipos: logro desbloqueado; hitos de un copy-pasta (10, 100 y 1.000 copias o upvotes); copy-pasta oculto o restaurado; reporte aceptado; alguien publicó una variante de tu copy-pasta; ascenso a usuario de confianza.
- Varios hitos del mismo copy-pasta en una hora se agrupan en una notificación.
- Preferencias por tipo en ajustes. Las de moderación no se pueden desactivar.
- Sin emails nuevos en la V2; siguen los de moderación del MVP.

### Logros y títulos

- Definidos en código, con una tabla `user_achievements`. Se evalúan en cola a partir de los eventos de dominio, de forma idempotente.
- **Rareza:** porcentaje de usuarios que tiene cada logro, recalculado una vez al día.
- **Título:** el usuario elige uno de sus logros con título y se muestra junto a su nombre en tarjetas y perfil.
- **Secretos:** aparecen como "???" hasta conseguirlos.
- **Antitrampas:** los upvotes y visitas recibidos solo cuentan si vienen de cuentas verificadas de más de 72 horas o, en visitas, de visitantes distintos que no son el propio usuario. Solo cuentan los reportes aceptados.
- **Revocación:** no son automáticas. Un admin puede revocar un logro con motivo, y queda en el log.
- **Retroactividad:** el comando `app:backfill-achievements` calcula los logros con los datos existentes al lanzar.

| Familia | Logro | Condición | Título |
| --- | --- | --- | --- |
| Creador | Primera pegada | Publicar 1 copy-pasta | Recién pegado |
| Creador | Pegador habitual | Publicar 10 | Pegador habitual |
| Creador | Fábrica de pastas | Publicar 100 | Maestro del Ctrl+V |
| Popularidad | Primer aplauso | 1 upvote recibido | — |
| Popularidad | Bien recibido | 10 upvotes recibidos | Aplaudido |
| Popularidad | Favorito del público | 100 upvotes recibidos | Favorito del público |
| Popularidad | Leyenda | 1.000 upvotes recibidos | Leyenda del foro |
| Copias | Copiado | 10 copias recibidas | — |
| Copias | Viral | 100 copias recibidas | Viral |
| Copias | Patrimonio de internet | 1.000 copias recibidas | Patrimonio de internet |
| Tendencia | En tendencia | Un copy-pasta en el top 10 semanal | En tendencia |
| Coleccionista | Primera carpeta | Crear una carpeta | — |
| Coleccionista | Coleccionista | Guardar 50 copy-pastas | Coleccionista |
| Guardián | Vigilante | 1 reporte aceptado | — |
| Guardián | Guardián | 10 reportes aceptados | Guardián |
| Guardián | Centinela | 50 reportes aceptados | Centinela |
| Difusión | Mensajero | 1 visita por tus enlaces compartidos | — |
| Difusión | Altavoz | 100 visitas | Altavoz |
| Difusión | Megáfono | 1.000 visitas | Megáfono |
| Votante | Crítico | Votar 100 veces | — |
| Variantes | Remezclador | Publicar una variante | Remezclador |
| Variantes | Discípulo aventajado | Una variante tuya supera en score al original | Discípulo aventajado |
| Plantillas | Plantillero | Una plantilla tuya copiada 10 veces | Plantillero |
| Veterano | Veterano | 1 año de cuenta | Veterano |
| Secreto | Noctámbulo | Publicar entre las 3:00 y las 4:00 | Noctámbulo |
| Secreto | Dinamita | 100 copias de un copy-pasta en 24 horas | Dinamita |

### Feed "Para ti"

- **Afinidad por etiqueta:** copiar +3, guardar +3, upvote +1, downvote −2, "no me interesa" −3, etiqueta elegida al registrarse +5. Las puntuaciones se reducen a la mitad cada 30 días.
- **Mezcla por página:** 70 % de las 5 etiquetas más afines, 20 % de exploración en el resto y 10 % de publicados en las últimas 48 horas con pocos votos.
- **Orden dentro de cada grupo:** calidad y frescura. Score más el doble de copias, dividido por la edad en horas más 2, elevada a 1,5.
- **Exclusiones:** lo que ya votaste, copiaste o marcaste como "no me interesa", y lo mostrado en los últimos 500 resultados (en Redis).
- **Explicación** bajo cada tarjeta: "Porque te gusta #etiqueta" o "Para que descubras algo nuevo".
- **Mecánica:** lista de 200 candidatos por usuario, cacheada 30 minutos y paginada sobre ella.
- Es la pestaña por defecto para usuarios con al menos 5 señales o con etiquetas elegidas. Los anónimos no la ven.
- **Al registrarse**, y la primera vez que entra un usuario existente tras el lanzamiento, se piden al menos 3 etiquetas favoritas. Se puede saltar.
- **No me interesa:** opción en el menú "..." de la tarjeta. La oculta del feed del usuario y resta afinidad a sus etiquetas.

### Copy-pasta del día

- Cada día a las 00:00 un job elige el de mejor puntuación de calidad y frescura de las últimas 48 horas, sin NSFW y que no haya sido elegido antes.
- El staff puede sustituirlo desde `/admin`. Se muestra destacado arriba de la home.

### Difusión

- **Enlaces compartidos:** el botón de compartir añade `?ref=` con el código público del usuario (`share_code`). Una visita cuenta una vez por visitante y día, y nunca la del propio usuario.
- **Imágenes Open Graph:** 1200 × 630 con título, primeras líneas y marca. Genérica para NSFW. Se genera al publicar o editar y se guarda en disco. Antes de elegir tecnología hay que hacer una prueba con emojis y acentos; evitar meter un navegador completo en la imagen Docker.
- **Compartir como imagen:** se genera en el navegador con la misma plantilla y se comparte con la Web Share API o se descarga. Para NSFW pide confirmación.
- **Carpetas públicas:** interruptor "pública" con URL `/col/{public_id}`, nombre y descripción opcional de hasta 280 caracteres. Aparecen en el perfil. Los copy-pastas ocultos no se muestran a otros. El staff puede volver privada una carpeta, con registro en el log.

### Plantillas y variantes

- **Plantillas:** casilla "Es plantilla" al publicar. Las variables usan `{{nombre}}`, con letras, números y guion bajo, hasta 10 distintas. Si se marca la casilla sin variables, error de validación.
- Al copiar una plantilla se abre un modal con un campo por variable y vista previa; se copia el resultado. El evento de copia lleva `template: true` en el contexto. La tarjeta muestra el distintivo "Plantilla".
- **Variantes:** botón "Crear variante" en el detalle, que abre el formulario prellenado y guarda `parent_id`. El detalle muestra "Variante de…" y la lista de variantes por score. Si el original está oculto o borrado, se muestra "Contenido retirado".

### PWA

- Manifest, iconos, color de tema y service worker con página sin conexión. No se cachea contenido dinámico.

### Unicode

- **Básico (Fase 13):** quitar de los títulos los caracteres de control de dirección (U+202A a U+202E, U+2066 a U+2069) y los de ancho cero (U+200B a U+200D, U+FEFF). Aislar la dirección del cuerpo con `unicode-bidi: isolate`. Recortar con CSS el desbordamiento vertical del zalgo en las tarjetas. Si el slug queda vacío, usar el ULID.
- **Avanzado (Fase 23):** tipo de contenido ASCII art con monoespaciada, sin saltos y con scroll horizontal; longitud contada en grafemas; normalización NFC antes de calcular `body_hash`; batería de tests con textos extremos.

## Modelo de datos

Seis tablas nuevas y columnas nuevas en tres existentes. Las FK siguen la regla de la Fase 12: `restrictOnDelete` hacia `users` y `copypastas`, para que ningún borrado físico se salte las Actions.

| Tabla | Cambio | Campos | Índices y restricciones |
| --- | --- | --- | --- |
| `users` | Columnas nuevas | title\_key nullable, username\_changed\_at, share\_code (8 caracteres), onboarded\_at, notification\_prefs jsonb, theme (`system`/`light`/`dark`) | unique(share\_code) |
| `username_history` | Nueva | user\_id, username, changed\_at | index(username, changed\_at) |
| `user_achievements` | Nueva | user\_id, achievement\_key, unlocked\_at, revoked\_at nullable, revoked\_by\_id nullable, revoke\_reason | unique(user\_id, achievement\_key) |
| `notifications` | Nueva (estándar de Laravel) | id uuid, type, notifiable, data jsonb, read\_at, timestamps | la de Laravel |
| `user_tag_affinities` | Nueva | user\_id, tag\_id, score float, updated\_at | PK(user\_id, tag\_id) |
| `copypasta_dismissals` | Nueva | user\_id, copypasta\_id, created\_at | PK(user\_id, copypasta\_id) |
| `featured_copypastas` | Nueva | date, copypasta\_id, picked\_by\_id nullable | PK(date); unique(copypasta\_id) |
| `folders` | Columnas nuevas | is\_public bool, public\_id ULID nullable, description (máximo 280) | unique(public\_id) |
| `copypastas` | Columnas nuevas | is\_template bool, parent\_id nullable (nullOnDelete), og\_image\_path nullable; en la Fase 23, kind (`text`/`ascii`) | index(parent\_id) |

Notas:

- La puntuación de afinidad se guarda sin aplicar el paso del tiempo. Al leerla o actualizarla se aplica `score × 0,5^(días desde updated_at / 30)`.
- `achievement_key` referencia una definición en código. La rareza se calcula a diario y se guarda en caché, sin tabla.
- `parent_id` es la única FK de `copypastas` con `nullOnDelete`: si un original desaparece físicamente, la variante sobrevive sin enlace.

## Rutas y pantallas

15 pantallas nuevas o rehechas en la web pública y 3 añadidos en `/admin`. Todas las de la web usan el sistema de diseño de la Fase 14.

| Ruta | Pantalla | Acceso | Fase |
| --- | --- | --- | --- |
| `/` | Feed con pestañas: Para ti, aleatorio, top semanal, top mensual, top histórico, nuevos; copy-pasta del día arriba | Todos; Para ti solo con sesión | 14, 19 |
| `/c/{ulid}/{slug}` | Detalle rediseñado, con variantes y compartir como imagen | Todos | 14, 20, 21 |
| `/publicar` | Publicar con vista previa en vivo | Usuario verificado | 15 |
| `/c/{ulid}/editar` | Editar | Autor | 15 |
| `/c/{ulid}/variante` | Crear variante con el formulario prellenado | Usuario verificado | 21 |
| `/mis-copypastas` | Mis copy-pastas con estado y métricas | Usuario | 15 |
| `/carpetas` | Mis carpetas | Usuario | 15 |
| `/carpetas/{id}` | Detalle de carpeta como feed | Dueño | 15 |
| `/col/{public_id}` | Carpeta pública | Todos | 20 |
| `/u/{username}` | Perfil público con logros | Todos | 16, 18 |
| `/estadisticas` | Estadísticas privadas | Usuario | 16 |
| `/notificaciones` | Lista de notificaciones | Usuario | 17 |
| `/bienvenida` | Elegir etiquetas favoritas | Usuario sin `onboarded_at` | 19 |
| `/settings/*` | Ajustes con el diseño nuevo, más título, tema y preferencias de notificaciones | Usuario | 14, 17, 18 |
| `/_componentes` | Catálogo del sistema de diseño | Solo entorno local | 14 |
| `/admin` → Copy-pasta del día | Ver el elegido y sustituirlo | Staff | 19 |
| `/admin` → Usuarios → Logros | Ver y revocar logros con motivo | Admin | 18 |
| `/admin` → Carpetas públicas | Listado y acción "hacer privada" | Staff | 20 |

El panel `/app` se elimina en la Fase 15: `/app/copypastas` redirige con 301 a `/mis-copypastas`, `/app/folders` a `/carpetas` y `/app` a `/estadisticas`.

## Fases de implementación

Once fases, de la 13 a la 23, y la 24 de lanzamiento. Las 13 a 15 cambian la base (diseño y área de usuario); las 16 a 18 dan motivos para volver; las 19 a 22 son descubrimiento, difusión y contenido; la 23 es la de menor prioridad.

### Fase 13 — Base de la V2

- [x] Redirección tras el login: staff a `/admin`, resto a la página pedida o al feed. Enlaces cruzados entre web y `/admin`.
- [x] Endurecimiento básico de Unicode según la especificación.
- [x] Columnas `username_changed_at` y `share_code` en `users`; tabla `username_history`; límite de un cambio cada 30 días y redirección del username antiguo durante 90 días.
- [x] Componente de avatar generado (iniciales y color derivado del id).
- [x] Comprobar que el bloque 12.5 registra eventos de todas las acciones existentes; completar lo que falte.
- [x] Filtrado de bots por user agent: rastreadores y generadores de vista previa (WhatsApp, Telegram, Discord, Slack, X, Facebook, Google, etc.). Un bot no genera `detail_view`, no suma `views` y no atribuye `ref`.
- [x] Migración de `copypasta_daily_stats.copypasta_id` a `restrictOnDelete` (decisión 9).

Aceptación: tests de redirección por rol; un título con U+202E se guarda sin él; un segundo cambio de username en 30 días falla; el username antiguo redirige al nuevo; test con user agents reales de cada plataforma (los de bots no generan `detail_view` ni suman `views`); borrar un copy-pasta con estadísticas falla por la FK.

**Desviaciones de la Fase 13:**

- **Eventos nuevos (completa la lista de 12.5).** `EventType` suma `publish`, `update`, `folder_create`, `folder_rename`, `folder_delete` y `username_change`. Las acciones de publicar, editar, carpetas y cambio de usuario no registraban nada. La columna es `varchar`, así que no hay migración. La cuenta borrada sigue sin evento.
- **Redirección tras el login.** El staff va siempre a `/admin` (decisión 2), aunque pidiera otra página. Fortify usa `TwoFactorLoginResponse` tras el reto de 2FA, así que la misma clase `LoginResponse` implementa los dos contratos. Un test de navegador cubre el staff sin 2FA: pasa por `/admin` y termina en la confirmación de contraseña previa a `/settings/security`.
- **Test de login actualizado.** `AuthenticationTest` esperaba `dashboard`; ahora espera el feed (`route('home')`), que es lo que decide la fase.
- **`/u/{username}` provisional.** La página de perfil es de la Fase 16. Hasta entonces el nombre actual responde 404 y un nombre antiguo redirige con 301 durante 90 días. Un nombre que otra cuenta ocupa ahora no redirige.
- **Historial y anonimización.** `AnonymizeUser` borra el historial de la cuenta anonimizada: así no reserva el nombre ni redirige a una cuenta eliminada. Un cambio hecho por un admin desde Filament se guarda en el historial, pero no consume el cupo de 30 días del usuario.
- **Bots.** Se usa `jaybizzle/crawler-detect` (1.4.1). Su lista no cubre Telegram ni Discord, así que `CrawlerDetector` añade esos dos patrones. Una petición sin user agent cuenta como bot. Los tests de navegador y de feature envían un user agent humano por defecto (`TestCase::HUMAN_USER_AGENT`), porque una petición sin UA ahora es bot.
- **Títulos.** La limpieza elimina U+200B a U+200D, incluido el ZWJ. Como efecto colateral, un emoji compuesto con ZWJ (p. ej. de familia) se separa en sus partes al publicar. Es lo que pide la especificación; el conteo por grafemas llega en la Fase 23. La migración de datos no toca títulos que queden vacíos tras limpiarlos (no hay ninguno en la base de desarrollo).
- **Migración de `share_code`.** Se rellena en la propia migración con `Str::random(8)` comprobando unicidad. La clave no depende del id ni del username.
- **Avatar provisional.** `<x-avatar>` usa clases de Tailwind neutras y se rediseña en la Fase 14. Solo está montado en los tests; las vistas lo usan en la Fase 14.
- **Zalgo y bidi.** El cuerpo de las tarjetas y del detalle usa `[unicode-bidi:isolate]` y un recorte vertical con `overflow-hidden`. No he revisado el resultado visual con texto zalgo.

### Fase 14 — Sistema de diseño

- [x] Leer `docs/design/`. Si no existe o está incompleto, parar y avisar.
- [x] Tokens en Tailwind (colores, tipografía, radios, sombras, espaciado) para modo claro y oscuro. El modo sigue al sistema por defecto, con selector guardado en `users.theme` y en cookie para anónimos.
- [x] Fuentes autoalojadas, porque la CSP solo permite `'self'`.
- [x] Componentes Blade: botón, tarjeta de copy-pasta, pestañas, chip de etiqueta, modal, toast, estado vacío, skeleton de carga, avatar, menú de usuario.
- [x] Rediseño con esos componentes de feed, detalle, login, registro, recuperación, ajustes y páginas legales.
- [x] Tema propio de Filament para `/admin` con los mismos tokens.
- [x] Página `/_componentes`, solo en local, con todos los componentes en los dos modos.
- [x] Componentes propios: botón, input, textarea, select, checkbox, switch, campo de código de 6 dígitos (2FA), modal, desplegable, pestañas, toast, chip de etiqueta, avatar, skeleton y estado vacío. Todos aparecen en `/_componentes`.
- [x] Alpine con el plugin oficial `@alpinejs/focus` para modales y desplegables.
- [x] Migrar todas las vistas que usan `<flux:*>` (login, registro, recuperación, ajustes, 2FA, passkeys, menús) a los componentes propios.
- [x] Eliminar `livewire/flux` de `composer.json` y sus assets (estilos en `resources/css/app.css` y vistas en `resources/views/flux`).
- [x] Tests de navegador: el staff entra con un código TOTP real en el campo nuevo, no con el código de recuperación.

Aceptación: ninguna vista usa colores o tamaños arbitrarios de Tailwind (comprobación automática en CI); ningún `<flux:*>` en `resources/views` (comprobación en CI); modal, desplegable y pestañas usables solo con teclado (Tab, Escape, flechas), con foco atrapado en el modal y atributos ARIA correctos, cubierto con tests de navegador; accesibilidad de Lighthouse de 95 o más en claro y oscuro; los tests de navegador de la Fase 12 siguen en verde.

**Desviaciones de la Fase 14a** (la 14b y la 14c quedan sin marcar):

- **Ruta de `/_componentes`.** Se registra en los entornos `local` y `testing`, no solo en `local`: el entorno de los tests es `testing` y `isLocal()` no lo cubriría. Producción nunca ejecuta tests, y la ruta no existe fuera de desarrollo.
- **Sin `class="dark"` desde el servidor.** `@fluxAppearance` gestiona la clase `.dark` desde su propio localStorage y chocaría con ella. Los tokens nuevos dependen solo de `data-theme` y de la media query, así que no necesitan la clase. Los layouts de Flux conservan su `class="dark"` fijo hasta la 14b.
- **Preferencia de tema sin middleware.** La directiva `@themeAttributes` (en `AppServiceProvider`) escribe el atributo en el `<html>` de los seis layouts. La resuelve `App\Support\ThemePreference`: usuario primero, cookie `theme` después, `system` por defecto.
- **Evento `theme_change`.** Lo registran también los anónimos (con hash de visitante), porque la regla de V2 pide evento para toda acción nueva de usuario.
- **Validación inline en el controlador de tema.** No hay `FormRequest` porque el resto de controladores públicos validan con `$request->validate()`.
- **Tokens añadidos al diseño.** `--color-avatar-ink` (tinta de las iniciales, igual en los dos modos) y la utilidad `bidi-isolate` (Tailwind 4 no tiene una para `unicode-bidi`, así que evitamos el valor arbitrario de la Fase 13).
- **Escala tipográfica y radios sobrescriben los valores por defecto de Tailwind.** `text-lg`, `rounded-lg`, etc. ya significan lo del diseño. Las vistas sin migrar cambian de tamaño hasta la 14b. La paleta `zinc` se conserva para esas vistas.
- **Tarjeta con pequeñas diferencias.** El borde destacado es de 1 px (1,5 px requeriría valor arbitrario); el desenfoque NSFW usa `blur-md` (12 px frente a 9 px del diseño); las etiquetas usan `text-xs` (12 px) en vez de 12,5 px. Las acciones (votar, copiar, guardar, compartir) son presentacionales: se conectan a sus acciones en la 14b.
- **Excepciones de valores arbitrarios.** `tests/Feature/Design/ArbitraryTailwindValuesTest.php` falla con cualquier clase `[...]` nueva. Las excepciones de vistas que la 14b migra son temporales y se borran en esa fase.
- **Showcase con textos en `lang/es`.** Los textos de interfaz de `/_componentes` salen de `lang/es/ui.php`; los títulos de las tarjetas de muestra son contenido de ejemplo.
- **Fuentes y dependencias.** `@fontsource-variable/bricolage-grotesque` y `@fontsource/jetbrains-mono` (CSS), `@alpinejs/focus` (registrado en `resources/js/app.js`) y `mallardduck/blade-lucide-icons` 2.x (sobre `blade-ui-kit/blade-icons`).
- **Factory y modelo.** `UserFactory` define `theme` como `Theme::System`, igual que el default de la base de datos. Sin ello, un usuario creado en memoria con `actingAs()` no tenía el valor. `User` declara `@property Theme $theme` para que PHPStan vea el tipo.
- **Test de navegador.** Solo el del modal del showcase (abre y queda visible). Los tests de teclado (Tab, Escape, flechas, foco atrapado) son de la 14c, como dice la aceptación.

**Desviaciones de la Fase 14b, primera mitad** (login, registro, recuperación, confirmación de contraseña, verificación de email, invitación, 2FA, passkeys y ajustes — perfil, seguridad, apariencia, borrado de cuenta; feed, detalle, páginas legales, menú de usuario y cabecera/sidebar de `/app` quedan para la segunda mitad):

- **`ui.modal` gana tres props opcionales.** `open` (estado inicial, para `:open="$errors->isNotEmpty()"` en el modal de borrar cuenta), `wire-model` (nombre de una propiedad Livewire a entrelazar con `@entangle`, para el modal de borrar passkey que antes se abría con `wire:model` desde PHP) y `close` (método Livewire invocado una vez, cuando el modal pasa a cerrado por Escape, el fondo o el botón "Cerrar" — nunca en el render inicial — para reproducir el `@close` de Flux). Los tres son compatibles con el uso ya existente en `/_componentes`.
- **`ui.button` gana la variante `danger`** (`bg-bad-bg text-bad`, el mismo par suave que ya usan los estados ok/warn/bad) para los botones destructivos: desactivar 2FA, eliminar passkey, eliminar cuenta. Añadida al showcase.
- **`ui.password-input`, componente nuevo.** Envuelve `ui.input` y añade el botón de mostrar/ocultar (iconos `lucide-eye`/`eye-off`) que Flux daba con `viewable`. Usado en los 8 campos de contraseña migrados. Añadido al showcase con las cadenas `ui.password.show`/`hide`.
- **Variable `--cp-qr-invert` en `tokens.css`.** El modal de activar 2FA invertía el QR con `$flux.appearance`/`$flux.dark` (magias de Flux); sin Flux, la inversión depende de `data-theme` igual que el resto de tokens.
- **`@livewireStyles`/`@livewireScripts` en los tres layouts de auth.** `@fluxScripts` traía consigo el arranque de Livewire (y con él, Alpine); al quitarlo había que añadir explícitamente los dos directivas de Livewire, o ningún `x-data` de la página funcionaba — incluido el toggle de contraseña y el propio login con passkey.
- **Apariencia reconectada a `ThemePreference`.** El `flux:radio.group` leía y escribía `$flux.appearance`, un estado de Flux que nunca tocaba la preferencia real del usuario. Ahora son tres formularios que postean a `route('theme.update')` (de la 14a), con el valor activo resuelto en la vista vía `app(ThemePreference::class)->current()` para no tocar la clase del componente.
- **Sin migrar a `lang/es` las cadenas que ya estaban en inglés sin traducir** (p. ej. "Log in", "Passkeys", "Update password"). Varias las comprueban literalmente `SecurityTest`, `AuthRedirectFlowsTest` y el helper `signInInBrowser` de `tests/Pest.php`; migrarlas es un cambio aparte que tocaría esos tests, no pedido en este mensaje.
- **`signInInBrowser()` sigue usando el código de recuperación**, no un TOTP real. El criterio de aceptación de la Fase 14 sobre esto queda pendiente para la 14c: cambiarlo afecta a seis tests de navegador que usan ese helper para entrar como staff.
- **`layouts/auth/card.blade.php` y `layouts/auth/split.blade.php` migrados pero sin usar.** `layouts/auth.blade.php` siempre resuelve a `auth.simple`; los otros dos son herencia del starter kit de Laravel, ya sin ninguna vista que los referencie.
- **Excepciones de valores arbitrarios retiradas.** Las de `layouts/auth/split.blade.php`, `pages/settings/layout.blade.php` y `⚡two-factor-setup-modal.blade.php` en `ArbitraryTailwindValuesTest`; las de `copypasta-card`, `impersonation-banner`, `layouts/app/header.blade.php` y `flux/navlist/group.blade.php` siguen hasta la segunda mitad.

**Desviaciones de la Fase 14b, segunda mitad** (menú de usuario y cabecera/sidebar de `/app`; cierra la migración de Flux). El feed, el detalle y las páginas legales quedan para cuando se aborde ese rediseño, ya sin relación con Flux:

- **Contenido de la sidebar sin cambios.** "Dashboard" (la vista placeholder del starter kit, sin contenido real) y los enlaces "Repository"/"Documentation" (al repo de Laravel, no al del proyecto) se quedan tal cual: es contenido del starter kit nunca adaptado, y la Fase 15 reconstruye esta zona entera con el menú de usuario definitivo. Decisión explícita del propietario del proyecto.
- **Drawer móvil hecho a mano, no en el sistema de diseño.** `layouts/app/sidebar.blade.php` monta su propio panel deslizante (backdrop, Escape, transform) en vez de usar `ui.modal` o un componente nuevo: es específico de esta sidebar, que la Fase 15 sustituye por completo. Sin `x-trap.inert` a propósito — con la sidebar fija en escritorio dentro del mismo árbol, atraparía el foco también fuera de pantallas pequeñas.
- **`ui.menu-item` gana la prop `type`** (por defecto `button`, antes fija). La necesita el botón de "Log out", que es un `<button type="submit">` dentro de un formulario, no un enlace.
- **`layouts/app/header.blade.php` borrado, no migrado.** A diferencia de `auth/card.blade.php` y `auth/split.blade.php` (migrados aunque sin uso), esta variante no la referencia ninguna ruta y su contenido apunta al repositorio de Laravel; migrar algo que nadie puede alcanzar no aportaba nada.
- **`resources/views/flux/` borrado entero.** `navlist/group.blade.php` y los cuatro overrides de iconos (`layout-grid`, `folder-git-2`, `chevrons-up-down`, `book-open-text`) quedaron huérfanos al migrar sidebar/menú a Lucide; Lucide ya trae esos cuatro iconos de serie, por eso existían los overrides.
- **`Flux::toast()` sustituido por `$this->dispatch('ui-toast', message: ...)`** en `⚡profile.blade.php` y `⚡security.blade.php` (perfil actualizado, contraseña actualizada). Es la única línea de lógica que toca esos dos ficheros en toda la Fase 14b: necesaria para quitar la dependencia, igual de intacta queda el resto (comprobado con los mismos tests de la 12.2a/12.2b).
- **`livewire/flux` fuera de `composer.json`**, con el `@import`, los dos `@source` y las reglas `[data-flux-*]`/`--color-accent-foreground`/`--color-accent-content` quitados de `resources/css/app.css`; `@fluxAppearance` quitado de `partials/head.blade.php`. El CSS compilado baja de 420 KB a 46 KB. La paleta `zinc` de `@theme` se queda: la siguen usando las vistas de feed/detalle/legales sin migrar.
- **Sin ningún `<flux:*>` en el proyecto.** El criterio de aceptación de la Fase 14 sobre esto ya se cumple antes de terminar la fase entera.

**Desviaciones del rediseño de páginas legales y cabecera pública** (bloque B del rediseño pendiente de la Fase 14: cabecera/pie de `layouts/public.blade.php`, el banner de impersonación, las páginas legales y las de error; el feed, la tarjeta y el detalle — con toda su interactividad — quedan para el bloque A):

- **Los toasts globales (`toast`/`login-required`) no se tocan, solo se reskinan.** `layouts/public.blade.php` sigue escuchando esos dos eventos tal cual los despacha `copypastaActions`/`copypastaFolders`/`copypastaReport` en `resources/js/app.js`; no se tocó ese JavaScript en este bloque, que no le correspondía. Si el bloque A decide unificar con el evento `ui-toast` de `ui.toast`, se hace entonces.
- **`x-app-logo` reemplaza el texto plano de la cabecera pública.** La cabecera no tenía nunca el icono de la marca, solo el nombre en texto; se iguala con el resto de cabeceras del sitio (settings, /app) en vez de reproducir la inconsistencia anterior.
- **El buscador de la cabecera no usa `x-ui.input`.** No tiene hueco para una etiqueta visible en una cabecera de una sola fila; mantiene el `aria-label` que ya tenía y los mismos tokens de borde/fondo que el resto de campos.
- **`z-[60]` del banner de impersonación, con la razón reescrita.** Seguía siendo el único valor arbitrario justificado del fichero; el motivo original ("por encima de los overlays de Flux") ya no aplicaba.
- **Las excepciones de `copypasta-card.blade.php` y `public/copypasta.blade.php`** en `ArbitraryTailwindValuesTest` cambian de texto ("se quitan en la 14b" → "se quitan cuando llegue la migración de feed/detalle"), porque siguen sin tocarse: son del bloque A.
- **Tests de navegador intermitentes bajo carga.** La suite completa falló una vez en un test de reportar un copy-pasta por timeout; en aislado y en una repetición de la suite completa pasó limpio. No es una regresión de este bloque — ni `copypasta-actions` ni el modal de reporte se tocaron aquí.

**Desviaciones del bloque A del rediseño pendiente de la Fase 14** (feed, tarjeta de copy-pasta con su interactividad real, detalle, modales de carpeta y reporte). Cierra el rediseño completo de la Fase 14 salvo el tema de Filament y el test de navegador con TOTP real, que siguen pendientes:

- **`ui.copypasta-card` deja de ser solo presentacional.** Gana un prop `copypasta` (modelo, opcional): cuando está presente, calcula sus propias URLs/mensajes y usa `x-data="copypastaActions(...)"` (voto, copiar, guardar, compartir) en vez del `{ revealed: false }` de solo-NSFW; cuando no está (como en `/_componentes`), se comporta exactamente igual que antes. El botón "⋯" pasa a ser un `ui.dropdown` con "Añadir a carpeta" y "Reportar" (antes botones sueltos).
- **`components/copypasta-card.blade.php` (sin namespace) pasa a ser un adaptador fino.** Antes tenía toda la lógica e interactividad; ahora solo traduce un `Copypasta` de Eloquent a los props presentacionales de `ui.copypasta-card` (incluida la conversión de color de etiqueta, ver más abajo) y le reenvía el modelo para que esta calcule el resto.
- **`components/copypasta-actions.blade.php` eliminado.** Quedó huérfano: su contenido vive ahora dentro de `ui.copypasta-card`, y nada más lo incluía.
- **Prop `context` en `ui.copypasta-card`**, además de `source`/`position`. El detalle lo necesitaba para no perder el `ref` (código de enlace compartido) que `EventContext::fromRequest()` ya traía; el feed sigue usando `source`/`position` sueltos.
- **`@js()` no se compila dentro de un atributo de un componente Blade, solo en etiquetas HTML normales.** Lo usé mal en los dos `x-on:click` del menú "⋯" (`@js($copypasta->getKey())` dentro de `<x-ui.menu-item x-on:click="...">`) y Blade lo dejó como texto literal, rompiendo el JavaScript de toda la página. Solucionado con `'{{ $copypasta->getKey() }}'`, igual que ya hacía el código original en las mismas etiquetas.
- **Mapeo de los 10 colores de `TagColor` (el admin de Filament) a los 5 tonos del sistema de diseño.** `tags.color` guarda valores como `red`/`teal`/`indigo`, pero `ui.tag-chip` solo entiende `t1`…`t5`; sin el mapeo, cualquier etiqueta con un color fuera de ese conjunto rompía la página con un error de índice no definido. Añadido `TagColor::tone()`, usado solo al construir la tarjeta pública; el selector de color del admin no cambia.
- **Colisión de texto "Guardar" entre el botón de favoritos de la tarjeta y el de confirmar carpeta.** El botón de guardar/favorito de la tarjeta nueva dice "Guardar" (antes decía "Añadir/Quitar de favoritos", oculto para lectores de pantalla); coincide con el texto del botón de guardar selección de carpetas. Añadido `data-test="folders-save-button"` al segundo para que los tests de navegador no sean ambiguos; nada cambia para quien usa la página.
- **`tests/Browser/CopypastaFlowsTest.php` actualizado para los nuevos botones.** El voto ya no es el carácter "▲" sino un botón solo-icono (`aria-label` de `ui.card.vote_up`); "Añadir a carpeta" y "Reportar" ahora viven dentro del menú "⋯", así que el test abre ese menú antes de pulsarlos.
- **El toast global se queda como estaba.** `copypastaActions`/`copypastaFolders`/`copypastaReport` siguen despachando el evento `toast` (no `ui-toast`); no hacía falta unificarlo para esta migración y tocar `resources/js/app.js` sin necesidad no entraba en el alcance.

**Desviaciones del cierre de la Fase 14** (14c: tema de Filament, tests de teclado, capturas y Lighthouse; traducción completa de auth/2FA/passkeys/ajustes; `tags.color` pasa de 10 colores a los 5 tonos del sistema de diseño; toasts unificados; limpieza final):

- **TOTP real de `signInInBrowser()` necesitó primero un secreto real en `UserFactory::withTwoFactor()`.** El secreto fijo `'secret'` era demasiado corto para `PragmaRX\Google2FA\Google2FA` (lanza `SecretKeyTooShortException`); ahora se genera con `TwoFactorAuthenticationProvider::generateSecretKey()`. El código se calcula con `Google2FA::getCurrentOtp()` (el contrato de Fortify no expone generación de códigos, solo `generateSecretKey()`/`qrCodeUrl()`/`verify()`).
- **`tags.color` migra de 10 colores a los 5 tonos (`t1`…`t5`) con el mismo mapeo que tenía `TagColor::tone()`.** Migración de datos irreversible (`2026_10_09_120000_migrate_tag_colors_to_design_tones`). El enum `TagColor` queda con 5 casos y `tone()` se elimina: ya no hace falta traducir nada al construir la tarjeta pública.
- **Selector de color del admin sustituido por un `ViewField` a medida** (`filament.forms.components.tag-color-field`), porque Filament no tiene un campo nativo que muestre la muestra real de cada tono. Usa los hex de modo claro de `tokens.css` directamente, sin acoplarse al tema de Filament.
- **Tema de Filament por color semilla, no por clases CSS fijas por componente.** `->colors(['primary' => '#5B2EFF', 'gray' => '#55527C'])` deja que Filament genere sus propias escalas de 50 a 950 (claro y oscuro) a partir de esos dos hex, en vez de sobrescribir clases de utilidad (`.fi-sidebar`, `.fi-input`…) con valores fijos: sigue el mecanismo propio de Filament, se adapta solo a su alternador de modo oscuro (clase `.dark` en `<html>`, confirmado en `vendor/filament/filament/resources/css/index.css`) y no depende de que las clases internas de Filament no cambien entre versiones. No reproduce pixel a pixel `bg`/`surface`/`border`/`ink`/`muted`, pero son el mismo tono violeta con el mismo primario de marca.
- **`resources/css/filament/admin/theme.css` escanea también `resources/views/filament/**`, no solo `filament/admin`.** El `ViewField` de color vive en `resources/views/filament/forms/components/`; sin ese `@source`, Tailwind no generaba las clases de utilidad que usa (p. ej. `peer-checked:border-gray-900`).
- **Tests de teclado de modal, desplegable y pestañas añadidos a `tests/Browser/ComponentsShowcaseTest.php`**, sobre la instancia "light" de `/_componentes`: Escape cierra el modal y el foco queda atrapado dentro mientras está abierto; las flechas abren y recorren el desplegable y Escape devuelve el foco al disparador; las flechas mueven la pestaña seleccionada. El desplegable de la demo gana `:id="'demo-dropdown-'.$mode"` (igual que ya tenía el modal con `demo-modal-{{ $mode }}`) porque la sección "Identidad" de la misma página también muestra un desplegable con `aria-haspopup="menu"` y los selectores quedaban ambiguos sin él.
- **Traducción completa de auth, 2FA, passkeys y ajustes a `lang/es`.** Todas las vistas de `pages/auth/*`, `pages/settings/*` y los componentes de passkeys quedan sin cadenas en inglés. Los tests que comprobaban texto literal (`SecurityTest`, `AuthRedirectFlowsTest`) pasan a usar `__('settings.security.*')`/`@login-button` en vez de `'Passkeys'`/`'Log in'`.
- **Bug encontrado por la propia traducción: `desktop-user-menu.blade.php` tenía `__('Settings')` y `__('Log out')` sin punto.** En Laravel, una clave sin punto se resuelve como *grupo* de traducción (`lang/{locale}/Settings.php`), no como cadena literal; al no existir ese fichero, `__()` devolvía un array vacío y `e()` lanzaba `TypeError` al imprimirlo — rompía con un 500 cualquier página que incluyera el menú de usuario autenticado (lo delató `SecurityTest`). Corregido con `ui.settings`/`ui.sign_out` en `lang/es/ui.php`.
- **`layouts/auth/card.blade.php` y `layouts/auth/split.blade.php`, borrados.** Quedaron migrados pero sin uso desde la 14b (ver su desviación); confirmado por `grep` que ninguna vista los referencia antes de borrarlos.
- **Toasts unificados en el evento `ui-toast`.** `copypastaActions`, `copypastaFolders` y `copypastaReport` (en `resources/js/app.js`) despachan `ui-toast` en vez de `toast`; `layouts/public.blade.php` deja de tener su propio `<div x-on:toast.window="...">` y usa `<x-ui.toast />`, el mismo componente que ya usan los layouts de auth y ajustes. El evento `login-required` (modal aparte, sin relación con los toasts) no se toca.
- **Botón de favorito como interruptor accesible.** `ui.copypasta-card` ya calculaba `aria-pressed` y el texto reactivo desde el bloque A; solo cambia la cadena activa de `lang/es/ui.php` (`card.saved`) de "Guardada" a "Guardado", concordando con "Guardado" en vez de con "Copy-pasta" (femenino) para sonar como estado del botón, no como descripción del contenido.
- **`layouts/auth/simple.blade.php` gana una etiqueta `<main>`.** Lighthouse marcaba `landmark-one-main` en las páginas de auth (login, registro, recuperación…) porque el contenido no estaba envuelto en ningún landmark de región principal; es el único layout de auth que se renderiza (los otros dos recién borrados nunca se usaron), así que corrige las seis pantallas de una vez.
- **Capturas de pantalla y resultados de Lighthouse en `storage/app/private/qa-screenshots/`** (ya ignorado por git vía el `.gitignore` propio de `storage/app/private`, no hace falta tocar el `.gitignore` del proyecto): feed, detalle, login y ajustes, en móvil (375×812) y escritorio (1280×900), claro y oscuro — 16 capturas. Lighthouse de accesibilidad (`--only-categories=accessibility`) en las mismas 4 páginas × 2 modos: 100/100 en las ocho combinaciones tras el arreglo del landmark. Lighthouse 13 necesita Node ≥20 (el proyecto usa Node 18 para el resto de herramientas); se ejecutó con una instalación de Node 22 aparte, sin tocar `package.json` ni el Node del proyecto.

### Fase 15 — Área de usuario fuera de Filament

- [x] Páginas Livewire: mis copy-pastas, publicar con vista previa, editar, carpetas y detalle de carpeta.
- [x] Reutilizar las Actions y Policies existentes; ninguna lógica de negocio en los componentes.
- [x] Eliminar el panel `/app` y redirigir sus URLs con 301.
- [x] Menú de usuario con todas las secciones.
- [x] Tests de navegador: publicar, editar, crear carpeta, añadir y quitar de una carpeta, copiar desde una carpeta.

Aceptación: `/app` responde 301; todo lo que un usuario hacía en el MVP funciona sin Filament; el detalle de carpeta muestra tarjetas con copiar y quitar.

**Desviaciones de la Fase 15:**

- **Redirecciones reales, no las literales del encargo.** El slug real del recurso de carpetas de Filament era `carpetas` (`/app/carpetas`), no `/app/folders`: se redirige la URL que existía de verdad. Se añade además `/app/copypastas/create` → `/publicar`, el enlace que usaba la cabecera pública.
- **`layouts/app/sidebar.blade.php` tenía más alcance del documentado.** Además de `/dashboard` (uso explícito), era el layout *implícito* de Livewire (`config('livewire.component_layout') = 'layouts::app'`) de las tres páginas de `/settings/*`, que no lo nombraban. Las tres ganan `#[Layout('layouts::public')]` explícito; `/dashboard` se queda (lo usa Fortify como `home` de varios flujos y tres tests existentes) pero pasa a `<x-layouts::public>`.
- **Menú de usuario: se reutiliza `<x-desktop-user-menu>`**, extendido con "Mis copy-pastas" y "Carpetas", en vez de construir un cajón móvil nuevo — el dropdown ya es accesible por teclado a cualquier anchura y no había ya ninguna sidebar que reemplazar dentro del layout público.
- **`description` en `folders` se adelanta de la Fase 20** (solo esa columna; `is_public`/`public_id` siguen en Fase 20). Nueva Action `UpdateFolderDescription`, autorizada por `FolderPolicy::view` (ownership) en vez de `update`, para que Favoritos admita descripción aunque no admita renombrar. Sin `EventType` nuevo.
- **Reordenar carpetas con botones subir/bajar**, no arrastrar — no hay librería de drag-and-drop instalada y añadir una no estaba pedido.
- **`/publicar` nunca da 403.** Un usuario sin verificar recibe 200 con un aviso para verificar el email (reutilizando `auth.verify_email.*`); la verificación se comprueba en la vista, no en el middleware de la ruta. El botón "Publicar" de la cabecera enlaza siempre a `/publicar` para cualquier autenticado, verificado o no.
- **`CopypastaEditController` y `CopypastaForm::mount()` devuelven 404, no 403**, para un copy-pasta ajeno — mismo patrón que `CopypastaController::show` (oculta la existencia en vez de revelarla).
- **Bug encontrado y corregido: `RemoveFromFolder` no admitía copy-pastas borrados.** Usaba `Copypasta::query()` (con el scope de borrado blando activo) para bloquear la fila; quitar de una carpeta un copy-pasta ya borrado lanzaba `ModelNotFoundException`. Pasa a `Copypasta::withTrashed()`.
- **Contador de título (`/120`) resuelto en Alpine, sin depender del servidor** — cuenta `$event.target.value.length` directamente en el input. Es una mejora deliberada: ningún texto plano nuevo depende de un viaje de red para sentirse "en vivo".
- **Bug real encontrado y corregido: el contador de título rompía su propio `wire:model.live`.** El contador "/120" tenía su propio `x-on:input` en el mismo `<input>` que `wire:model.live="title"`, compitiendo con el `x-model` que Livewire ata dinámicamente al elemento; bajo el driver de Playwright de Pest 4 (no reproducido de forma consistente en un navegador real) esto hacía que, de forma intermitente, la petición de guardado del título nunca se enviara — el cuerpo y las etiquetas sí persistían porque no comparten elemento. Corregido leyendo el contador directamente de `$wire.title.length` (reactivo, sin listener propio), eliminando el conflicto de raíz en vez de solo mitigarlo en el test.
- **Bug real encontrado y corregido: el botón de publicar colisionaba con el CTA de la cabecera.** Los dos decían exactamente "Publicar"; un `press()` por texto en el test resolvía a veces el enlace de la cabecera en vez del botón del formulario, navegando a `/publicar` de nuevo en lugar de enviarlo. El botón del formulario gana `data-test="publish-submit-button"` (mismo patrón que `@folders-save-button`) y el test lo usa en vez de buscar por texto.
- **Un `wait(1)` tras `visit('/publicar')`, antes de la primera interacción.** Sin él, la prueba falla con más frecuencia (confirmado en una tanda de 6 repeticiones): la primera interacción en una página recién cargada puede llegar antes de que Alpine termine de atar sus directivas. Con los dos bugs de arriba corregidos y este margen, la prueba pasa 8/8 en tandas sueltas y de forma estable dentro de la suite completa de navegador.
- **Un fallo suelto más en la suite completa de navegador, no de esta fase.** `ModerationFlowsTest` (sin tocar en la Fase 15) falló una vez al ejecutar la suite entera y pasó limpio en aislado — la misma intermitencia bajo carga ya documentada en la Fase 14, no una regresión de esta fase.

### Fase 16 — Perfil público y estadísticas

- [x] Primera tarea: votos netos (decisión 8). `previous` y `next` en el `context` de los eventos de voto, y agregación por deltas. Sin producción no hace falta recalcular eventos antiguos: se resiembra.
- [x] Gráfico de 30 días como componente Blade que genera SVG en el servidor, sin librería de gráficos. Accesible: título y descripción en el SVG, y los mismos datos en una tabla oculta para lectores de pantalla.
- [x] Página `/u/{username}` con contadores públicos, copy-pastas y carpetas públicas (estas últimas se activan en la Fase 20).
- [x] Enlaces al perfil desde el autor de cada tarjeta y del detalle; "usuario eliminado" sin enlace.
- [x] Página `/estadisticas` con totales, gráfico de 30 días, mejor copy-pasta, el que más crece, etiquetas fuertes y fiabilidad de reportes.
- [x] Consultas de estadísticas solo sobre `copypasta_daily_stats` y contadores; nunca sobre `events` en bruto.

Aceptación: los totales coinciden con un recuento directo en un test con datos sembrados; la página de estadísticas hace 6 consultas como máximo (en la práctica, 5); el perfil de una cuenta borrada responde 404; el gráfico tiene título, descripción y tabla equivalente.

**Desviaciones de la Fase 16:**

- **Sin migraciones nuevas.** `copypasta_daily_stats.upvotes`/`downvotes` están declaradas `unsignedInteger`, pero Postgres no tiene un tipo sin signo nativo: Laravel compila eso a un `integer` normal, sin `CHECK >= 0` (confirmado contra el esquema real). Los deltas netos de un día pueden ser negativos, igual que ya pasa con `favorites`, sin tocar el esquema.
- **`/app` → `/mis-copypastas` corregido a `/app` → `/estadisticas`.** La Fase 15 lo había dejado apuntando a `/mis-copypastas`, en contra de lo que dice la introducción de este plan ("`/app` a `/estadisticas`"); se corrige aquí junto con su test.
- **"Actualizado hace X" viene de una clave de caché (`stats.last_aggregated_at`), no de una columna nueva.** `events:aggregate` la escribe con `Cache::forever()` al terminar. Evita una migración para un dato que ya expresa justo lo que el job sabe: cuándo corrió por última vez.
- **La tabla accesible del gráfico es siempre visible para lectores de pantalla (`sr-only`), no un botón "Ver como tabla" como en el diseño.** El plan solo pide "los mismos datos en una tabla oculta para lectores de pantalla"; un toggle visible es una pantalla más sin pedir. El test de navegador de aceptación (cambiar de métrica y comprobar que la tabla cambia) usa `assertSourceHas`, que lee el HTML sin exigir visibilidad.
- **`App\Support\Numbers::abbreviate()` nuevo**, reutilizado por el perfil y las estadísticas (perfil, 3 contadores; estadísticas, KPIs y tabla): mismo formato "1,8k" que ya usaba la tarjeta para el score, sin tocar esa vista.
- **Bug real encontrado y corregido: Livewire no puede hidratar un `Carbon` anidado en un array.** `ComputeUserStats` guardaba `Carbon` dentro de `range` y `lastAggregatedAt`; al ser propiedades públicas de `UserStats` (array), cada interacción posterior (cambiar de métrica) fallaba con "incomplete object" al deserializar el snapshot. Se formatean a texto plano dentro de la Action, antes de que el array llegue al componente.
- **Bug real encontrado y corregido: `fill="var(--color-vote)"` en el SVG salía negro.** Los tokens de Tailwind 4 están en `@theme inline`, que no publica las variables en `:root`: `var(--color-vote)` no resuelve a nada fuera de las utilidades generadas. El gráfico usa `class="fill-vote"` / `class="stroke-border"` / `class="fill-muted"` en vez de `style`/atributos con `var()`.
- **`wire:key` dinámico en `<x-ui.daily-chart>`.** El estado de Alpine del gráfico (el texto al pasar el ratón) se construye una vez al montar `x-data`; sin una `wire:key` que cambie con la métrica, Livewire parchea el elemento existente en vez de sustituirlo y ese estado queda con los datos de la métrica anterior. La clave es `daily-chart-{{ $metric }}`.
- **Cinco valores arbitrarios de Tailwind sustituidos por la escala estándar** (`min-h-[22px]` → `min-h-6`, `border-[1.5px]` → `border-2`, `min-w-[480px]` → `min-w-lg`, `min-w-[220px]` → `min-w-56`, `min-w-[320px]` → `min-w-80`), exigido por `ArbitraryTailwindValuesTest`.
- **`UsernameRedirectTest` y `AppPanelRedirectTest` (de la Fase 13/15) actualizados.** Esperaban el 404 provisional de `ProfileController` y la redirección antigua de `/app`; se actualizan a la página real de esta fase, no se tocan sus demás casos (redirección 301, expiración a los 90 días, cuenta anonimizada).
- **Un fallo suelto más en la suite completa de navegador, no de esta fase.** `UserAreaFlowsTest::el_selector_de_carpetas_se_maneja_solo_con_teclado` falla igual en el commit anterior a esta fase (confirmado con `git stash`); la misma intermitencia bajo carga ya documentada en la Fase 14 y la 15.

### Fase 17 — Notificaciones

- [x] Tabla de notificaciones de Laravel y una clase por tipo.
- [x] Campana con contador en la cabecera, actualizada al navegar y cada 60 segundos.
- [x] Página `/notificaciones` con marcar una o todas como leídas.
- [x] Hitos de copias y upvotes (10, 100, 1.000) detectados al actualizar contadores.
- [x] Agrupación de hitos del mismo copy-pasta en una hora.
- [x] Preferencias por tipo en ajustes; las de moderación son obligatorias.

Aceptación: cada tipo se genera en su caso y solo una vez; un tipo desactivado no se genera; la agrupación funciona con dos hitos seguidos.

**Desviaciones de la Fase 17:**

- **Añadir un tipo nuevo (Fases 18 y 21)** son tres pasos: un caso en `NotificationType` (clase, icono, tono, si es obligatoria), una clase que extiende `AppNotification` y sus líneas en `lang/es/notifications.php`, más una rama en `NotificationPresenter`. Esas clases no se crean en esta fase.
- **La columna `notifications.type` guarda el valor del enum (`milestone`, `copypasta_hidden`…), no el nombre de la clase** (`databaseType()`), y `data` es `jsonb`. Índice en `created_at` para el borrado y un índice parcial `(notifiable_type, notifiable_id) WHERE read_at IS NULL` para la campana.
- **Hitos:** `copypasta_milestones` (índice único) se escribe con `insertOrIgnore`, sin modelo ni factory. Si un contador cruza varios umbrales a la vez, se registran todos y se notifica el más alto. Con el copy-pasta oculto, sin publicar o borrado, o el autor baneado o anonimizado, el hito no se registra y se detecta cuando vuelva a ser válido; con el tipo desactivado se registra pero no se notifica. La detección va en `DB::afterCommit` desde `CastVote` (solo cuando el resultado es un upvote) y `RecordCopypastaCopy`; `RecalculateCounters` no notifica.
- **Agrupación:** la ventana de una hora se mide desde el `created_at` de la notificación sin leer; el hito se añade a `data.milestones` y la lista se ordena y muestra por `updated_at`.
- **Reporte aceptado:** texto genérico, sin título ni enlace, para no revelar un contenido que ya está oculto. Una Action nueva, `AcceptCopypastaReports`, la comparten `ConcealCopypasta` y `MarkCopypastaNsfw`. Las ocultaciones automáticas no aceptan reportes, así que tampoco notifican.
- **Oculto y restaurado** son obligatorias. La de oculto enlaza al detalle mientras el copy-pasta no esté borrado (el autor puede abrirlo aunque esté oculto); la de restaurado exige que esté visible. Se envía además del email de moderación, que se mantiene. Hitos y reporte aceptado muestran «Contenido retirado» sin enlace si el destino está oculto, borrado o no existe. Ninguna notificación se crea para cuentas baneadas, borradas o anonimizadas.
- **Ascenso a confianza:** hoy solo ocurre a mano con `ChangeUserRole`; no hay promoción automática. Solo notifica al pasar de `user` a `trusted`; bajar a un miembro del staff a `trusted` no es un ascenso.
- **Se corrige un hueco del MVP: los reportes de «NSFW sin marcar» no se resolvían al marcar el copy-pasta como NSFW.** Ahora `MarkCopypastaNsfw` los acepta (solo ese motivo, con `resolved_by` y `resolved_at`), avisa a sus reporteros y deja el número en `meta.accepted_reports` del registro de moderación; la fiabilidad del reportero ya contaba los reportes aceptados. Queda fuera: un reporte de ese motivo sobre un copy-pasta que ya era NSFW (el autor lo marcó después) sigue pendiente hasta que se descarte.
- **La restauración se unifica:** `DismissCopypastaReports::restoreIfOrphaned` llama a `RestoreCopypasta`, que es el único sitio que notifica el restaurado y escribe el registro.
- **Abrir una notificación es una acción de Livewire** (`open()`), no un enlace: un GET que cambiara el estado se activaría con la precarga de `wire:navigate`. Marca como leída, registra `notification_open` con el tipo en el `context` y redirige al destino; una notificación sin destino solo se marca. Durante una impersonación no se marca, no se registra el evento y no se muestran los botones «Marcar leídas». Las acciones de lectura se limitan a 120 por minuto y por usuario.
- **`wire:poll` y pestañas ocultas:** Livewire 4.4.7 solo reduce el sondeo de una pestaña en segundo plano (lo ejecuta un 5 % de las veces) y `.visible` mira el viewport, no la pestaña. El `wire:poll.60s` vive dentro de un `<template x-if="visible">` con `document.hidden`, así que no existe mientras la pestaña está oculta, y al volver se refresca de inmediato.
- **La lista del desplegable no se consulta hasta abrirlo** (`loadList()`); cargar una página cuesta solo el contador cacheado (Redis, invalidado con `NotificationSent` y en las lecturas masivas).
- **El presupuesto de 5 consultas del feed para miembros calienta antes la caché de la campana** (`FeedQueryCountTest`); en producción el contador sale de Redis. Un test nuevo fija que con la caché fría la cabecera añade una sola consulta.
- **Borrado programado (`notifications:prune`, diario)** por fecha de creación: leídas de más de 90 días y no leídas de más de 180.
- **Test de navegador de hitos:** el flujo de aceptación usa notificaciones sembradas con `notify()`; la generación de cada tipo y la agrupación se prueban en tests de Feature.

### Fase 18 — Logros y títulos

- [x] Definiciones en código con el catálogo de la especificación y tabla `user_achievements`.
- [x] Evaluación en cola a partir de los eventos de dominio, idempotente, con las reglas antitrampa.
- [x] Notificación al desbloquear (Fase 17).
- [x] Sección de logros en el perfil: conseguidos, pendientes con progreso, secretos como "???" y porcentaje de usuarios.
- [x] Selector de título en ajustes y título visible junto al nombre.
- [x] Revocación por admin con motivo y log.
- [x] Comando `app:backfill-achievements`.

Alcance: esta fase implementa la infraestructura completa y las familias Creador, Popularidad, Copias, Tendencia, Coleccionista, Guardián, Votante y Veterano, más los secretos Noctámbulo y Dinamita. Los logros de Difusión (Fase 20) y de Variantes y Plantillas (Fase 21) se añaden en esas fases sobre esta misma infraestructura.

Aceptación: un test por familia, incluido uno que demuestre que los upvotes de cuentas de menos de 72 horas no cuentan; ejecutar el backfill dos veces no duplica nada; un logro revocado no muestra su título.

**Desviaciones de la Fase 18:**

- **Registro único.** `App\Enums\Achievement` (familia, métrica, umbral, clave de título, secreto, icono y tono), `AchievementMetric` (qué mide cada métrica y cómo se calcula su progreso) y `AchievementFamily`. Un logro nuevo sobre una métrica existente es un caso del enum más sus claves en `lang/es/achievements.php` (nombre, descripción y, si da título, el título). Una métrica nueva necesita además el código que la mueve y su consulta en `BackfillAchievements`. Un test comprueba que cada logro tiene sus textos y que la descripción nombra su umbral.
- **El progreso se escribe dentro de la transacción de la acción; el job solo lee, concede y notifica.** El encargo decía que lo actualizaban los listeners que evalúan. Con deltas en la cola, un reintento los contaría dos veces y un rollback de la acción dejaría el progreso adelantado. `user_achievement_progress` (PK `user_id, metric`) lo mueve `AdjustAchievementProgress` con un upsert atómico (`add` para contadores, que nunca bajan de 0, y `raiseTo` para flags). Cada acción encola `EvaluateUserAchievementsJob` con las métricas tocadas, con `afterCommit`.
- **Qué mide cada métrica.** `published`: copy-pastas del usuario que existen ahora y están visibles; suma al publicar o restaurar y resta al borrar u ocultar por moderación (un test cubre que borrar uno ya oculto no resta dos veces). `votes_cast` y `saved` son los votos y favoritos activos, no los acumulados: votar y retirar no suma. `saved` es solo Favoritos (decisión 10); cualquier vía que toque la carpeta por defecto lo mueve (`ToggleFavorite`, `AddToFolder`, `RemoveFromFolder`). `folders_created`, `copies_received`, `reports_accepted` y `night_publications` no bajan nunca; Favoritos no cuenta como carpeta creada. Los logros ya conseguidos no se pierden nunca.
- **`votes.counts_for_achievements` (migración nueva).** Se fija al votar: cuenta si el votante tiene el email verificado y la cuenta tiene 72 horas o más. Retirar o cambiar un upvote solo resta si había contado. Anonimizar a un votante (`AnonymizeUser`) retira del autor los upvotes que contaban. Los votos antiguos los clasifica el backfill con `votes.updated_at` y las fechas de verificación y alta del votante.
- **Nueva Action `DeleteCopypasta`.** El borrado propio estaba en `MyCopypastas::delete()` y `CopypastaForm::delete()` (`$copypasta->delete()` directo, sin Action). Ahora los dos llaman a la Action, que autoriza con la Policy y mueve el progreso. `ConcealCopypasta` y `RestoreCopypasta` ajustan `published` con `AdjustPublishedProgress`.
- **Los "jobs" periódicos son comandos programados**, como el resto del proyecto: `achievements:grant-trending` (cada hora), `achievements:grant-veterans` y `achievements:refresh-rarity` (diarios).
- **En tendencia:** el top 10 semanal usa el orden de la pestaña pública "top semana" (`FeedSort::TopWeek`), sin NSFW y solo con score mayor que 0 (sin esa condición, con pocos copy-pastas cualquiera entraría en el top). Es una bandera: una vez conseguido, no se pierde.
- **Veterano** deriva su progreso de `users.created_at` (días de cuenta sobre 365); no se guarda. El job diario encola la evaluación de las cuentas activas con 365 días que aún no lo tienen.
- **Noctámbulo:** de 3:00 a 3:59 en la zona de la app (Europe/Madrid), medido sobre `published_at`. La especificación decía "entre las 3:00 y las 4:00"; manda el encargo (a las 4:00 no se concede). Se cuenta al publicar, aunque luego se oculte o se borre.
- **Dinamita:** se mira al registrar una copia ajena, una vez registrado el evento, solo si el contador del copy-pasta llega a 100, y cuenta los eventos `copy` de las últimas 24 horas con el índice `(copypasta_id, created_at)`, sin las copias del propio autor (es una métrica recibida). Es una bandera.
- **Cuentas baneadas, borradas o anonimizadas:** no se evalúan (no se les concede nada), pero su progreso sigue sumando. `UnbanUser` (y `RestoreUser`, que se añade por coherencia) encola una evaluación completa para conceder lo que cumplieron mientras tanto.
- **Notificación "logro desbloqueado".** `NotificationType::AchievementUnlocked` (configurable, no obligatoria), `AchievementUnlockedNotification`, claves en `lang/es/notifications.php` y una rama en `NotificationPresenter` que enlaza a `/u/{username}#logros` con el usuario de la sesión (la lista siempre es la propia, así no hay consulta extra). `GrantAchievement` solo notifica cuando `insertOrIgnore` inserta de verdad: dos evaluaciones simultáneas no notifican dos veces. Con el tipo desactivado el logro se concede igual.
- **Backfill (`app:backfill-achievements`, `BackfillAchievements`).** Una consulta por métrica: los contadores se sustituyen por el valor recalculado (borrar e insertar en una transacción; conviene lanzarlo con poco tráfico) y las banderas solo suben, así que repetirlo no cambia nada. Concede con `INSERT … ON CONFLICT DO NOTHING` (un revocado no vuelve) solo a cuentas activas, sin notificar, y refresca la rareza. Las copias recibidas y Dinamita salen de los `events` que se conservan (13 meses) y En tendencia, del top actual: no se puede reconstruir más atrás. El desfase menor con el cálculo en vivo: `folders_created` cuenta las carpetas que existen (en vivo no baja al borrar).
- **Rareza:** `RefreshAchievementRarity` guarda en caché (`achievements.rarity`, sin caducidad) el porcentaje de cuentas activas (sin banear, sin anonimizar, sin borrar) con cada logro, sin contar los revocados. Por debajo del 1 % se muestra "<1 %"; mientras el job no haya corrido no se muestra rareza.
- **Perfil.** `ListProfileAchievements` lo prepara con dos consultas como máximo (filas de `user_achievements` y, solo para el dueño, el progreso). Los pendientes son el **siguiente escalón de cada familia** y no todos, para que la lista sea corta; los secretos sin conseguir salen como "???" sin descripción solo al dueño. Los demás ven los conseguidos y el número de secretos conseguidos, nunca cuáles son. Un logro revocado no aparece en ninguna lista. La fecha usa "12 de marzo de 2026".
- **Título.** Página nueva `/settings/titulo` (`title.edit`) con un radio por título conseguido y "Ninguno"; guarda al cambiar y registra `title_change` (`ChangeUserTitle`, máximo 20 cambios por hora). `users.title_key` guarda la clave del título (la misma del logro). Se muestra en tarjetas, detalle y perfil sin consultas extra: `title_key` entra en los `select` de `user:` de `Feed`, `ProfileCopypastas`, `FolderDetail` y `CopypastaController`, y `User::titleLabel()` lo traduce con `lang`. Las cuentas baneadas o anonimizadas no muestran título, y anonimizar lo borra.
- **Admin.** Pestaña "Logros" en la ficha de usuario (`AchievementsRelationManager`, solo admin; `UserPolicy::manageAchievements`) con revocar y restaurar, ambas con motivo obligatorio y registradas en `moderation_actions` (`revoke_achievement` y `restore_achievement`). Revocar quita el título si era el activo; restaurar no notifica ni vuelve a fijar el título.
- **`ModerationNotificationsTest` se ajusta** para ignorar las notificaciones de logros: las acciones de moderación ahora también mueven progreso (p. ej. aceptar un reporte concede Vigilante) y esos tests hablan de moderación.
- **Test de navegador:** tras pulsar "Publicar" hay que esperar a la navegación (`assertPathBeginsWith('/c/')`): el título del formulario aparece también en la vista previa y un `assertSee` pasaba antes de publicar.

### Fase 19 — Descubrimiento

- [x] Tabla `user_tag_affinities` actualizada desde los eventos, con el decaimiento de 30 días.
- [x] Pantalla `/bienvenida` al registrarse y en la primera visita de usuarios existentes, saltable.
- [x] Pestaña "Para ti" con la mezcla 70/20/10, exclusiones, explicación por tarjeta y lista de candidatos cacheada.
- [x] "No me interesa" en el menú de la tarjeta, con `copypasta_dismissals`.
- [x] Copy-pasta del día: job a las 00:00, destacado en la home y sustitución desde `/admin`.

Aceptación: con afinidades sembradas, al menos el 60 % de una página de Para ti pertenece a las etiquetas afines y al menos el 10 % a otras; nada descartado ni votado aparece; dos páginas seguidas no repiten elementos; un usuario sin señales ve el top semanal hasta elegir etiquetas.

**Desviaciones de la Fase 19:**

- **Un usuario sin señales no ve el top semanal por defecto: ve la pestaña de antes (aleatorio).** El criterio de aceptación del plan decía top semanal; manda el encargo ("la pestaña por defecto sigue siendo la actual"). Para ti es la pestaña por defecto con al menos una etiqueta favorita o 5 señales.
- **Señales sin contador.** `TagAffinities::defaultsToForYou()` las cuenta con una lectura acotada (`LIMIT` por tabla) de votos, primeras copias, Favoritos y descartes, y mira las favoritas en la misma consulta. No hay columna de contador en `users`.
- **Mezcla 14/4/2 por página de 20** (70 %, 20 % y 10 %), en posiciones fijas: exploración en 4, 9, 14 y 18, recientes en 7 y 16 (cuenta desde 0), el resto afinidad. Los grupos no se solapan: afinidad son los copy-pastas de las 5 etiquetas con más afinidad efectiva positiva; recientes, los de las últimas 48 horas con 5 votos o menos (up más down) fuera de esas etiquetas; exploración, el resto. Todo en `config/affinity.php`.
- **Fórmula de calidad en un solo sitio:** `App\Support\CopypastaQuality`, `(score + 2 × copias) / (edad en horas + 2)^1,5`. La usan los tres grupos de Para ti y el copy-pasta del día.
- **Relleno.** Un grupo corto toma de los otros (afinidad: exploración y recientes; exploración: afinidad y recientes; recientes: afinidad y exploración) y después del top semanal no visto. Si no queda nada, última salida: lo mejor visible aunque ya se haya votado, copiado o visto (sin lo propio, lo oculto ni el NSFW no activado), para que Para ti no salga vacío. Cada elemento lleva el grupo de origen (`affinity`, `explore`, `recent` o `fallback`). Un relleno recibe "Porque te gusta" si tiene una etiqueta con afinidad positiva y "Para que descubras algo nuevo" si no.
- **Etiquetas muy negativas.** Un copy-pasta con alguna etiqueta de afinidad efectiva por debajo de −5 se excluye de los tres grupos y del relleno (no solo de exploración). Con −5 justos todavía se muestra, y el decaimiento puede devolverla.
- **Lista de candidatos.** 200 por usuario en la caché (Redis con `CACHE_STORE=redis`), 30 minutos; "Actualizar" y cambiar favoritas la invalidan. Los últimos 500 mostrados se guardan por usuario con caducidad de 7 días y se excluyen al reconstruirla. Lo que se oculta, borra o descarta tras construirla se filtra al leerla, sin regenerarla.
- **Afinidad dentro de la transacción de cada acción**, como el progreso de logros (`AdjustTagAffinity`, un upsert con el decaimiento aplicado antes del delta, sobre todas las etiquetas del copy-pasta). Pesos en `config/affinity.php`: copiar +3 (solo la primera copia por usuario y copy-pasta: la tabla nueva `user_copied_copypastas` decide con `insertOrIgnore`), favorito +3 y quitarlo −3, upvote +1, downvote −2, cambiar o retirar un voto aplica el delta inverso, "no me interesa" −3 y deshacer +3. Solo la carpeta de Favoritos mueve afinidad: `AddToFolder` y `RemoveFromFolder` no hacen nada en cualquier otra.
- **Favoritas aparte (`user_favorite_tags`).** Suman +5 al leer y no decaen; editarlas es añadir y quitar filas. Mínimo 3 etiquetas activas para guardar.
- **`app:rebuild-affinities`** recalcula desde votos actuales, entradas en Favoritos (`copypasta_folder.created_at`), `user_copied_copypastas` (que rellena antes desde `events`, con la fecha de la primera copia) y descartes, plegando cada señal en orden de fecha con el mismo decaimiento. Sustituye lo guardado y repetirlo da lo mismo. Coincide con el cálculo en vivo salvo en historias con retiradas o cambios de voto, donde el vivo conserva el decaimiento entre el voto y su retirada. Un test fija la igualdad con actividad variada y fechas distintas.
- **"Una sola vez" en `/bienvenida`.** Se marca `onboarded_at` al ofrecérsela al usuario (la redirección del registro o de la primera visita al feed), no al guardar: así no vuelve a verla aunque cierre la pestaña. Staff, `/admin`, peticiones que no son GET y sesiones de impersonación no se redirigen ni se marcan. `/bienvenida` es accesible a cualquier miembro; sin parámetros es la bienvenida y con `?modo=editar` el editor de favoritas (mismo selector, otro texto, sin "Saltar"). La columna `users.onboarded_at` no existía aunque el plan la daba por hecha: migración nueva. `UserFactory` la rellena por defecto, con el estado `notOnboarded()`.
- **Registro.** `RegisterResponse` redirige a `/bienvenida`; `RegistrationTest` espera ahora esa ruta. `CreateNewUser` devuelve el usuario refrescado: sin los valores por defecto de las columnas (el tema), la misma petición que lo creaba podía fallar al pintar el layout (el servidor del test de navegador comparte contenedor entre peticiones).
- **Para ti ignora el buscador y las etiquetas** y los oculta. Al abrir la pestaña (o un enlace `?sort=para_ti&q=…`) se limpian de la URL. Los anónimos no la ven, ni pidiéndola por URL.
- **Barra lateral "Tus etiquetas · Editar favoritas"** solo en escritorio; en móvil, el enlace "Editar favoritas" en la cabecera de la pestaña Para ti. En ajustes, una entrada de menú lleva a `/bienvenida?modo=editar`.
- **"No me interesa"** en el menú "⋯" de cualquier tarjeta con sesión (no de la propia ni de una oculta: `CopypastaPolicy::dismiss`). La tarjeta se oculta al momento y el toast ofrece "Deshacer" (el toast admite ahora un botón de acción), que revierte descarte y afinidad. Eventos `dismiss` y `dismiss_undo`, `favorite_tags_update` (con las etiquetas o `skipped`). `EventContext` acepta `group`: copiar, votar y guardar desde Para ti llevan `source=para_ti`, la posición y el grupo.
- **Copy-pasta del día.** `featured:pick` a las 00:00 Europe/Madrid elige el mejor de las últimas 48 horas (visible, sin NSFW, nunca destacado) y, si no hay, de 7 días; si tampoco, no hay del día. Lanzarlo dos veces el mismo día no cambia nada. Se destaca arriba de la home (no en las páginas alias, ni al buscar o filtrar) para todos, con el id en caché hasta medianoche; el copy-pasta se lee cada vez. Si el elegido se oculta, se borra o se marca NSFW, `ConcealCopypasta`, `DeleteCopypasta` y `MarkCopypastaNsfw` invalidan la caché y la home deja de mostrarlo; no se elige otro hasta la medianoche siguiente o hasta que el staff lo sustituya. `/admin` → "Copy-pasta del día" (`FeaturedCopypastaPage`) muestra el actual y lo sustituye (`ReplaceFeaturedCopypasta`): el sustituido sale de `featured_copypastas` y vuelve a ser elegible; no se puede elegir uno ya destacado otro día, oculto ni NSFW. El cambio queda en `moderation_actions` (`replace_featured`), algo que no pedía el plan.
- **Presupuesto de consultas de la home.** `FeedQueryCountTest`: invitado, 5 como máximo (con el elegido del día en caché, como en estado estable); miembro, 7 (la pestaña por defecto y el panel de favoritas suman 2).
- **Bug de la Fase 18 corregido:** `lang/es/settings.php` tenía dos claves `nav.title` (la del título del logro pisaba "Ajustes", y la cabecera de ajustes decía "Título"). El enlace al selector de título pasa a `nav.user_title`.
- **Tests de navegador existentes ajustados.** `UserAreaFlowsTest` y `CopypastaFlowsTest` ya no usan pausas fijas: abren las páginas con `visitInteractive()` (espera a que Alpine y Livewire hayan enlazado todos los componentes) y esperan estados concretos (`aria-pressed` de la etiqueta antes de publicar, el foco dentro del menú antes de pulsar Enter). Los `playwright run-server` ya no quedan huérfanos: Pest Browser lo lanza con `sh -c` y al terminar solo mata el `sh`, así que `tests/Pest.php` recuerda los servidores de su propio proceso y los termina al cerrar.
- **`eventually()` depende de una clase `@internal` del plugin.** El helper de `tests/Pest.php` envuelve `Pest\Browser\Execution::waitForExpectation()`. Al actualizar `pestphp/pest-plugin-browser`, si los tests de navegador que lo usan (hoy `ForYouFlowsTest`) fallan o `Execution` cambia, revisar en `vendor/pestphp/pest-plugin-browser/src/Execution.php` que el método siga existiendo, reciba un callable y reintente ante `ExpectationFailedException` sin bloquear el bucle de eventos (el servidor de la app corre en el mismo proceso). Si no, adaptar solo el envoltorio.

### Fase 20 — Difusión

- [ ] `share_code` en los enlaces del botón de compartir y atribución de visitas con las reglas antitrampa.
- [ ] Logros de Difusión (Mensajero, Altavoz y Megáfono) sobre la infraestructura de la Fase 18: una métrica nueva de visitas atribuidas por `share_code`, que se mueve dentro de la transacción que cuenta la visita (`AdjustAchievementProgress`) y encola la evaluación; los casos en `Achievement` con sus claves de lang; su consulta en `BackfillAchievements`. Las visitas del propio usuario y de bots no cuentan, y solo cuenta un visitante distinto por día.
- [ ] Prueba técnica de generación de imágenes Open Graph con emojis y acentos; elegir la opción más ligera que pase la prueba.
- [ ] Imágenes Open Graph generadas al publicar o editar, genérica para NSFW.
- [ ] "Compartir como imagen" en el navegador, con confirmación en NSFW.
- [ ] Carpetas públicas con `/col/{public_id}`, descripción y presencia en el perfil; acción del staff para hacerlas privadas.

Aceptación: la imagen de un copy-pasta con emojis se genera sin cuadros vacíos; una visita del propio usuario no cuenta; una carpeta pública nunca muestra copy-pastas ocultos a otros.

### Fase 21 — Plantillas y variantes

- [ ] Casilla "Es plantilla", validación de variables y distintivo en la tarjeta.
- [ ] Modal de copia con un campo por variable y vista previa.
- [ ] Crear variante desde el detalle, "Variante de…" y lista de variantes.
- [ ] Notificación al autor del original (Fase 17).
- [ ] Logros Remezclador, Discípulo aventajado y Plantillero sobre la infraestructura de la Fase 18: métricas nuevas (variantes publicadas, variantes que superan en score al original, copias de plantillas propias), los casos en `Achievement` con sus claves de lang y su consulta en `BackfillAchievements`.

Aceptación: copiar una plantilla devuelve el texto con las variables sustituidas; una plantilla sin variables no se puede publicar; borrar el original deja la variante visible con "Contenido retirado".

### Fase 22 — PWA y pulido

- [ ] Manifest, iconos, color de tema, service worker con página sin conexión.
- [ ] Revisión de estados vacíos, cargas y errores en todas las pantallas nuevas.
- [ ] Lighthouse sobre producción: rendimiento y accesibilidad de 90 o más.

Aceptación: la app se puede instalar en Android y en escritorio; sin conexión aparece la página offline; Lighthouse cumple los umbrales.

### Fase 23 — Unicode avanzado (prioridad baja)

- [ ] Tipo de contenido ASCII art con monoespaciada, sin saltos y con scroll horizontal.
- [ ] Longitud contada en grafemas.
- [ ] Normalización NFC antes de calcular `body_hash`.
- [ ] Batería de tests con emojis compuestos, zalgo, texto de derecha a izquierda y ASCII art.

Aceptación: un ASCII art de 120 columnas se muestra sin romperse; un emoji de familia cuenta como 1 carácter; dos textos visualmente iguales en NFC y NFD se detectan como duplicados.

### Fase 24 — Lanzamiento

Pendiente de la Fase 12.4. El despliegue a producción lo ejecuta el propietario del proyecto: Claude Code prepara los ficheros y los tests, pero no despliega.

- [ ] VPS en la UE con Ubuntu LTS: acceso solo por clave SSH, cortafuegos con 22, 80 y 443 abiertos, actualizaciones de seguridad automáticas.
- [ ] `compose.production.yaml` a partir del de staging.
- [ ] Workflow de despliegue apuntando al VPS con una variable de host de producción.
- [ ] Mailer transaccional con SPF, DKIM y DMARC en el dominio.
- [ ] Backups diarios de Postgres copiados a almacenamiento externo compatible con S3, con prueba de restauración.
- [ ] Ejecutar `app:recalculate-counters` en cada entorno con datos.
- [ ] Ejecutar `app:backfill-achievements` una sola vez en producción, con la app en modo mantenimiento (`php artisan down`): el comando se niega a correr si la app no está en mantenimiento, salvo con `--force`. Sustituye los contadores con borrar e insertar, y con tráfico en vivo podría perder una actualización.
- [ ] Checklist de humo y Lighthouse sobre el VPS.

Aceptación: el despliegue de producción se ejecuta desde CI con los tests de navegador en verde; una restauración de backup probada; checklist de humo completo y Lighthouse con los umbrales de la Fase 22.

## Brief para Claude Design

Este bloque se pega tal cual en Claude Design. El resultado elegido se guarda en `docs/design/` antes de la Fase 14.

**Producto.** Copy-pastas es un foro web en español para descubrir, copiar y compartir copy-pastas: textos virales de internet que la gente pega en chats y redes. La acción principal es copiar. Votar, guardar en carpetas y compartir van detrás.

**Público.** Gente joven y muy de internet, que llega sobre todo desde el móvil a través de enlaces compartidos en WhatsApp, Discord o X.

**Personalidad.** Divertida y con cultura de internet, pero limpia y cuidada. Tiene que parecer un producto, no un foro antiguo ni un panel de administración. El contenido es el protagonista: mucho texto, así que la tipografía y la lectura importan más que la decoración.

**Restricciones técnicas.**

- Se implementa con Tailwind CSS. Entrega los tokens como valores concretos: colores en hex, familias tipográficas, escala de tamaños, radios, sombras y espaciado.
- Fuentes que se puedan autoalojar (por ejemplo, de Google Fonts). Una para interfaz y otra monoespaciada para ASCII art.
- Modo claro y modo oscuro, los dos igual de cuidados.
- Diseño pensado primero para móvil, de unos 380 px, y que escale a escritorio.
- Los mismos tokens se aplicarán al panel de administración, hecho con Filament, así que la paleta tiene que funcionar también en tablas y formularios densos.

**La tarjeta de copy-pasta es la pieza clave.** Lleva título, las primeras 6 líneas del cuerpo, etiquetas de colores, autor con avatar generado y título de logro, score, y acciones: copiar, votar arriba y abajo, guardar, compartir y menú "...". El botón de copiar debe ser el más visible. Variantes que hay que resolver: NSFW difuminado hasta hacer clic, distintivo de "Plantilla" y la explicación "Porque te gusta #etiqueta" en el feed Para ti.

**Pantallas a diseñar**, en móvil y escritorio:

1. Feed: pestañas (Para ti, aleatorio, top semanal, top mensual, top histórico, nuevos), filtros de etiquetas, buscador y copy-pasta del día destacado arriba.
2. Detalle de copy-pasta, con variantes y compartir como imagen.
3. Publicar, con vista previa de la tarjeta en vivo.
4. Perfil público con contadores y logros: conseguidos, pendientes con progreso y secretos.
5. Estadísticas privadas con un gráfico de 30 días.
6. Detalle de carpeta.
7. Desplegable de notificaciones.
8. Bienvenida para elegir etiquetas favoritas.
9. Estados vacíos: feed sin resultados, carpeta vacía, sin notificaciones.

**Qué entregar.** Primero dos o tres direcciones visuales distintas aplicadas al feed y a la tarjeta. Tras elegir una, todas las pantallas en esa dirección y la lista final de tokens.

## Backlog fuera de la V2

Ideas aparcadas, ordenadas por valor estimado.

| Idea | Motivo para aplazarla |
| --- | --- |
| Similares y recomendaciones por embeddings del texto | Necesita datos reales para comparar con el modelo por etiquetas de la Fase 19 |
| Apelación de ocultaciones | Las microempresas no están obligadas por la DSA a un sistema interno; confirmar con el abogado |
| Seguir a usuarios y feed de seguidos | Tiene sentido cuando haya autores con volumen |
| Login social (Google, GitHub, Discord) | Menos fricción en el registro, pero no cambia la experiencia |
| Comentarios | Multiplican la moderación y no aportan al uso principal |
| Subida de avatares | Obliga a moderar imágenes |
| Resúmenes por email | Las notificaciones dentro de la app cubren lo básico |
| API pública de lectura | Para bots de Discord o Telegram, cuando haya demanda |
| Multidioma (catalán, inglés) | Los textos ya están en ficheros de idioma |
