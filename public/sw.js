// Sprint 23 — minimal service-worker (network-first for HTML, cache-first for static).
const CACHE = 'webfactory-v1';
self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});
self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match('/'))
        );
        return;
    }
    if (event.request.method !== 'GET') return;
    event.respondWith(
        caches.open(CACHE).then((cache) => cache.match(event.request).then((cached) => {
            const fetched = fetch(event.request).then((res) => {
                cache.put(event.request, res.clone());
                return res;
            }).catch(() => cached);
            return cached || fetched;
        }))
    );
});
