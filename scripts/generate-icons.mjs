// Renders the app icons from the SVGs in resources/icons/ with Playwright's Chromium, which is already a dev
// dependency. Run it from the project root through Sail:
//
//   vendor/bin/sail node scripts/generate-icons.mjs [--sheet path/to/contact-sheet.png]
//
// Writes public/icons/icon-192.png, icon-512.png, icon-maskable-512.png, public/apple-touch-icon.png (180) and
// public/favicon.ico (16, 32 and 48). public/favicon.svg is authored by hand: it flips colors with the color scheme.
// With --sheet it also writes a contact sheet to check the icons by eye; that file is not part of the repository.

import { readFile, writeFile } from 'node:fs/promises';
import { chromium } from 'playwright';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const sheetIndex = process.argv.indexOf('--sheet');
const sheetPath = sheetIndex === -1 ? null : process.argv[sheetIndex + 1];

const sources = {
    any: await read('resources/icons/icon.svg'),
    maskable: await read('resources/icons/icon-maskable.svg'),
    apple: await read('resources/icons/icon-apple.svg'),
};

const browser = await chromium.launch();
const page = await browser.newPage();

async function render(svg, size) {
    await page.setViewportSize({ width: size, height: size });
    await page.setContent(`<style>html,body{margin:0;background:transparent}svg{display:block;width:${size}px;height:${size}px}</style>${svg}`);

    return page.screenshot({ omitBackground: true, type: 'png' });
}

const targets = [
    ['any', 192, 'public/icons/icon-192.png'],
    ['any', 512, 'public/icons/icon-512.png'],
    ['maskable', 512, 'public/icons/icon-maskable-512.png'],
    ['apple', 180, 'public/apple-touch-icon.png'],
];

for (const [source, size, path] of targets) {
    await writeFile(new URL(path, root), await render(sources[source], size));
}

// An .ico is a small header followed by PNG images, which every current browser reads.
const sizes = [16, 32, 48];
const images = await Promise.all(sizes.map((size) => render(sources.any, size)));
const header = Buffer.alloc(6 + 16 * sizes.length);
header.writeUInt16LE(1, 2);
header.writeUInt16LE(sizes.length, 4);
let offset = header.length;

sizes.forEach((size, index) => {
    const entry = 6 + 16 * index;
    header.writeUInt8(size, entry);
    header.writeUInt8(size, entry + 1);
    header.writeUInt16LE(1, entry + 4);
    header.writeUInt16LE(32, entry + 6);
    header.writeUInt32LE(images[index].length, entry + 8);
    header.writeUInt32LE(offset, entry + 12);
    offset += images[index].length;
});

await writeFile(new URL('public/favicon.ico', root), Buffer.concat([header, ...images]));

if (sheetPath !== null) {
    const files = await Promise.all(targets.map(async ([, , path]) => `data:image/png;base64,${(await readFile(new URL(path, root))).toString('base64')}`));
    const favicon = await read('public/favicon.svg');
    const tile = (src, size, extra = '') => `<img src="${src}" width="${size}" height="${size}" style="${extra}">`;
    const row = (theme) => `
        <section style="background:${theme.bg};color:${theme.ink};padding:24px;display:flex;flex-direction:column;gap:16px">
            <h2 style="margin:0;font:600 16px system-ui">${theme.name}</h2>
            <div style="display:flex;gap:24px;align-items:flex-end;flex-wrap:wrap">
                <figure style="margin:0;font:12px system-ui">${tile(files[1], 160)}<br>any 512</figure>
                <figure style="margin:0;font:12px system-ui">${tile(files[0], 96)}<br>any 192</figure>
                <figure style="margin:0;font:12px system-ui">${tile(files[3], 90)}<br>apple 180</figure>
                <figure style="margin:0;font:12px system-ui">${tile(files[2], 160)}<br>maskable 512 (sin recorte)</figure>
                <figure style="margin:0;font:12px system-ui">${tile(files[2], 160, 'border-radius:50%')}<br>maskable, círculo</figure>
                <figure style="margin:0;font:12px system-ui">${tile(files[2], 160, 'border-radius:28%')}<br>maskable, squircle</figure>
                <figure style="margin:0;font:12px system-ui"><div style="width:32px;height:32px">${favicon.replace('<svg ', '<svg width="32" height="32" ')}</div><br>favicon.svg</figure>
            </div>
        </section>`;
    const html = `<body style="margin:0">${row({ name: 'Fondo claro', bg: '#F3F2FF', ink: '#15132B' })}${row({ name: 'Fondo oscuro', bg: '#0E0D1A', ink: '#F3F2FF' })}</body>`;
    await page.setViewportSize({ width: 1200, height: 620 });
    await page.setContent(html);
    await page.screenshot({ path: sheetPath, fullPage: true });
}

await browser.close();
