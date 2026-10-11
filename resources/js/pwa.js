/**
 * Service worker registration and the "Instalar app" prompt.
 *
 * The page announces the worker with <meta name="service-worker">; without it (kill switch on) every worker this
 * origin registered is removed, along with its caches.
 */

const INSTALL_URL = '/pwa/instalada';

let installPrompt = null;

/**
 * The install entry of the user menu asks Alpine whether the browser offered the prompt.
 */
window.copypastasInstall = {
    available: () => installPrompt !== null,

    async prompt() {
        if (installPrompt === null) {
            return;
        }

        const prompt = installPrompt;

        installPrompt = null;
        window.dispatchEvent(new CustomEvent('pwa-install-state'));

        await prompt.prompt();
    },
};

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa-install-state'));
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-install-state'));

    fetch(INSTALL_URL, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
    }).catch(() => {});
});

async function unregisterWorkers() {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(registrations.map((registration) => registration.unregister()));

    if ('caches' in window) {
        await Promise.all((await caches.keys()).map((key) => caches.delete(key)));
    }
}

if (window.isSecureContext && 'serviceWorker' in navigator) {
    const url = document.querySelector('meta[name="service-worker"]')?.content;

    window.addEventListener('load', () => {
        (url ? navigator.serviceWorker.register(url, { scope: '/' }) : unregisterWorkers()).catch(() => {});
    });
}
