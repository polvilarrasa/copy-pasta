/** copy-pastas · Tailwind · tokens "Medianoche"
 *  Requiere tokens.css (variables --cp-*). Modo oscuro con la clase .dark.
 *  Uso: bg-bg, bg-surface, text-ink, text-muted, bg-accent text-on-accent, border-line,
 *       bg-tag-2-bg text-tag-2-fg, rounded-card, shadow-pop, text-card-title ...
 */
const v = (name) => `var(--cp-${name})`;

module.exports = {
  darkMode: 'class',
  theme: {
    screens: { md: '768px', lg: '1024px', xl: '1280px' }, // base = móvil de 390 px
    extend: {
      colors: {
        bg: v('bg'),
        surface: { DEFAULT: v('surface'), 2: v('surface-2') },
        line: v('border'),
        ink: v('ink'),
        muted: v('muted'),
        accent: { DEFAULT: v('accent'), on: v('on-accent') },
        vote: { DEFAULT: v('vote'), on: v('on-vote') },
        ach: v('ach'),
        alert: v('pink'),
        ok: { DEFAULT: v('ok'), bg: v('ok-bg') },
        warn: { DEFAULT: v('warn'), bg: v('warn-bg') },
        bad: { DEFAULT: v('bad'), bg: v('bad-bg') },
        info: { DEFAULT: v('info'), bg: v('info-bg') },
        tag: {
          1: { bg: v('t1-bg'), fg: v('t1-fg') }, // menta: wholesome, chat
          2: { bg: v('t2-bg'), fg: v('t2-fg') }, // naranja: humor, memes
          3: { bg: v('t3-bg'), fg: v('t3-fg') }, // violeta: gaming, cringe, tecnología
          4: { bg: v('t4-bg'), fg: v('t4-fg') }, // amarillo: oficina, clásicos, ascii
          5: { bg: v('t5-bg'), fg: v('t5-fg') }, // rosa: plantillas, anime, nsfw
        },
        unread: v('unread'),
        scrim: v('scrim'),
      },
      fontFamily: {
        sans: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },
      fontSize: {
        xs: ['12px', { lineHeight: '16px', fontWeight: '600' }],
        sm: ['14px', { lineHeight: '20px', fontWeight: '600' }],
        base: ['15px', { lineHeight: '23px' }],                 // cuerpo de la copy-pasta
        md: ['16px', { lineHeight: '24px', fontWeight: '700' }], // inputs y botones
        'card-title': ['19px', { lineHeight: '24px', letterSpacing: '-0.015em', fontWeight: '700' }],
        xl: ['22px', { lineHeight: '28px', letterSpacing: '-0.03em', fontWeight: '800' }],
        '2xl': ['28px', { lineHeight: '34px', letterSpacing: '-0.025em', fontWeight: '800' }],
        '3xl': ['36px', { lineHeight: '40px', letterSpacing: '-0.035em', fontWeight: '800' }],
        '4xl': ['52px', { lineHeight: '54px', letterSpacing: '-0.04em', fontWeight: '800' }],
      },
      borderRadius: {
        sm: '8px',   // distintivo Plantilla
        md: '12px',  // botones de icono, filas
        lg: '14px',  // Copiar, inputs, carril de votos
        xl: '16px',
        card: '20px',
        panel: '24px',
        full: '9999px',
      },
      boxShadow: {
        day: v('shadow-day'),
        pop: v('shadow-pop'),
        focus: '0 0 0 2px var(--cp-bg), 0 0 0 4px var(--cp-vote)',
      },
      spacing: { 11: '44px', 'tap': '44px', 'gutter': '16px', 'gutter-lg': '32px', 'card-pad': '14px' },
      minHeight: { tap: '44px' },
      minWidth: { tap: '44px' },
      maxWidth: { page: '1216px', feed: '640px' },
    },
  },
};

/* ------------------------------------------------------------------ *
 * FILAMENT (panel de administración)
 *
 * En un service provider (p. ej. AdminPanelProvider):
 *
 *   ->colors([
 *       'primary' => [50 => '#F3F0FF', 100 => '#E7E1FF', 200 => '#D2C7FF', 300 => '#B5A2FF',
 *                     400 => '#9473FF', 500 => '#7647FF', 600 => '#5B2EFF', 700 => '#4A1FE0',
 *                     800 => '#3D1BB5', 900 => '#321A8F', 950 => '#1E0F5C'],
 *       'gray'    => [50 => '#F3F2FF', 100 => '#EAE8FB', 200 => '#DAD7F3', 300 => '#C0BDE3',
 *                     400 => '#A5A2D0', 500 => '#7F7CAD', 600 => '#55527C', 700 => '#3A3860',
 *                     800 => '#2A2847', 900 => '#17162A', 950 => '#0E0D1A'],
 *       'success' => [50 => '#CFF5E3', 500 => '#0B5A3C', 400 => '#6DF0B8', 950 => '#12392F'],
 *       'warning' => [50 => '#FAF0B5', 500 => '#5E4D00', 400 => '#F5E26B', 950 => '#433A0A'],
 *       'danger'  => [50 => '#FFD9DC', 500 => '#A3202B', 400 => '#FF8A8A', 950 => '#4D1A22'],
 *       'info'    => [50 => '#E0D9FF', 500 => '#40259E', 400 => '#B9A8FF', 950 => '#2B2263'],
 *   ])
 *   ->font('Bricolage Grotesque')
 *
 * Notas:
 *  - En Filament el primario es el violeta (600 = #5B2EFF). El lima #C6F432 queda como
 *    color de marca del sitio público y como acento de la pestaña/botón principal en modo
 *    oscuro si quieres verlo también en el panel (primary 400 → '#C6F432' en dark).
 *  - Las etiquetas (t1…t5) se pueden usar como colores de Badge/TextColumn con ->color().
 *  - Tablas: texto sm (14/20), tabular-nums, cabecera 12/16 en muted, filas de 44 px.
 * ------------------------------------------------------------------ */
