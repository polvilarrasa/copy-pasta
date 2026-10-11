// Kill switch: removes the service worker and every cache it made. Deployed with PWA_KILL_SWITCH=true.
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        await Promise.all((await caches.keys()).map((key) => caches.delete(key)));
        await self.registration.unregister();
    })());
});
