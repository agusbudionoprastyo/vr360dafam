const CACHE_VERSION = 'v1';
const CACHE_NAME = '::static-cache';
const OFFLINE_URL = 'offline.html';

const urlsToCache = [
    'offline.html'
];

self.addEventListener('install', function(event) {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_VERSION + CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        (async function() {
            if (self.registration && self.registration.navigationPreload) {
                await self.registration.navigationPreload.enable();
            }
        })().then(
            caches.keys().then(function(cacheNames) {
                cacheNames.forEach(function(cacheName) {
                    if (cacheName !== CACHE_VERSION + CACHE_NAME) {
                        caches.delete(cacheName);
                    }
                })
            })
        )
    )
})

self.addEventListener('fetch', event => {
    const { request } = event;
    if (request.headers.has('range')) return;
    if (request.method == 'POST') return;
    if (request.url.includes('google')) return;
    event.respondWith(
        (async function() {
            let cachedResponse = await caches.match(request);
            if (cachedResponse) return cachedResponse;
            try {
                const response = await event.preloadResponse;
                if (response) return response;
                return await fetch(request);
            } catch (err) {
                if (request.mode === 'navigate') {
                    const cache = await caches.open(CACHE_VERSION + CACHE_NAME);
                    cachedResponse = await cache.match(OFFLINE_URL);
                    return cachedResponse;
                }
                throw err;
            }
        })()
    );
});