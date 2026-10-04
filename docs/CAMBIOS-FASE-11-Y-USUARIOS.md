# Cambios: cierre de la Fase 11 y gestión de usuarios

Este documento recoge todo lo hecho después de la Fase 10 (commit `4dbd2d0`): el cierre parcial de la Fase 11, dos refactorizaciones de rendimiento y seguridad, y la ampliación de la gestión de usuarios del panel de admin. Complementa a [PLAN.md](PLAN.md), que sigue siendo la referencia de la especificación.

## 1. Commits

| Commit | Qué contiene |
| --- | --- |
| `be55261` | Fase 11 (ya existente antes de esta sesión): imagen de producción, staging con worker, scheduler, backups y `app:create-admin`. |
| `f289066` | Validación de formularios en español. |
| `8c472f1` | Límite de cambios en carpetas por usuario. |
| `bbbfc1a` | Acciones de moderación compartidas entre la tabla de copy-pastas y la cola. |
| `bc87b15` | Gestión de usuarios: verificación de email, creación con contraseña temporal y borrado lógico. Va etiquetado como `fase9` porque amplía esa fase. |

Todos los commits pasaron `sail artisan test`, `vendor/bin/phpstan` y `vendor/bin/pint --test`. Tras el último commit: **361 tests, 1009 aserciones, phpstan sin errores, pint limpio**.

## 2. Estado de la Fase 11

La Fase 11 **no está cerrada**. Su criterio de aceptación pide un despliegue a staging desde CI y un checklist de humo, y ninguno de los dos se puede completar sin infraestructura.

Hecho:

- Dockerfile de producción en etapas, con `docker-compose` de staging (`compose.staging.yaml`).
- Worker de colas y scheduler (`schedule:run`).
- Backups diarios de Postgres con 14 días de retención en un volumen local (`docker/backup.sh`).
- Comando `app:create-admin` para crear el primer admin.
- Migraciones automáticas al arrancar la imagen, `/up`, `robots.txt` dinámico, cabeceras de seguridad y compresión.
- Workflow de despliegue en [.github/workflows/deploy-staging.yml](../.github/workflows/deploy-staging.yml). Solo despliega por SSH si existe la variable `STAGING_HOST`.

Pendiente, y por qué:

- **Despliegue a staging desde CI y checklist de humo** (registro, publicar, votar, reportar, ocultar). Hace falta un host o plataforma de staging y un remoto de git. **El repositorio no tiene ningún remoto configurado** (`git remote -v` vacío), así que el workflow no se ha ejecutado nunca.
- **Mailer transaccional real y SPF/DKIM.** El plan lo deja a cargo del operador.
- **Copia de backups fuera del servidor y logs a un agregador.** También a cargo del operador.
- **Revisión legal** de privacidad, cookies y normas. Están escritas y marcadas como borrador en la app.
- **Auditoría de Lighthouse.** La Fase 10 dejó rendimiento en 64 con el servidor de Sail, que no comprime. La compresión ya está en la imagen de producción, pero **no se ha repetido la auditoría**.

## 3. Validación en español (`f289066`)

- [lang/es/validation.php](../lang/es/validation.php): reglas de Laravel traducidas y nombres de campo legibles (`name` → "nombre", `password` → "contraseña", etc.). Está escrito a mano porque **no se añadió `laravel-lang`**, para no introducir una dependencia sin aprobación.
- Test: [tests/Feature/Validation/SpanishValidationMessagesTest.php](../tests/Feature/Validation/SpanishValidationMessagesTest.php). Comprueba con un registro real que los errores salen en español. Se verificó que falla si se quita el fichero.

## 4. Límite de cambios en carpetas (`8c472f1`)

Las acciones de carpeta de Filament no tenían límite propio, y la Fase 7 lo había dejado pendiente para la Fase 10.

- Trait [app/Concerns/LimitsFolderChanges.php](../app/Concerns/LimitsFolderChanges.php): **60 cambios por minuto y por usuario**, con `RateLimiter::attempt`, igual que `PublishCopypasta`.
- Lo usan `CreateFolder`, `RenameFolder`, `DeleteFolder`, `AddToFolder` y `RemoveFromFolder`. Así el límite se aplica igual desde Filament y desde los endpoints web de carpetas.
- `SyncCopypastaFolders` no tiene límite propio: delega en `AddToFolder` y `RemoveFromFolder`, así que **cuenta un cambio por cada carpeta que realmente modifica**. Es una decisión; si se prefiere contar una sola vez por sincronización, se cambia.
- Los favoritos (`ToggleFavorite`) no entran aquí: ya tienen `throttle:120,1` en la ruta.
- Mensaje en `lang/es/app.php` (`errors.folder_rate_limited`).
- Test: [tests/Feature/Actions/FolderChangesLimitTest.php](../tests/Feature/Actions/FolderChangesLimitTest.php). Superado el límite, cada acción responde con 429, y el límite es por usuario.

## 5. Acciones de moderación compartidas (`bbbfc1a`)

Ocultar, restaurar y marcar NSFW estaban definidas dos veces: en la tabla de copy-pastas y en la cola de moderación.

- Nueva clase [CopypastaModerationActions.php](../app/Filament/Admin/Resources/Copypastas/Tables/CopypastaModerationActions.php), junto a `CopypastasTable`. Define las tres acciones y el helper `actor()`.
- `CopypastasTable` y `ModerationQueue` las usan. La cola conserva solo "descartar reportes", que no existe en la tabla.
- Sin cambios de comportamiento: los tests existentes de la cola y de la tabla pasan sin tocar sus aserciones.

## 6. Gestión de usuarios (`bc87b15`)

Qué había antes: listado con búsqueda y filtros de rol y baneo, ficha con pestañas, edición de username y email, banear, desbanear, cambiar rol, reset de contraseña e impersonación. Esta sección cubre lo que se añadió.

### 6.1 Modelo de datos

Migración nueva [database/migrations/2026_10_04_202731_add_user_management_columns_to_users_table.php](../database/migrations/2026_10_04_202731_add_user_management_columns_to_users_table.php):

- `deleted_at` (borrado lógico).
- `must_change_password` (boolean, por defecto `false`).

El tipo de la columna `moderation_actions.action` es un string de 30 caracteres, así que los tipos de log nuevos no necesitan migración.

En [app/Models/User.php](../app/Models/User.php):

- Usa `SoftDeletes`.
- Cast `must_change_password` a boolean.
- Un hook `saving` limpia `must_change_password` cuando cambia la contraseña, **salvo que el mismo guardado fije el flag** (como hace la creación desde admin). Así cubre de una vez la pantalla de ajustes, el reset por email y el perfil de Filament, sin tocar cada flujo.

Relaciones que siguen mostrando a un usuario borrado, con `withTrashed()`:

- `Copypasta::user` y `Copypasta::hiddenBy`.
- `Report::reporter` y `Report::resolvedBy`.
- `ModerationAction::actor`.

### 6.2 Enum de tipos de log

[app/Enums/ModerationActionType.php](../app/Enums/ModerationActionType.php) añade:

| Valor | Cuándo |
| --- | --- |
| `user_created` | Un admin crea un usuario. Guarda el rol en `meta`. |
| `user_deleted` | Borrado lógico. |
| `user_restored` | Restauración. |
| `email_verified` | Verificación marcada a mano. |
| `email_unverified` | Verificación quitada. |
| `verification_resent` | Reenvío del correo de verificación. |

Las etiquetas en español están en `lang/es/admin.php`, bajo `moderation_actions`.

### 6.3 Actions

Todas autorizan con `Gate` antes de tocar nada, escriben en `moderation_actions` y son no-op cuando el estado ya es el pedido, como `BanUser` y `ChangeUserRole`.

| Action | Qué hace | Permiso |
| --- | --- | --- |
| [CreateUserByAdmin](../app/Actions/CreateUserByAdmin.php) | Crea el usuario con contraseña temporal de 16 caracteres sin símbolos, `must_change_password = true` y email verificado. Devuelve la contraseña una sola vez. | `create` (solo admin) |
| [DeleteUser](../app/Actions/DeleteUser.php) | Borrado lógico. | `delete` (admin, no sobre sí mismo) |
| [RestoreUser](../app/Actions/RestoreUser.php) | Restaura un usuario borrado. | `restore` (admin, no sobre sí mismo) |
| [SetUserEmailVerification](../app/Actions/SetUserEmailVerification.php) | Marca o quita la verificación del email. | `verifyEmail` (admin, no sobre sí mismo) |
| [ResendEmailVerification](../app/Actions/ResendEmailVerification.php) | Reenvía el correo de verificación. Solo si el email sigue sin verificar. **Máximo 3 por hora y por usuario.** | `resendVerification` (admin, no sobre sí mismo) |
| [ChangeTemporaryPassword](../app/Actions/ChangeTemporaryPassword.php) | Fija la nueva contraseña y pone `must_change_password = false`. La validación de la contraseña actual y de las reglas está en el controlador. | Autenticado |

### 6.4 Políticas

[app/Policies/UserPolicy.php](../app/Policies/UserPolicy.php) añade `create`, `delete`, `restore`, `verifyEmail` y `resendVerification`. Las cuatro últimas siguen la regla de `ban`: **solo admin, y nunca sobre uno mismo**. `create` es solo para admin.

### 6.5 Contraseña temporal

Flujo completo:

1. El admin crea el usuario desde `/admin/usuarios` (botón "Nuevo"). En la página de creación elige username, email y rol.
2. Se muestra una notificación persistente con la contraseña temporal. Se puede cerrar, así que el admin tiene que copiarla en ese momento.
3. El usuario entra con esa contraseña. Cualquier panel o ruta protegida le redirige a `/contrasena-temporal`.
4. En esa página pide la contraseña temporal, una nueva (las reglas de `PasswordValidationRules`, más `different:current_password`) y su confirmación. Al guardar, `must_change_password` pasa a `false`.

Piezas:

- [EnsurePasswordIsChanged](../app/Http/Middleware/EnsurePasswordIsChanged.php): redirige mientras el flag esté activo. Rutas permitidas: el propio formulario y los logout de ambos paneles. **No redirige si un admin está impersonando** al usuario, para que pueda revisar la web.
- Registrado en el grupo `web` (en [bootstrap/app.php](../bootstrap/app.php)) y en los dos paneles ([AdminPanelProvider](../app/Providers/Filament/AdminPanelProvider.php), [AppPanelProvider](../app/Providers/Filament/AppPanelProvider.php)). Va justo después de `EnsureUserIsNotBanned`.
- [TemporaryPasswordController](../app/Http/Controllers/Auth/TemporaryPasswordController.php): `edit` y `update`. Tras cambiarla redirige con `intended` a `/app`.
- Rutas `password.temporary.edit` (GET) y `password.temporary.update` (PUT) en [routes/web.php](../routes/web.php), dentro de `auth`.
- Vista [temporary-password.blade.php](../resources/views/pages/auth/temporary-password.blade.php), con textos en `lang/es/auth.php` bajo `temporary_password`. Incluye un botón para cerrar sesión, para que el usuario no quede atrapado si no tiene la contraseña.

**La contraseña temporal no se envía por email.** El admin tiene que pasarla al usuario por otro canal.

### 6.6 Borrado lógico: efectos

- El usuario borrado **no puede entrar**. Comprobado con `Auth::attempt`, porque el proveedor de Eloquent excluye los registros con `deleted_at`.
- Sus sesiones abiertas deberían dejar de ser válidas en la siguiente petición, porque el proveedor de sesión también excluye los borrados. Esto sigue el comportamiento estándar de Laravel y **no está cubierto por ningún test**.
- Su contenido se mantiene: copy-pastas, carpetas, reportes y log. **Sus copy-pastas siguen visibles con su nombre de autor.** Si se prefiere ocultarlos al borrar, es un cambio aparte.
- Username y email **siguen reservados** mientras el usuario exista borrado. No se pueden reutilizar al crear otro usuario.
- **El borrado de cuenta propia desde ajustes sigue siendo definitivo** (`forceDelete`), porque el texto de esa pantalla promete borrado permanente. Cambio en [⚡delete-user-modal.blade.php](../resources/views/pages/settings/⚡delete-user-modal.blade.php). Sin este cambio, el test existente de borrado de cuenta fallaba.

### 6.7 Panel de admin

En [app/Filament/Admin/Resources/Users/](../app/Filament/Admin/Resources/Users/):

- **Tabla** ([UsersTable.php](../app/Filament/Admin/Resources/Users/Tables/UsersTable.php)): columnas "Verificado" y "Borrado". Filtros "Email verificado" (sí/no) y los filtros de borrados de Filament.
- **Acciones de fila y de la ficha** ([UserModerationActions.php](../app/Filament/Admin/Resources/Users/UserModerationActions.php)): verificar, quitar verificación, reenviar verificación, borrar y restaurar.
- **Usuarios borrados**: todas las acciones salvo "Restaurar" quedan ocultas. Editar también se oculta en la tabla y en la ficha, y `UserPolicy::update` devuelve `false` para ellos, así que la página de edición responde 403 aunque se escriba la URL.
- **Creación**: botón "Nuevo" en [ListUsers](../app/Filament/Admin/Resources/Users/Pages/ListUsers.php), que abre [CreateUser](../app/Filament/Admin/Resources/Users/Pages/CreateUser.php). El formulario ([UserForm.php](../app/Filament/Admin/Resources/Users/Schemas/UserForm.php)) pide rol solo al crear. Para cambiarlo después se usa la acción de cambio de rol, que ya registra el log.
- **Ruta**: `getRecordRouteBindingEloquentQuery()` incluye los borrados, así que se puede abrir la ficha de un usuario borrado para restaurarlo. Se hace con `withoutGlobalScope(SoftDeletingScope::class)` porque `withTrashed()` no pasaba phpstan nivel 6.

### 6.8 Textos

- `lang/es/admin.php`: campos, filtros, acciones (`users.actions.*`), creación (`users.create.*`) y tipos de log.
- `lang/es/auth.php`: `temporary_password.*`.
- `lang/es/app.php`: `errors.folder_rate_limited`.
- `lang/es/validation.php`: traducciones de validación (sección 3).

### 6.9 Tests

| Fichero | Cubre |
| --- | --- |
| [CreateUserByAdminTest.php](../tests/Feature/Actions/CreateUserByAdminTest.php) | Creación con contraseña temporal, rol, verificación y log. Un moderador no puede crear. |
| [DeleteUserTest.php](../tests/Feature/Actions/DeleteUserTest.php) | Borrado lógico y log. Un admin no se borra a sí mismo. Un moderador no puede borrar. |
| [RestoreUserTest.php](../tests/Feature/Actions/RestoreUserTest.php) | Restauración y no-op sobre un usuario que no está borrado. |
| [SetUserEmailVerificationTest.php](../tests/Feature/Actions/SetUserEmailVerificationTest.php) | Marcar y quitar verificación, no-op y prohibición sobre uno mismo. |
| [ResendEmailVerificationTest.php](../tests/Feature/Actions/ResendEmailVerificationTest.php) | Reenvío, no-op para verificados y límite de 3 por hora. |
| [TemporaryPasswordTest.php](../tests/Feature/Auth/TemporaryPasswordTest.php) | Redirección al cambio, formulario accesible, impersonación sin redirección, cambio que levanta el flag, rechazo de la temporal como nueva, cambio desde cualquier sitio, login de usuario borrado. |
| [UserPolicyTest.php](../tests/Feature/Policies/UserPolicyTest.php) | Permisos por rol de las acciones nuevas (un test añadido al fichero existente). |
| [UserManagementTest.php](../tests/Feature/Filament/UserManagementTest.php) | Creación desde Filament, bloqueo de creación a moderadores, borrar y restaurar desde la tabla, verificar desde la tabla, acciones ocultas en borrados y ficha de un borrado accesible. |

## 7. Decisiones

Recojo aquí todas las decisiones que tomé sin preguntar, para que se puedan revisar.

1. **Cuentas creadas por admin salen verificadas.** El admin responde de ellas.
2. **Contraseña temporal de 16 caracteres sin símbolos, mostrada una vez.** Sin email, para no enviar credenciales por correo.
3. **Rol elegible al crear**, aunque la petición solo hablaba de crear usuarios. Así se puede crear un moderador directamente.
4. **Reenvío de verificación limitado a 3 por hora y por usuario**, con el mismo mecanismo que el resto de límites del proyecto.
5. **Contenido de usuarios borrados visible con su autor.** Pendiente de confirmar.
6. **Límite de carpetas: 60 por minuto y por usuario**, contando cada cambio de membresía por separado.
7. **Borrado de cuenta propia definitivo.** Mantiene la promesa de la pantalla de ajustes.
8. **Sin 2FA ni cierre de sesiones desde admin**, según lo que se decidió.
9. **Validación escrita a mano, sin `laravel-lang`.**

Cambios al plan: [PLAN.md](PLAN.md) recoge las notas de la Fase 7 (límite de carpetas, resuelto), la Fase 8 (acciones duplicadas, resuelto), la Fase 9 (ampliación con sus decisiones) y la Fase 11 (validación en español, hecha).

## 8. Pendiente y limitaciones conocidas

**Del plan:**

- Despliegue a staging desde CI y checklist de humo (sección 2).
- Mailer real, SPF/DKIM, backups fuera del servidor, logs a un agregador.
- Revisión legal de los textos legales.
- Repetir la auditoría de Lighthouse sobre la imagen de producción. El objetivo es 90 o más en rendimiento.

**Comprobaciones en navegador que nunca se han hecho.** Los tests cubren el backend y los componentes de Filament, pero no se ha clicado en el navegador:

- Modal de reporte y botón de la tarjeta (Fase 8).
- Banda de impersonación y botón "Actuar como" (Fase 9).
- Pestañas de la ficha de usuario.
- Acción "Quitar de la carpeta" del relation manager de Filament (Fase 7).
- **Todo el flujo de gestión de usuarios de la sección 6**, incluido el cambio forzado de contraseña.

**Limitaciones conocidas:**

- **Helper `actor()` duplicado en seis sitios**: `CopypastaModerationActions`, `UserModerationActions`, `CreateTag`, `EditTag`, `CreateMyCopypasta` y `EditMyCopypasta`. Hay que extraerlo a un único sitio. Ya se marcó como limpieza posterior en la Fase 8.
- **CSP con `unsafe-eval`**, necesario para Alpine y Livewire en la build estándar. Migrar a `@alpinejs/csp` está en el backlog.
- **Tema propio de Filament** (`resources/css/filament/admin/theme.css`), aplazado desde la Fase 3.

**Backlog post-MVP** (sin cambios): perfil público, carpetas compartibles, apelaciones, plantillas con variables, login social, comentarios, notificaciones in-app, API pública y multidioma. Está en [PLAN.md](PLAN.md).

## 9. Cómo probarlo en local

Los contenedores de Sail tienen que estar levantados (`vendor/bin/sail up -d`). Con la base de datos al día y los datos de prueba:

```bash
vendor/bin/sail artisan migrate
```

```bash
vendor/bin/sail artisan migrate:fresh --seed
```

El segundo comando borra la base de datos local y carga el seeder: admin, moderador, etiquetas y 300 copy-pastas.

Credenciales de los usuarios sembrados: `admin@copypastas.test` y `moderador@copypastas.test`, contraseña `password`. Correos de prueba en Mailpit: http://localhost:8025.

Lista de comprobación manual para la gestión de usuarios:

1. Entra como admin en http://localhost/admin/usuarios y pulsa **Nuevo**.
2. Crea un usuario con un email que no exista. Copia la contraseña temporal de la notificación.
3. Cierra sesión y entra con ese usuario en http://localhost/app. Debe llegar a `/contrasena-temporal`.
4. Prueba a ir a `/app` sin cambiar la contraseña: debe volver a redirigir. Prueba también el botón "Cerrar sesión" de esa página.
5. Cambia la contraseña. Debe entrar en `/app` sin más redirecciones.
6. Como admin, borra a ese usuario desde la tabla. Intenta entrar con él: debe fallar.
7. Filtra por "Borrado", abre su ficha y restáurale. Vuelve a entrar con sus credenciales.
8. Quita la verificación a un usuario, reenvía el correo (lo verás en Mailpit) y verifícalo a mano.
9. Revisa el log de moderación del usuario: deben aparecer las acciones de los pasos anteriores.

Comandos de verificación:

```bash
vendor/bin/sail artisan test --compact
```

```bash
vendor/bin/sail bin phpstan analyse --no-progress
```

```bash
vendor/bin/sail bin pint --test
```

## Anexo: ficheros modificados por commit

Lista generada desde `git show --name-status`. Incluye los ficheros de `docs/PLAN.md`, que se actualizó en cada commit.

### `f289066` — Validación de formularios en español

- `docs/PLAN.md` (modificado)
- `lang/es/validation.php` (nuevo)
- `tests/Feature/Validation/SpanishValidationMessagesTest.php` (nuevo)

### `8c472f1` — Límite de cambios en carpetas

- `app/Actions/AddToFolder.php` (modificado)
- `app/Actions/CreateFolder.php` (modificado)
- `app/Actions/DeleteFolder.php` (modificado)
- `app/Actions/RemoveFromFolder.php` (modificado)
- `app/Actions/RenameFolder.php` (modificado)
- `app/Concerns/LimitsFolderChanges.php` (nuevo)
- `docs/PLAN.md` (modificado)
- `lang/es/app.php` (modificado)
- `tests/Feature/Actions/FolderChangesLimitTest.php` (nuevo)

### `bbbfc1a` — Acciones de moderación compartidas

- `app/Filament/Admin/Pages/ModerationQueue.php` (modificado)
- `app/Filament/Admin/Resources/Copypastas/Tables/CopypastaModerationActions.php` (nuevo)
- `app/Filament/Admin/Resources/Copypastas/Tables/CopypastasTable.php` (modificado)
- `docs/PLAN.md` (modificado)

### `bc87b15` — Gestión de usuarios

- `app/Actions/ChangeTemporaryPassword.php` (nuevo)
- `app/Actions/CreateUserByAdmin.php` (nuevo)
- `app/Actions/DeleteUser.php` (nuevo)
- `app/Actions/ResendEmailVerification.php` (nuevo)
- `app/Actions/RestoreUser.php` (nuevo)
- `app/Actions/SetUserEmailVerification.php` (nuevo)
- `app/Enums/ModerationActionType.php` (modificado)
- `app/Filament/Admin/Resources/Users/Pages/CreateUser.php` (nuevo)
- `app/Filament/Admin/Resources/Users/Pages/ListUsers.php` (modificado)
- `app/Filament/Admin/Resources/Users/Pages/ViewUser.php` (modificado)
- `app/Filament/Admin/Resources/Users/Schemas/UserForm.php` (modificado)
- `app/Filament/Admin/Resources/Users/Tables/UsersTable.php` (modificado)
- `app/Filament/Admin/Resources/Users/UserModerationActions.php` (modificado)
- `app/Filament/Admin/Resources/Users/UserResource.php` (modificado)
- `app/Http/Controllers/Auth/TemporaryPasswordController.php` (nuevo)
- `app/Http/Middleware/EnsurePasswordIsChanged.php` (nuevo)
- `app/Models/Copypasta.php` (modificado)
- `app/Models/ModerationAction.php` (modificado)
- `app/Models/Report.php` (modificado)
- `app/Models/User.php` (modificado)
- `app/Policies/UserPolicy.php` (modificado)
- `app/Providers/Filament/AdminPanelProvider.php` (modificado)
- `app/Providers/Filament/AppPanelProvider.php` (modificado)
- `bootstrap/app.php` (modificado)
- `database/migrations/2026_10_04_202731_add_user_management_columns_to_users_table.php` (nuevo)
- `docs/PLAN.md` (modificado)
- `lang/es/admin.php` (modificado)
- `lang/es/auth.php` (modificado)
- `resources/views/pages/auth/temporary-password.blade.php` (nuevo)
- `resources/views/pages/settings/⚡delete-user-modal.blade.php` (modificado)
- `routes/web.php` (modificado)
- `tests/Feature/Actions/CreateUserByAdminTest.php` (nuevo)
- `tests/Feature/Actions/DeleteUserTest.php` (nuevo)
- `tests/Feature/Actions/ResendEmailVerificationTest.php` (nuevo)
- `tests/Feature/Actions/RestoreUserTest.php` (nuevo)
- `tests/Feature/Actions/SetUserEmailVerificationTest.php` (nuevo)
- `tests/Feature/Auth/TemporaryPasswordTest.php` (nuevo)
- `tests/Feature/Filament/UserManagementTest.php` (nuevo)
- `tests/Feature/Policies/UserPolicyTest.php` (modificado)
