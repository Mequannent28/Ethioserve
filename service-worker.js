const CACHE_NAME = 'ethioserve-v1';
const OFFLINE_URL = '/Ethioserve-main/offline.html';

// Assets to pre-cache on install
const PRECACHE_ASSETS = [
    '/Ethioserve-main/customer/index.php',
    '/Ethioserve-main/assets/css/style.css',
    '/Ethioserve-main/assets/js/main.js',
    '/Ethioserve-main/offline.html',
    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'
];

// Install event — pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching app shell...');
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event — clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch event — serve from cache or network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (form submissions, API calls, etc.)
    if (request.method !== 'GET') return;

    // For API calls and PHP pages — network first, fall back to cache
    if (url.pathname.endsWith('.php') || url.pathname.includes('/api')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache a copy of the response
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // Try cache, then offline page
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match(OFFLINE_URL);
                    });
                })
        );
        return;
    }

    // For static assets (CSS, JS, images, fonts) — cache first
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Return cached version but also update in background
                fetch(request).then((response) => {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, response);
                    });
                }).catch(() => { });
                return cachedResponse;
            }
            // Not in cache — fetch from network and cache it
            return fetch(request).then((response) => {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(request, responseClone);
                });
                return response;
            }).catch(() => {
                return caches.match(OFFLINE_URL);
            });
        })
    );
});
