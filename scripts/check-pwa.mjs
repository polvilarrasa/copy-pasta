// Checks the PWA of a running copy-pastas, in the way a person would by hand with Chrome's DevTools:
//
//   vendor/bin/sail node scripts/check-pwa.mjs [address]        (the default address is http://localhost)
//
// 1. The manifest is valid JSON with the fields that installability needs.
// 2. Chrome's own installability check (the one in DevTools > Application > Manifest) reports no errors.
// 3. /sw.js is served as JavaScript and the worker takes control of the page.
// 4. With the network cut, a navigation shows the offline page.
//
// It needs a page served from localhost or HTTPS. Exits with 1 when any check fails. Use it on staging and, in
// Fase 24, on the production server.

import { chromium } from 'playwright';

const base = (process.argv[2] ?? 'http://localhost').replace(/\/$/, '');
const results = [];

function check(name, passed, detail = '') {
    results.push({ name, passed, detail });
    console.log(`${passed ? 'OK  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

// The new headless mode (channel chromium) is the one whose installability check works; the old one answers with nothing.
const browser = await chromium.launch({ channel: 'chromium' });
const context = await browser.newContext();
const page = await context.newPage();

try {
    await page.goto(`${base}/`, { waitUntil: 'load' });

    // Requests go through the page, so they use the browser's network like a visitor does.
    const fetchInPage = (url) => page.evaluate(async (address) => {
        const response = await fetch(address);

        return { type: response.headers.get('content-type') ?? '', body: await response.text() };
    }, url);

    const manifestHref = await page.getAttribute('link[rel="manifest"]', 'href');
    const manifestResponse = await fetchInPage(new URL(manifestHref, base).href);
    let manifest = null;

    try {
        manifest = JSON.parse(manifestResponse.body);
    } catch {
        // Reported by the check below.
    }

    check('El manifest es JSON válido', manifest !== null, manifestResponse.type);

    if (manifest !== null) {
        const sizes = (manifest.icons ?? []).map((icon) => `${icon.sizes}:${icon.purpose}`);

        check('Tiene nombre, start_url, scope y display standalone', Boolean(manifest.name && manifest.start_url && manifest.scope) && manifest.display === 'standalone');
        check('Tiene iconos 192, 512 y 512 maskable', ['192x192:any', '512x512:any', '512x512:maskable'].every((size) => sizes.includes(size)), sizes.join(', '));
        check('Declara el share target a /publicar con title y text', manifest.share_target?.action === '/publicar' && manifest.share_target?.params?.title === 'title' && manifest.share_target?.params?.text === 'text');
    }

    const cdp = await context.newCDPSession(page);

    await cdp.send('Page.enable');
    await page.waitForTimeout(1500);

    // `in-incognito` is only the test browser's own context; it is not a problem of the site.
    const installabilityErrors = (await cdp.send('Page.getInstallabilityErrors')).installabilityErrors
        .map((error) => error.errorId)
        .filter((errorId) => errorId !== 'in-incognito');

    check('Chrome no ve errores de instalabilidad', installabilityErrors.length === 0, installabilityErrors.join(', '));

    const worker = await fetchInPage(`${base}/sw.js`);

    check('/sw.js se sirve como JavaScript', /^text\/javascript/.test(worker.type), worker.type);

    const controlled = await page.evaluate(async () => {
        await navigator.serviceWorker.ready;

        if (! navigator.serviceWorker.controller) {
            await new Promise((resolve) => navigator.serviceWorker.addEventListener('controllerchange', resolve, { once: true }));
        }

        return true;
    });

    check('El service worker controla la página', controlled === true);

    await context.setOffline(true);
    await page.goto(`${base}/normas`).catch(() => {});

    const offlineText = await page.textContent('body');

    check('Sin conexión, una navegación muestra la página offline', /Sin conexión/.test(offlineText ?? '') && /Reintentar/.test(offlineText ?? ''));
} finally {
    await browser.close();
}

process.exit(results.every((result) => result.passed) ? 0 : 1);
