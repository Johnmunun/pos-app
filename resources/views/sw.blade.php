/**
 * Service Worker (dynamic) – PWA cache + Firebase Cloud Messaging (web)
 */
const CACHE_NAME = 'pos-saas-v4';
const OFFLINE_URL = '/offline.html';

const FCM_ENABLED = {{ $enabled ? 'true' : 'false' }};
const FCM_FIREBASE_CONFIG = {!! json_encode($firebaseConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
const FCM_DEFAULT_ICON_URL = {!! json_encode($iconUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
const FCM_DEFAULT_CLICK_URL = {!! json_encode($clickUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};

// Installer le service worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return Promise.allSettled([
                cache.add(OFFLINE_URL).catch(() => {}),
            ]);
        })
    );
    self.skipWaiting();
});

// Activer le service worker
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
    return self.clients.claim();
});

// Intercepter les requêtes (même logique que l'ancien sw.js)
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    // Important: do not intercept cross-origin requests (Firebase/Google endpoints, etc.)
    // Intercepting them can break Push/FCM token registration in some browsers.
    if (url.origin !== self.location.origin) return;
    if (event.request.method !== 'GET') return;
    if (event.request.headers.get('X-Inertia') || event.request.headers.get('X-Requested-With') === 'XMLHttpRequest') return;
    if (url.pathname.startsWith('/admin/')) return;
    if (url.pathname.startsWith('/api/')) return;
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/assets/') || url.pathname.includes('/css/') || url.pathname.includes('/js/')) return;
    if (url.protocol === 'chrome-extension:' || url.protocol === 'moz-extension:' || url.protocol === 'safari-extension:' || !url.protocol.startsWith('http')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response.status === 200 && response.type === 'basic' && !response.headers.get('X-Inertia')) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        const requestUrl = new URL(event.request.url);
                        if (requestUrl.protocol.startsWith('http')) {
                            cache.put(event.request, responseToCache).catch(() => {});
                        }
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((response) => {
                    if (response) return response;
                    if (event.request.destination === 'document' && !url.pathname.startsWith('/admin/') && !url.pathname.startsWith('/api/')) {
                        return caches.match(OFFLINE_URL).then((offlineResponse) => {
                            if (offlineResponse) return offlineResponse;
                            return new Response('Offline', {
                                status: 503,
                                statusText: 'Service Unavailable',
                                headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                            });
                        });
                    }
                    return new Response('Network error', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                    });
                });
            })
    );
});

// FCM background notifications
if (FCM_ENABLED) {
    // compat libs for service worker
    importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
    importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

    try {
        firebase.initializeApp(FCM_FIREBASE_CONFIG);
        const messaging = firebase.messaging();

        messaging.onBackgroundMessage((payload) => {
            const notification = payload?.notification || {};
            const data = payload?.data || {};

            const title = notification.title || 'OmniPOS';
            const options = {
                body: notification.body || '',
                icon: notification.icon || FCM_DEFAULT_ICON_URL,
                badge: notification.badge || '/icons/icon-96x96.png',
                data: {
                    ...data,
                    click_url: data.click_url || FCM_DEFAULT_CLICK_URL,
                },
            };

            self.registration.showNotification(title, options);
        });
    } catch (e) {
        // Silent: SW should not crash.
    }
}

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const clickUrl = event?.notification?.data?.click_url || FCM_DEFAULT_CLICK_URL;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.focus();
                    return client.navigate(clickUrl);
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(clickUrl);
            }
        })
    );
});

