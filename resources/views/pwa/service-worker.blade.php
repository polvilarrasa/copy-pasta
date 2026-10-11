// Service worker of copy-pastas. Deliberately small: it only makes the offline page and the hashed build assets
// available without the network. It never stores a page, because pages carry the member's data.
const CACHE_PREFIX = 'copypastas-';
const CACHE = CACHE_PREFIX + @json($version);
const OFFLINE_URL = @json($offlineUrl);
const PRECACHE = @json($precache);

// Never touched: the browser handles these as if there were no worker.
const UNTOUCHED_PATHS = ['/livewire', '/admin', '/notificaciones'];

const isUntouched = (url) => UNTOUCHED_PATHS.some((path) => url.pathname === path || url.pathname.startsWith(path + '/') || url.pathname.startsWith(path + '-'));

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE.map((url) => new Request(url, { cache: 'reload' }))))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin || isUntouched(url)) {
        return;
    }

    // Pages: always the network. If it fails, the offline page.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    // Vite assets carry a hash in their name, so a cached one is never stale: cache first.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok && response.type === 'basic') {
                    const copy = response.clone();

                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            })),
        );
    }
});
