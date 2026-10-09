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
- [ ] Tema propio de Filament para `/admin` con los mismos tokens.
- [x] Página `/_componentes`, solo en local, con todos los componentes en los dos modos.
- [x] Componentes propios: botón, input, textarea, select, checkbox, switch, campo de código de 6 dígitos (2FA), modal, desplegable, pestañas, toast, chip de etiqueta, avatar, skeleton y estado vacío. Todos aparecen en `/_componentes`.
- [x] Alpine con el plugin oficial `@alpinejs/focus` para modales y desplegables.
- [x] Migrar todas las vistas que usan `<flux:*>` (login, registro, recuperación, ajustes, 2FA, passkeys, menús) a los componentes propios.
- [x] Eliminar `livewire/flux` de `composer.json` y sus assets (estilos en `resources/css/app.css` y vistas en `resources/views/flux`).
- [ ] Tests de navegador: el staff entra con un código TOTP real en el campo nuevo, no con el código de recuperación.

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

### Fase 15 — Área de usuario fuera de Filament

- [ ] Páginas Livewire: mis copy-pastas, publicar con vista previa, editar, carpetas y detalle de carpeta.
- [ ] Reutilizar las Actions y Policies existentes; ninguna lógica de negocio en los componentes.
- [ ] Eliminar el panel `/app` y redirigir sus URLs con 301.
- [ ] Menú de usuario con todas las secciones.
- [ ] Tests de navegador: publicar, editar, crear carpeta, añadir y quitar de una carpeta, copiar desde una carpeta.

Aceptación: `/app` responde 301; todo lo que un usuario hacía en el MVP funciona sin Filament; el detalle de carpeta muestra tarjetas con copiar y quitar.

### Fase 16 — Perfil público y estadísticas

- [ ] Primera tarea: votos netos (decisión 8). `previous` y `next` en el `context` de los eventos de voto, y agregación por deltas. Sin producción no hace falta recalcular eventos antiguos: se resiembra.
- [ ] Gráfico de 30 días como componente Blade que genera SVG en el servidor, sin librería de gráficos. Accesible: título y descripción en el SVG, y los mismos datos en una tabla oculta para lectores de pantalla.
- [ ] Página `/u/{username}` con contadores públicos, copy-pastas y carpetas públicas (estas últimas se activan en la Fase 20).
- [ ] Enlaces al perfil desde el autor de cada tarjeta y del detalle; "usuario eliminado" sin enlace.
- [ ] Página `/estadisticas` con totales, gráfico de 30 días, mejor copy-pasta, el que más crece, etiquetas fuertes y fiabilidad de reportes.
- [ ] Consultas de estadísticas solo sobre `copypasta_daily_stats` y contadores; nunca sobre `events` en bruto.

Aceptación: los totales coinciden con un recuento directo en un test con datos sembrados; la página de estadísticas hace 6 consultas como máximo; el perfil de una cuenta borrada responde 404; el gráfico tiene título, descripción y tabla equivalente.

### Fase 17 — Notificaciones

- [ ] Tabla de notificaciones de Laravel y una clase por tipo.
- [ ] Campana con contador en la cabecera, actualizada al navegar y cada 60 segundos.
- [ ] Página `/notificaciones` con marcar una o todas como leídas.
- [ ] Hitos de copias y upvotes (10, 100, 1.000) detectados al actualizar contadores.
- [ ] Agrupación de hitos del mismo copy-pasta en una hora.
- [ ] Preferencias por tipo en ajustes; las de moderación son obligatorias.

Aceptación: cada tipo se genera en su caso y solo una vez; un tipo desactivado no se genera; la agrupación funciona con dos hitos seguidos.

### Fase 18 — Logros y títulos

- [ ] Definiciones en código con el catálogo de la especificación y tabla `user_achievements`.
- [ ] Evaluación en cola a partir de los eventos de dominio, idempotente, con las reglas antitrampa.
- [ ] Notificación al desbloquear (Fase 17).
- [ ] Sección de logros en el perfil: conseguidos, pendientes con progreso, secretos como "???" y porcentaje de usuarios.
- [ ] Selector de título en ajustes y título visible junto al nombre.
- [ ] Revocación por admin con motivo y log.
- [ ] Comando `app:backfill-achievements`.

Aceptación: un test por familia, incluido uno que demuestre que los upvotes de cuentas de menos de 72 horas no cuentan; ejecutar el backfill dos veces no duplica nada; un logro revocado no muestra su título.

### Fase 19 — Descubrimiento

- [ ] Tabla `user_tag_affinities` actualizada desde los eventos, con el decaimiento de 30 días.
- [ ] Pantalla `/bienvenida` al registrarse y en la primera visita de usuarios existentes, saltable.
- [ ] Pestaña "Para ti" con la mezcla 70/20/10, exclusiones, explicación por tarjeta y lista de candidatos cacheada.
- [ ] "No me interesa" en el menú de la tarjeta, con `copypasta_dismissals`.
- [ ] Copy-pasta del día: job a las 00:00, destacado en la home y sustitución desde `/admin`.

Aceptación: con afinidades sembradas, al menos el 60 % de una página de Para ti pertenece a las etiquetas afines y al menos el 10 % a otras; nada descartado ni votado aparece; dos páginas seguidas no repiten elementos; un usuario sin señales ve el top semanal hasta elegir etiquetas.

### Fase 20 — Difusión

- [ ] `share_code` en los enlaces del botón de compartir y atribución de visitas con las reglas antitrampa.
- [ ] Prueba técnica de generación de imágenes Open Graph con emojis y acentos; elegir la opción más ligera que pase la prueba.
- [ ] Imágenes Open Graph generadas al publicar o editar, genérica para NSFW.
- [ ] "Compartir como imagen" en el navegador, con confirmación en NSFW.
- [ ] Carpetas públicas con `/col/{public_id}`, descripción y presencia en el perfil; acción del staff para hacerlas privadas.

Aceptación: la imagen de un copy-pasta con emojis se genera sin cuadros vacíos; una visita del propio usuario no cuenta; una carpeta pública nunca muestra copy-pastas ocultos a otros.

### Fase 21 — Plantillas y variantes

- [ ] Casilla "Es plantilla", validación de variables y distintivo en la tarjeta.
- [ ] Modal de copia con un campo por variable y vista previa.
- [ ] Crear variante desde el detalle, "Variante de…" y lista de variantes.
- [ ] Notificación al autor del original (Fase 17) y logros de variantes y plantillas (Fase 18).

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
