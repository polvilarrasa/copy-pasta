# Sistema de diseño — Copy-pastas V2

Resultado de Claude Design. Lo usa la Fase 14 de `docs/PLAN-V2.md`.

## Dirección elegida

**Medianoche** — "chat, neón y votos en carril". Fondo violeta muy oscuro en modo oscuro, acento lima neón, tarjetas definidas por un borde de 1 px (sin sombra) y los votos en un carril lateral de la tarjeta. Se descartaron las direcciones "Papel" (editorial) y "Pegatina" (contornos gruesos y sombra dura); no están en este repo.

## Ficheros

- `tokens.css`: fuente de verdad de los valores (colores en claro y oscuro, tipografía, radios, sombras, espaciado).
- `tailwind.config.js`: referencia generada por Claude Design. **No usar directamente**: el proyecto usa Tailwind 4, que se configura en CSS con `@theme`. Traducir sus valores a `@theme` y a variables CSS por modo.
- `source/`: código de las pantallas finales exportado de Claude Design (`.dc.html`). **No se pueden abrir en el navegador**: dependen del runtime de Claude Design (`support.js`, `<sc-for>`, `<dc-import>`). Sirven como referencia exacta: los estilos están en línea y los datos de ejemplo en el `<script>` final de cada fichero.
  - `Tokens.dc.html`: tabla de tokens con uso de cada uno, escala tipográfica, radios, sombras, espaciado y contrastes verificados.
  - `Card.dc.html` y `Tarjeta.dc.html`: la tarjeta de copy-pasta y sus variantes (ASCII, plantilla, NSFW, "Porque te gusta", copy-pasta del día) en claro y oscuro.
  - Pantallas, en móvil (`-M`) y escritorio (`-D`): `Feed`, `Detalle`, `Publicar`, `Perfil`, `Stats`, `Carpeta`, `Notif`, `Bienvenida`, `Vacios`. Numeradas como en el brief: 1 feed, 2 detalle, 3 publicar, 4 perfil, 5 estadísticas, 6 carpeta, 7 notificaciones, 8 bienvenida, 9 estados vacíos.
- `screens/`: capturas exportadas de Claude Design, si las hay. Ante cualquier duda, `source/` manda sobre las capturas.

## Tokens: lo esencial

- Nombres semánticos con valor claro y oscuro: `bg`, `surface`, `surface-2`, `border`, `ink`, `muted`, `accent`, `on-accent`, `vote`, `on-vote`, `ach`, `pink`, estados (`ok`, `warn`, `bad`, `info` y sus `-bg`), `unread` y `scrim`.
- **El acento cambia de carácter según el modo.** Claro: `accent` casi negro (#1B1740) con texto lima. Oscuro: `accent` lima (#C6F432) con texto oscuro. Igual con `vote`: violeta (#5B2EFF) en claro, lima en oscuro. Implementar como variables CSS que cambian con el modo, nunca como colores fijos.
- **Etiquetas:** una paleta de 5 huecos con fondo y texto por modo: `t1` verde, `t2` naranja, `t3` violeta, `t4` amarillo, `t5` rosa. La columna `tags.color` guarda el hueco (`t1`…`t5`) y el staff lo elige al crear la etiqueta. Varias etiquetas comparten color; es lo esperado.
- **Contraste AA** verificado en todos los pares de texto (detalle en `Tokens.dc.html`).
- **Escala tipográfica propia** (xs 12 → 4xl 52) con interlineado, peso y tracking por tamaño. El cuerpo de la copy-pasta es 15/23; los inputs son 16 px para evitar el zoom de iOS.
- **Radios:** sm 8, md 12, lg 14, xl 16, 2xl 20 (tarjeta), 3xl 24, full.
- **Sombras:** la tarjeta no tiene; `shadow-day` solo para la copy-pasta del día; `shadow-pop` para desplegables; `ring-focus` (2 px de `bg` + 2 px de `vote`) para el foco de teclado.
- **Medidas:** objetivo táctil mínimo 44 px; margen de página 16 px en móvil y 32 px en escritorio; contenedor máximo 1216 px; relleno y separación de tarjetas 14 px. Puntos de ruptura: md 768, lg 1024, xl 1280.

## Tipografía

- Interfaz: **Bricolage Grotesque**, variable (peso 400–800, eje `opsz` 12–96).
- Monoespaciada: **JetBrains Mono**, pesos 400 y 500. Siempre `white-space: pre` en ASCII art.

Las pantallas las cargan desde Google Fonts; en la app hay que **autoalojarlas** con los paquetes `@fontsource` (comprobar el nombre exacto del paquete variable de Bricolage Grotesque), porque la CSP solo permite `'self'`.

## Iconos

Las pantallas usan iconos SVG de trazo dibujados a mano en cada fichero (constante `I` del script). Para la app, usar un set completo de estilo equivalente como componentes Blade. Lucide es el más parecido; Filament sigue con Heroicons en `/admin`.

## Panel `/admin` (Filament)

Filament usa una sola paleta primaria para los dos modos y genera los tonos a partir de un color. Como `accent` cambia entre casi negro y lima, no sirve como primario. Usar **`vote` claro (#5B2EFF)** como color primario de Filament, y los mismos `bg`, `surface`, `border`, `ink` y `muted` en su tema.

## Diferencias entre el diseño y PLAN-V2

El diseño usa contenido de ejemplo. Donde no coincide con el plan, **manda `docs/PLAN-V2.md`**, salvo lo que se indique como decisión aquí.

1. **Logros del perfil:** los nombres y condiciones de `Perfil-*.dc.html` (Primer Ctrl+V, Madrugador, Archivero, Políglota…) son de ejemplo. El catálogo válido es el de PLAN-V2. Se usa solo la maquetación: conseguidos, pendientes con progreso, secretos y título activo.
2. **Contadores del perfil:** el diseño muestra Publicadas, Copias y Puntos. Usar los del plan: publicados, copias recibidas y upvotes recibidos.
3. **Perfil sin listados:** el diseño no incluye los copy-pastas del usuario ni sus carpetas públicas. Añadirlos debajo de los logros con los componentes del sistema (pestañas "Top" y "Nuevos", y rejilla de carpetas).
4. **Publicar:** el botón "Guardar borrador" no está en el plan. *Decisión: se quita; los borradores quedan fuera de la V2.* El contador del título muestra /80, pero el límite del plan es 120. *Decisión: se mantiene 120 y el contador muestra /120.*
5. **Notificación de votos agregados** ("lola.exe y 24 personas más han votado…"): no está en el plan, que solo notifica hitos. *Decisión: fuera; se notifican solo los hitos, para no saturar.*
6. **Carpeta:** el diseño muestra descripción en una carpeta privada, buscador dentro de la carpeta y botón "Compartir carpeta" estando privada. *Decisión: todas las carpetas pueden tener descripción (en las privadas solo la ve su dueño); el buscador dentro de la carpeta entra en la Fase 15; "Compartir carpeta" no aparece hasta la Fase 20, y en una carpeta privada lleva a hacerla pública antes de compartir.*
7. **"Editar favoritas" en el feed:** la barra lateral de escritorio muestra tus etiquetas favoritas con un enlace para editarlas, y la bienvenida dice que se pueden cambiar desde el feed. El plan solo tiene la elección inicial. *Decisión: entra en la Fase 19, reutilizando la pantalla de bienvenida en modo edición.*
8. **Estadísticas:** el diseño tiene cuatro indicadores (copias, votos, guardados, compartidos) con la variación frente a los 30 días anteriores, gráfico seleccionable con tabla accesible y tabla de mejores copy-pastas. Encaja con el plan. Faltan visitas, fiabilidad de reportes y progreso hacia usuario de confianza, que se mantienen y se maquetan con el sistema. La variación necesita 60 días de `copypasta_daily_stats`.
9. **Etiquetas de ejemplo:** `nsfw`, `plantillas` y `ascii` aparecen como etiquetas en los datos de ejemplo. En la app son el aviso +18, la casilla de plantilla y el tipo de contenido de la Fase 23; no se crean como etiquetas.
10. **Avatar:** degradado de dos tonos derivado de un `hue` con un brillo. Encaja con el avatar generado del plan; el `hue` se deriva del id del usuario.

## Huecos conocidos

Sin diseño específico; Claude Code los resuelve con los componentes y tokens del sistema:

- Login, registro, recuperación de contraseña, verificación de email, 2FA y passkeys.
- Ajustes (`/settings/*`), incluidos tema, título y preferencias de notificaciones.
- Mis copy-pastas y la rejilla de carpetas (solo está diseñado el detalle de una carpeta).
- Páginas legales, aviso anónimo, 404 y 403.
- Panel `/admin`: solo el tema (colores y tipografía), sin rediseño de pantallas.
