# Auditoría del MVP de Copy-pastas

Oct 4, 2026 · @Pol

El MVP tiene una base sólida, pero no está listo para producción: hay 5 problemas críticos y 6 altos que corregir antes de abrirlo al público. Varios vienen de mi propio plan, no solo de Claude Code. La auditoría se basa en PLAN.md y en el documento de cambios posterior a la Fase 11; no he visto el código, así que los puntos marcados como "verificar" son hipótesis a confirmar en el repo.

## Resumen de hallazgos

21 hallazgos: 5 críticos, 6 altos, 10 medios. Los bajos van aparte, en su propia sección. La columna Estado es para ir marcando el avance.

| ID | Severidad | Hallazgo | Área | Estado |
| --- | --- | --- | --- | --- |
| C1 | Crítico | El repo no tiene remoto: CI nunca se ha ejecutado | Proceso | Pendiente |
| C2 | Crítico | Borrar la cuenta propia borra en cascada y descuadra contadores ajenos | Datos | Pendiente |
| C3 | Crítico | La impersonación permite 2FA, passkeys y borrado de cuenta | Seguridad | Pendiente |
| C4 | Crítico | Un solo usuario puede ocultar cualquier copy-pasta al instante | Moderación | Pendiente |
| C5 | Crítico | Sin mailer real nadie puede verificar email ni publicar; backups en el mismo servidor | Infra | Pendiente |
| A1 | Alto | La contraseña temporal contradice la spec y verifica emails sin comprobarlos | Usuarios | Pendiente |
| A2 | Alto | Staff sin 2FA obligatorio; un admin puede degradar a todos los demás | Seguridad | Pendiente |
| A3 | Alto | Descartar reportes de un auto-ocultado puede dejarlo oculto y fuera de la cola (verificar) | Moderación | Pendiente |
| A4 | Alto | El frontend no tiene tests; varios flujos nunca se han probado en navegador | Calidad | Pendiente |
| A5 | Alto | Editar tras recibir votos o reportes destruye la evidencia | Moderación | Pendiente |
| A6 | Alto | Huecos de cumplimiento: avisos solo con login, sin vía de recurso, legales en borrador | Legal | Pendiente |
| M1 | Medio | Un no verificado puede guardar en carpetas pero no entrar a `/app` a verlas | Producto | Pendiente |
| M2 | Medio | Dos pantallas de ajustes editan email y contraseña | Usuarios | Pendiente |
| M3 | Medio | El orden aleatorio no escala y su semilla vive en sesión, no en la URL | Rendimiento | Pendiente |
| M4 | Medio | Caché, sesiones y rate limits en base de datos | Infra | Pendiente |
| M5 | Medio | Migraciones al arrancar el contenedor: posible carrera entre web, worker y scheduler | Infra | Pendiente |
| M6 | Medio | NSFW: los anónimos confirman +18, los registrados no | Producto | Pendiente |
| M7 | Medio | Autorización fuera de Policies en `MyCopypastaResource` | Código | Pendiente |
| M8 | Medio | La métrica principal (copias y compartidos por visita) no se puede medir | Producto | Pendiente |
| M9 | Medio | Usuarios borrados lógicamente se conservan sin límite de tiempo | Datos | Pendiente |
| M10 | Medio | Los moderadores ven el email de todos los usuarios | Privacidad | Pendiente |

## Críticos

Cinco problemas que bloquean la salida a producción. C2 y C4 nacen de decisiones de mi plan.

### C1 — El repositorio no tiene remoto

`git remote -v` está vacío. Eso significa tres cosas:

- La Fase 0 se marcó como hecha, pero su criterio pedía que todo pasara también en CI. CI no se ha ejecutado nunca.
- El workflow de despliegue nunca ha corrido.
- Todo el trabajo vive en un solo disco.

**Corrección:** subir hoy a un remoto privado y comprobar que el workflow de CI queda en verde antes de tocar nada más.

### C2 — Borrar la cuenta propia rompe datos de otros

El borrado desde ajustes hace `forceDelete`. Mi plan puso `cascadeOnDelete` en casi todas las FK, y la combinación tiene cuatro efectos:

1. **Copy-pastas:** se borran físicamente y desaparecen de las carpetas de otros usuarios sin el placeholder "Contenido retirado". El cascade de `copypasta_folder` se lleva la fila.
2. **Votos:** se borran a nivel de base de datos sin pasar por `CastVote`. `upvotes_count`, `downvotes_count` y `score` de los copy-pastas votados quedan descuadrados para siempre.
3. **Favoritos:** ocurre lo mismo con `favorites_count` al borrarse sus carpetas.
4. **Reportes:** se pierden los reportes que envió, y con ellos parte del historial de moderación.

**Corrección:**

- Sustituir el borrado por una Action `DeleteOwnAccount` que retire votos y favoritos con deltas, anonimice el usuario (username `eliminado-xxxx`, email y contraseña nulos) y conserve sus copy-pastas atribuidos a "usuario eliminado". Decidido así; ver Decisiones tomadas.
- Cambiar las FK de `copypastas.user_id`, `reports.reporter_id` y `votes.user_id` a `restrictOnDelete`, para que ningún borrado físico vuelva a saltarse las Actions.
- Añadir un comando que recalcule los contadores desde las tablas reales y ejecutarlo una vez. Si alguien probó a borrar su cuenta en local o en staging, los datos ya están mal.

### C3 — La impersonación permite tomar la cuenta

Durante la impersonación solo se bloquean email y contraseña. La Fase 9 dejó como pregunta abierta 2FA y passkeys, y el documento de cambios no menciona el borrado de cuenta.

Un admin que registra una passkey mientras actúa como otro usuario conserva acceso a esa cuenta después de terminar la impersonación, sin dejar rastro en el log. Además, si el borrado de cuenta no está bloqueado, puede eliminar la cuenta con `forceDelete` (y todo C2) sin registro.

**Corrección:** un middleware `BlockDuringImpersonation` en todas las rutas de seguridad de cuenta: 2FA, passkeys, borrado, sesiones, email y contraseña. Añadir un test por ruta.

### C4 — Un solo usuario puede ocultar cualquier copy-pasta

Un reporte con motivo "contenido sexual con menores" oculta el copy-pasta al instante. El único requisito es tener el email verificado, y con email desechable eso cuesta un minuto. Con el límite de 10 reportes por hora, una sola cuenta oculta 240 copy-pastas al día. Con 5 cuentas se oculta cualquier cosa por la vía normal.

Es un vector de censura y de acoso a autores concretos, y fue diseño mío.

**Corrección:**

- Exigir una antigüedad mínima de cuenta para reportar, por ejemplo 72 horas.
- Contar los reportes ponderados por la fiabilidad del reportador: proporción de reportes aceptados frente a rechazados.
- El motivo "menores" pasa a prioridad máxima en la cola con alerta inmediata, y solo auto-oculta si lo reporta un usuario de confianza (ver Decisiones tomadas).
- Si se rechazan 3 reportes de un usuario en 30 días, pierde el derecho a reportar durante un tiempo.

### C5 — Bloqueantes de despliegue

- **Sin mailer real** nadie puede verificar su email, así que nadie puede publicar ni reportar. Ni avisos de ocultación, ni alertas de menores, ni recuperación de contraseña.
- **Backups en un volumen del mismo servidor**: no son backups. Si se pierde el host, se pierden ambos.
- Ninguno de los dos es opcional ni algo que delegar sin fecha. Son requisitos de la primera versión pública.

## Altos

Seis problemas que no impiden arrancar, pero que conviene cerrar antes de tener usuarios reales.

### A1 — La contraseña temporal contradice la especificación

La spec dice que el admin nunca ve ni fija contraseñas. La creación de usuarios genera una contraseña y se la muestra al admin, que se la pasa al usuario por otro canal. Hay tres problemas:

- **El admin conoce la contraseña.** Puede entrar como ese usuario sin pasar por la impersonación, que es el único camino que deja rastro en el log.
- **El email queda verificado sin comprobarlo.** Si el admin escribe mal el email, la cuenta queda atada a la dirección de un tercero, y la recuperación de contraseña le llegará a él.
- **Hay más piezas de las necesarias.** `must_change_password`, el middleware, el controlador, la vista y el hook del modelo existen solo para resolver un problema que una invitación no tiene.

**Corrección:** crear el usuario sin contraseña y enviarle una invitación con enlace firmado y caducidad de 72 horas para fijarla. Al usar el enlace, el email queda verificado porque demuestra que la dirección es suya. Eliminar todo el flujo de contraseña temporal.

### A2 — Las cuentas de staff son el punto débil

- **2FA no obligatorio.** El starter kit ya trae 2FA y passkeys, pero no se exige al staff. Una cuenta de admin puede impersonar, banear, cambiar roles y crear admins. Es la cuenta más valiosa de la plataforma y la única protegida solo por contraseña.
- **Un admin puede degradar a todos los demás.** La Fase 9 permite a un admin banear o cambiar el rol de otros admins. Una cuenta comprometida puede expulsar al resto y quedarse sola.

**Corrección:**

- Exigir 2FA para entrar en `/admin`; quien no lo tenga activo, se redirige a configurarlo.
- Proteger el último admin: ningún cambio puede dejar la plataforma con cero admins activos.
- Solo tú (un flag `is_owner` o similar) puedes promover o degradar admins.

### A3 — Auto-ocultados que se quedan huérfanos (verificar)

La auto-ocultación deja los reportes pendientes para que el copy-pasta siga en la cola. "Descartar" marca los reportes como rechazados, pero según el plan no restaura nada.

Si un moderador descarta los reportes de un copy-pasta auto-ocultado sin restaurarlo a mano, el copy-pasta queda oculto con motivo "Pendiente de revisión" y sin reportes pendientes. Desaparece de la cola y no vuelve a aparecer.

**Corrección:** comprobar el comportamiento con un test. Si se confirma, descartar los reportes de un auto-ocultado lo restaura en la misma Action y lo registra en el log.

### A4 — El frontend no tiene tests

La Fase 10 encontró 4 errores de Alpine que rompían el selector de carpetas, el modal de reporte y las acciones de tarjeta. Los 361 tests seguían en verde, y solo se vieron al abrir el navegador.

Siguen sin probarse en navegador el modal de reporte, la banda de impersonación, las pestañas de usuario, "Quitar de la carpeta" y todo el flujo de gestión de usuarios. Las partes con más JavaScript propio son justo las que no tienen cobertura.

**Corrección:** tests de navegador con el plugin de browser testing de Pest (verificar disponibilidad con la versión instalada) o Dusk. Mínimo 6 flujos en CI: copiar, votar, guardar en carpeta, reportar, ocultar desde la cola e impersonar y volver.

### A5 — Editar después de votos o reportes

El autor puede editar siempre, sin rastro más allá de `edited_at`. Esto permite dos abusos:

- **Cebo y cambio:** publicar algo inocuo, subir al top semanal y editarlo para convertirlo en spam u ofensa con el score ya ganado.
- **Borrado de evidencia:** si alguien reporta un copy-pasta y el autor lo edita antes de que lo revise un moderador, este ve la versión nueva, no la reportada.

**Corrección:**

- Tabla `copypasta_revisions` con título, cuerpo y fecha de cada versión.
- Cada reporte guarda el `revision_id` que vio el reportador, y la cola muestra esa versión con un diff frente a la actual.
- Política decidida: edición libre, con "editado" e historial visible en el detalle.

### A6 — Huecos de cumplimiento legal

Con la información que tengo, la plataforma parece entrar en el Reglamento de Servicios Digitales de la UE (DSA) como servicio de alojamiento. Hay que confirmarlo con un abogado; esto no es asesoramiento legal.

- **Avisos solo con login.** La DSA pide un mecanismo de aviso de contenido ilegal accesible a cualquier persona, no solo a usuarios registrados. Hoy un anónimo no puede reportar nada.
- **Sin vía de recurso.** El email al autor cuando se oculta su copy-pasta debería indicar qué puede hacer si no está de acuerdo. Las apelaciones están en el backlog.
- **Textos legales en borrador.** Privacidad, cookies y normas están marcados como pendientes de revisión.

**Corrección:** un formulario de aviso para anónimos con email de contacto, separado del reporte de usuario, que entra en la misma cola. Plantilla de email de ocultación con motivo y vía de recurso. Revisión legal antes de abrir.

## Medios

Diez problemas que no comprometen datos ni seguridad, pero sí escalabilidad, coherencia o mantenimiento.

**M1 — Verificación incoherente.** La spec permite a los no verificados votar y guardar. La Fase 5 cerró `/app` a los no verificados, así que pueden añadir a carpetas desde la web pero no ver sus carpetas ni corregir su email en el perfil de Filament. *Corrección:* abrir `/app` a cualquier usuario no baneado y restringir con Policies solo los recursos que lo exigen, como publicar.

**M2 — Dos pantallas de ajustes.** `/settings` del starter kit y el perfil de `/app` editan email y contraseña por caminos distintos. Cada regla nueva, como el bloqueo durante la impersonación, hay que aplicarla dos veces; C3 muestra el riesgo. *Corrección:* quedarse con una sola. Recomiendo `/settings`, porque ya trae 2FA, passkeys y borrado, y llevar ahí la preferencia NSFW.

**M3 — El orden aleatorio no escala.** `ORDER BY md5(id || seed)` calcula un hash por fila y ordena la tabla entera en cada página. Con 300 filas tarda menos de 1 ms; con 500.000 no. El `EXPLAIN ANALYZE` de la Fase 4 se hizo sobre 300 filas y no dice nada sobre producción. Además, la semilla vive en sesión, lo que contradice la regla de que todo el estado del feed vaya en la URL, y obliga a crear una sesión por cada visitante anónimo y cada bot. *Corrección:* columna `random_key` indexada y un desplazamiento derivado de una semilla que va en la query string. Repetir los `EXPLAIN` con un seeder de al menos 200.000 copy-pastas.

**M4 — Todo en la base de datos.** Caché, sesiones, colas y rate limits usan el driver `database`. Cada copia de un copy-pasta escribe una fila de caché para el límite por visitante y otra para el throttle. Cada visita anónima escribe una sesión. Bajo tráfico real, Postgres hará de almacén clave-valor. *Corrección:* Redis para caché, sesiones y rate limits antes de abrir. Las colas pueden seguir en base de datos.

**M5 — Migraciones al arrancar el contenedor.** Si web, worker y scheduler comparten imagen y entrypoint, los tres lanzan `migrate` a la vez en cada despliegue. *Corrección:* un paso de migración único en el workflow antes de reiniciar los servicios, o como mínimo `migrate --isolated` solo en el contenedor web.

**M6 — NSFW desigual.** Un anónimo confirma ser mayor de 18 años para ver NSFW; un registrado activa `show_nsfw` sin confirmar nada, y el registro no pregunta la edad. *Corrección:* pedir la misma confirmación al activar la preferencia y guardar la fecha en que se aceptó.

**M7 — Autorización fuera de Policies.** `MyCopypastaResource::canViewAny()` comprueba verificación y baneo a mano porque `CopypastaPolicy::viewAny` es solo de staff. Rompe la convención del proyecto. *Corrección:* un método `viewOwn` en la Policy, o una Policy separada para el panel de usuario.

**M8 — La métrica principal no se puede medir.** El plan define el éxito como copias más compartidos por visita. Solo existe `copies_count`: no se registran los compartidos ni las visitas. *Corrección:* tabla de eventos agregados por día (vistas, copias, compartidos por copy-pasta) sin cookies de terceros. Es base para la V2.

**M9 — Usuarios borrados para siempre.** El borrado lógico no tiene caducidad. Los datos personales de un usuario borrado por un admin se guardan indefinidamente, y su username y email quedan reservados. *Corrección:* anonimización automática a los 30 días, con la misma Action de C2, y política de retención en el texto de privacidad.

**M10 — Emails visibles para moderadores.** Los moderadores tienen lectura sobre usuarios, incluido el email. No lo necesitan para moderar contenido. *Corrección:* ocultar el email a quien no sea admin.

## Bajos y limpieza

Deuda técnica menor. Se puede repartir entre las fases de la V2.

| Hallazgo | Por qué importa | Corrección |
| --- | --- | --- |
| Copy-pastas sin etiquetas visibles en el feed | La regla de negocio se adaptó a las factories, que no asignaban etiquetas; la spec exige de 1 a 5 | Asignar etiquetas en la factory y volver a la regla de la spec |
| Helper `actor()` duplicado en 6 sitios | Ya marcado como limpieza desde la Fase 8 | Extraerlo a un único sitio |
| `withoutGlobalScope(SoftDeletingScope::class)` en lugar de `withTrashed()` | El código se adaptó a PHPStan en vez de tipar bien la consulta | Anotar el tipo genérico o una entrada justificada en el baseline |
| Tests con caché `array` y producción con `database` | Ya causó un 500 en la home con `__PHP_Incomplete_Class` | Usar en tests el mismo driver que producción en lo que dependa de serializar |
| `lockForUpdate` en cada voto | Con deltas atómicos (`SET x = x + 1`) el bloqueo sobra y crea contención en copy-pastas virales | Quitar el bloqueo y mantener los deltas |
| La impersonación no caduca | Si la sesión expira sin pulsar "Volver", el fin no queda en el log | Límite de 30 minutos y registro del fin al expirar |
| CSP con `unsafe-eval` | Debilita la protección contra XSS | Probar el modo CSP de Livewire y Alpine solo en la web pública; los paneles de Filament probablemente necesiten mantenerlo |
| Pantallas de Fortify en inglés (verificar) | La Fase 1 aplazó su traducción a la Fase 4 y no consta que se hiciera | Revisar login, recuperación, 2FA y passkeys |
| `app:create-admin` lee `ADMIN_PASSWORD` del entorno | Queda visible con `docker inspect` | Prompt interactivo o invitación por email, como en A1 |
| Lighthouse sin repetir sobre la imagen de producción | Criterio de la Fase 10 sin cerrar | Medir en staging y documentar el resultado |
| El username se puede cambiar sin límite | Romperá los enlaces del perfil público de la V2 | Límite de un cambio cada 30 días y redirección del username antiguo |

## Decisiones de Claude Code que sí mantendría

No todo lo que se desvió del plan está mal. Estas desviaciones mejoran lo que yo había especificado y no deberían revertirse al corregir lo anterior:

- **Tarjetas en Blade + Alpine con endpoints JSON**, en lugar de un componente Livewire por tarjeta. Es lo que permite que el feed haga 4 consultas.
- **`scopeWithViewerState()`** para el voto y el favorito del visitante en subconsultas, sin consultas por tarjeta.
- **`body_hash` en un mutator** en lugar de un evento, que no depende de que los eventos estén activos.
- **Límites dentro de las Actions** y no solo en las rutas, para que se cumplan igual desde Filament y desde la web.
- **Login único de Fortify** para los dos paneles.
- **`id` como último desempate** en todos los órdenes, que evita repetidos al paginar.
- **Detección de problemas fuera de los tests:** `robots.txt` que sombreaba la ruta dinámica, la caché de etiquetas con clases incompletas y los errores de Alpine. Indica que Claude Code probó la imagen real y no se fió solo de los tests.

## Fase 12 — Correcciones antes de producción

Cubre todos los críticos y altos, y los medios que afectan a datos o a infraestructura. Va en cuatro bloques para hacer un commit por bloque. Ya incorpora las decisiones de la sección siguiente.

### 12.1 — Proceso y datos

- [ ] Subir el repo a un remoto privado y dejar CI en verde (C1).
- [ ] Action `DeleteOwnAccount`: retira votos y favoritos con deltas, anonimiza el usuario y mantiene sus copy-pastas visibles como "usuario eliminado" (C2).
- [ ] FK de `copypastas.user_id`, `votes.user_id` y `reports.reporter_id` a `restrictOnDelete` (C2).
- [ ] Comando `app:recalculate-counters` que recalcula todos los contadores desde las tablas reales; ejecutarlo una vez (C2).
- [ ] Anonimización programada de usuarios con borrado lógico de más de 30 días (M9).

Aceptación: tras borrar una cuenta con votos, favoritos, copy-pastas y reportes, los contadores de los copy-pastas afectados coinciden con un recuento real y los copy-pastas de otros siguen en sus carpetas.

### 12.2 — Seguridad de cuentas

- [ ] Middleware `BlockDuringImpersonation` en 2FA, passkeys, borrado, sesiones, email y contraseña; un test por ruta (C3).
- [ ] Impersonación con caducidad de 30 minutos y registro del fin al expirar.
- [ ] 2FA obligatorio para acceder a `/admin` (A2).
- [ ] Protección del último admin y flag de propietario para promover o degradar admins (A2).
- [ ] Sustituir la contraseña temporal por invitación con enlace firmado de 72 horas que verifica el email; eliminar `must_change_password` y sus piezas (A1).
- [ ] Unificar ajustes en `/settings` y mover allí la preferencia NSFW con confirmación +18 (M2, M6).
- [ ] Abrir `/app` a usuarios no verificados y restringir con Policies; método `viewOwn` en lugar de la comprobación manual (M1, M7).
- [ ] Ocultar el email de los usuarios a los moderadores (M10).

Aceptación: un admin impersonando no puede cambiar ningún factor de autenticación ni borrar la cuenta; ningún flujo deja la plataforma sin admins; un admin no puede conocer la contraseña de otro usuario.

### 12.3 — Moderación

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

### 12.4 — Infraestructura y calidad

- [ ] Redis para caché, sesiones y rate limits, como un servicio más del compose (M4).
- [ ] Semilla del orden aleatorio en la query string y columna `random_key` indexada; `EXPLAIN` con 200.000 copy-pastas (M3).
- [ ] Migraciones como paso único del despliegue, antes de reiniciar servicios, no en el entrypoint de todos los contenedores (M5).
- [ ] Tests de navegador de 6 flujos en CI: copiar, votar, guardar en carpeta, reportar, ocultar e impersonar (A4).
- [ ] VPS en la UE con Ubuntu LTS: acceso solo por clave SSH, cortafuegos con 22, 80 y 443 abiertos, actualizaciones de seguridad automáticas.
- [ ] `compose.production.yaml` a partir del de staging: app, worker, scheduler, Postgres y Redis, con HTTPS automático vía `SERVER_NAME` y el dominio.
- [ ] Workflow de despliegue apuntando al VPS con una variable de host de producción.
- [ ] Mailer transaccional con SPF, DKIM y DMARC en el dominio (C5).
- [ ] Backups diarios de Postgres copiados a un almacenamiento externo compatible con S3, con prueba de restauración (C5).
- [ ] Checklist de humo y Lighthouse sobre el VPS.

Aceptación: el VPS desplegado desde CI con los 6 tests de navegador en verde; restauración de un backup probada; el feed aleatorio con 200.000 filas responde en menos de 50 ms.

## Decisiones tomadas

Respondidas el 4 de octubre de 2026. La Fase 12 ya las incorpora.

1. **Cuenta borrada:** sus copy-pastas siguen visibles atribuidos a "usuario eliminado".
2. **Creación de usuarios por admin:** invitación por email con enlace firmado, que fija la contraseña y verifica el email.
3. **Edición tras publicar:** libre, con historial de versiones visible en el detalle.
4. **Moderación de confianza:** nuevo rol "usuario de confianza", asignado por admins. Sus reportes pesan más y son los únicos, aparte del staff, que auto-ocultan por el motivo "menores".
5. **Hosting:** un VPS en la UE con Docker Compose, reutilizando la imagen y el workflow de la Fase 11. Sin entorno de staging separado al principio.

## Verificación contra el código (4 oct 2026)

Cada hallazgo se ha contrastado con el código real. Rutas relativas a la raíz del repo; los números son líneas.

| ID | Veredicto | Evidencia |
| --- | --- | --- |
| C1 | Confirmado | `git remote -v` vacío. Los workflows existen en `.github/workflows/`, pero nunca han corrido en remoto. |
| C2 | Confirmado | `resources/views/pages/settings/⚡delete-user-modal.blade.php:22` hace `forceDelete()`. FK `cascadeOnDelete` en `copypastas.user_id` (migración `2026_10_04_150001`:17), `votes.user_id` (`150003`:17) y `reports.reporter_id` (`150006`:18). Los favoritos se pierden con la carpeta sin decrementar `favorites_count` (solo lo tocan `app/Actions/ToggleFavorite.php:35` y `AddToFolder.php:37`). |
| C3 | Parcial | Ver la sección "Corrección de C3" abajo. |
| C4 | Confirmado | `app/Actions/ReportCopypasta.php:89-91` oculta de inmediato si el motivo es menores. Línea 46: límite de 10 reportes por hora. `app/Policies/ReportPolicy.php:16-22` solo exige email verificado. Umbral de 5 en `app/Models/Report.php:32`. No hay antigüedad mínima ni reputación. |
| C5 | Confirmado | `docker/backup.sh:8-9` escribe en `/backups`, en el mismo host. El repo no configura ningún proveedor de correo (`.env.example:45` es solo un comentario). |
| A1 | Confirmado | `app/Actions/CreateUserByAdmin.php:29,37-39` genera la contraseña, la devuelve al admin y fija `email_verified_at = now()`. |
| A2 | Confirmado | `app/Providers/Filament/AdminPanelProvider.php:45-60` no exige 2FA. `app/Policies/UserPolicy.php:37-50` permite a un admin banear o cambiar el rol de cualquier otro admin. `app/Actions/ChangeUserRole.php` no tiene guarda de último admin ni flag de propietario. |
| A3 | Confirmado, con matiz | `app/Filament/Admin/Pages/ModerationQueue.php:72` filtra `whereHas('pendingReports')`. Descartar solo rechaza (`app/Actions/DismissCopypastaReports.php:29-33`) y `hidden_at` se queda. El copy-pasta sale de la cola y no vuelve. Sí se puede restaurar desde el listado de copy-pastas de `/admin` (acción "Restaurar" en `CopypastaModerationActions.php:42-52`), pero nadie sabe que tiene que ir a buscarlo. Falta el test del caso auto-ocultado en `tests/Feature/Actions/DismissCopypastaReportsTest.php`. |
| A4 | Confirmado | No hay `tests/Browser`. `composer.json` no incluye pest-plugin-browser ni dusk. |
| A5 | Confirmado | `app/Actions/UpdateCopypasta.php:32` solo fija `edited_at`. No hay tabla de revisiones. |
| A6 | Confirmado | La ruta de reporte tiene `middleware('auth')` en `routes/web.php`. `resources/views/emails/copypasta-hidden.blade.php` no menciona vía de recurso. `resources/views/public/normas.blade.php:4` y `privacidad.blade.php:4` muestran el aviso de borrador. |
| M1 | Confirmado | `app/Models/User.php:188-197`: `/app` exige email verificado. `app/Policies/FolderPolicy.php:36-40`: añadir a carpeta no lo exige. |
| M2 | Confirmado | `/settings/profile`, `/settings/security` y `app/Filament/App/Pages/Auth/EditProfile.php` editan email y contraseña por caminos distintos. |
| M3 | Confirmado | `app/Models/Copypasta.php:252` usa `md5(id || ?)`. La semilla vive en sesión (`app/Livewire/Feed.php:175-184`). |
| M4 | Confirmado | Caché, sesiones y colas en `database` (`config/cache.php:18`, `config/queue.php:16`, `.env.example:30`). |
| M5 | Falso en el compose actual | `docker/entrypoint.sh:19` migra solo con `RUN_MIGRATIONS=true`. `compose.staging.yaml:9` lo pone solo en `app`; worker (`:21`) y scheduler (`:30`) no lo tienen. El riesgo descrito no ocurre hoy. Tarea retirada de la Fase 12. |
| M6 | Confirmado | `NsfwConfirmationController` guarda la cookie de consentimiento de los anónimos. `EditProfile.php:73-77` activa `show_nsfw` sin confirmar. El registro (`CreateNewUser`) no pregunta la edad. |
| M7 | Confirmado | `app/Filament/App/Resources/MyCopypastas/MyCopypastaResource.php:44-48` comprueba verificación y baneo a mano. `app/Policies/CopypastaPolicy.php:12-15`: `viewAny` es solo staff. |
| M8 | Confirmado | Solo existe `copies_count` (`app/Actions/RecordCopypastaCopy.php:24`). No hay tabla de eventos ni de visitas. |
| M9 | Confirmado | `routes/console.php` solo poda colas. `username` (unique, migración `2026_10_04_144742`:27) y `email` (unique) siguen ocupados tras el borrado lógico. |
| M10 | Confirmado | `app/Filament/Admin/Resources/Users/Tables/UsersTable.php:29-31` muestra el email. `app/Policies/UserPolicy.php:11-14`: `viewAny` es staff, así que lo ven los moderadores. |

**Bajos**

- Etiquetas en el feed: parcial. `database/seeders/CopypastaSeeder.php:44-45` asigna etiquetas; la factory no. La tarea queda como está: asignar etiquetas en la factory y volver a la regla de la spec.
- `actor()` duplicado: confirmado, pero está en 7 sitios, no en 6.
- `withoutGlobalScope(SoftDeletingScope)`: confirmado.
- Tests con caché y sesión `array`: confirmado (`phpunit.xml:25,30`).
- `lockForUpdate` en cada voto: confirmado (`app/Actions/CastVote.php:27,32`). Los contadores se calculan leyendo y sumando, no con deltas atómicos.
- Impersonación sin caducidad: confirmado.
- CSP con `unsafe-eval`: confirmado (`app/Http/Middleware/SecurityHeaders.php:18`).
- Pantallas de Fortify en inglés: confirmado. Hay 50 cadenas `__()` en `resources/views/pages/auth/*.blade.php` sin traducción en `lang/es`, y no existe `lang/es.json`. El Dockerfile fija `APP_LOCALE=es` (`Dockerfile:29`).
- `app:create-admin` lee `ADMIN_PASSWORD` del entorno: confirmado (`app/Console/Commands/CreateAdmin.php:30`).
- Lighthouse sin repetir: no verificable en código.
- Username sin límite de cambios: confirmado (`app/Concerns/ProfileValidationRules.php` no tiene límite).

### Corrección de C3

Durante la impersonación, estas rutas de seguridad de cuenta:

- **Contraseña** (`/settings/security`, `updatePassword`): bloqueada en `resources/views/pages/settings/⚡security.blade.php:71`.
- **Email** (`/settings/profile`, y `/app`): bloqueado si cambia, en `⚡profile.blade.php:36` y `EditProfile.php:45`.
- **2FA** (activar, confirmar, desactivar, códigos de recuperación): abierta en código, pero protegida por `password.confirm` (`vendor/laravel/fortify/routes/routes.php:147-151`, `config/fortify.php:167-171`). Se puede si el admin confirmó su contraseña en la misma sesión en las últimas 3 horas, porque `ImpersonateManager::take()` (`vendor/lab404/laravel-impersonate/src/Services/ImpersonateManager.php:110-131`) no limpia `auth.password_confirmed_at`.
- **Passkeys** (registrar, borrar): igual que 2FA, con `password.confirm` (`vendor/laravel/passkeys/routes/routes.php:34-43`).
- **Borrado de cuenta**: no tiene bloqueo en código, pero pide `current_password`, que valida contra el usuario impersonado. El admin no puede completarlo sin su contraseña. Sí queda sin registro, porque `forceDelete()` no pasa por `DeleteUser`.
- **Sesiones**: la app no tiene gestión de sesiones. No aplica.

Conclusión: la auditoría dice que el admin puede eliminar la cuenta; no es alcanzable sin la contraseña del usuario. Las rutas de 2FA y passkeys sí son alcanzables en una ventana de confirmación previa. La corrección se mantiene, con el bloqueo por diseño en lugar de por efecto secundario (tarea 12.2a).

### Hallazgos nuevos

- **N1 (Alto, 12.2a):** `auth.password_confirmed_at` no se limpia al impersonar, así que una confirmación previa del admin sirve para 2FA y passkeys del usuario impersonado.
