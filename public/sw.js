/**
 * Service Worker pour PWA
 * Version basique avec cache et offline support
 */

const CACHE_NAME = 'pos-saas-v3'; // Incrémenter pour forcer la mise à jour
const OFFLINE_URL = '/offline.html';

// Installer le service worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // Ne mettre en cache que les fichiers qui existent réellement
            // Les assets Vite sont gérés dynamiquement et ne doivent pas être mis en cache ici
            return Promise.allSettled([
                cache.add('/offline.html').catch(() => {}), // Ignorer si le fichier n'existe pas
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

// Intercepter les requêtes
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Ignorer les requêtes non-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignorer les requêtes Inertia (AJAX avec header X-Inertia)
    // Inertia gère ses propres requêtes et ne doit pas être intercepté
    if (event.request.headers.get('X-Inertia') || 
        event.request.headers.get('X-Requested-With') === 'XMLHttpRequest') {
        return; // Laisser passer la requête sans interception
    }

    // Ignorer les routes admin dynamiques (toujours fraîches)
    if (url.pathname.startsWith('/admin/')) {
        return; // Laisser passer la requête sans interception
    }

    // Ignorer les routes API
    if (url.pathname.startsWith('/api/')) {
        return; // Laisser passer la requête sans interception
    }

    // Mettre en cache les images produits
    if (url.pathname.startsWith('/storage/pharmacy/products/') || 
        url.pathname.startsWith('/storage/products/')) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((response) => {
                    if (response.status === 200) {
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return response;
                }).catch(() => {
                    // Si offline et pas en cache, retourner une image placeholder
                    return new Response('', { status: 404 });
                });
            })
        );
        return;
    }

    // Ignorer les assets Vite (toujours fraîches, gérés par Vite)
    if (url.pathname.startsWith('/build/') || 
        url.pathname.startsWith('/assets/') ||
        url.pathname.includes('/css/') ||
        url.pathname.includes('/js/')) {
        return; // Laisser passer la requête sans interception
    }

    // Ignorer les requêtes avec des schémas non supportés (chrome-extension, moz-extension, etc.)
    if (url.protocol === 'chrome-extension:' || 
        url.protocol === 'moz-extension:' ||
        url.protocol === 'safari-extension:' ||
        !url.protocol.startsWith('http')) {
        return; // Laisser passer la requête sans interception
    }

    // Pour les autres requêtes (pages statiques, CSS, etc.)
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Ne mettre en cache que les réponses réussies et non-Inertia
                if (response.status === 200 && 
                    response.type === 'basic' &&
                    !response.headers.get('X-Inertia')) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        // Vérifier à nouveau le protocole avant de mettre en cache
                        const requestUrl = new URL(event.request.url);
                        if (requestUrl.protocol.startsWith('http')) {
                            cache.put(event.request, responseToCache).catch(() => {
                                // Ignorer les erreurs de cache silencieusement
                            });
                        }
                    });
                }
                return response;
            })
            .catch(() => {
                // En cas d'erreur réseau, retourner depuis le cache
                return caches.match(event.request).then((response) => {
                    if (response) {
                        return response;
                    }
                    // Si pas dans le cache et offline, retourner la page offline uniquement pour les documents HTML
                    if (event.request.destination === 'document' && 
                        !url.pathname.startsWith('/admin/') &&
                        !url.pathname.startsWith('/api/')) {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});

