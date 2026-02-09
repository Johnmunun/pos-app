/**
 * Service: ImageCache
 * 
 * Gère le cache des images produits via Cache API
 * Évite les re-téléchargements inutiles
 */

const CACHE_NAME = 'pos-saas-images-v1';
const MAX_CACHE_SIZE = 50 * 1024 * 1024; // 50 Mo max

class ImageCache {
    constructor() {
        this.cache = null;
        this.initPromise = null;
    }

    /**
     * Initialiser le cache
     */
    async init() {
        if (this.initPromise) {
            return this.initPromise;
        }

        if ('caches' in window) {
            this.initPromise = caches.open(CACHE_NAME).then(cache => {
                this.cache = cache;
                return cache;
            });
            return this.initPromise;
        }

        return Promise.resolve(null);
    }

    /**
     * Vérifier si une image est en cache
     */
    async isCached(imageUrl) {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache) {
            return false;
        }

        try {
            const response = await this.cache.match(imageUrl);
            return !!response;
        } catch (error) {
            console.warn('Error checking image cache:', error);
            return false;
        }
    }

    /**
     * Récupérer une image depuis le cache ou le réseau
     */
    async getImage(imageUrl) {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache) {
            // Fallback si Cache API n'est pas disponible
            return imageUrl;
        }

        try {
            // Vérifier le cache d'abord
            const cachedResponse = await this.cache.match(imageUrl);
            if (cachedResponse) {
                return URL.createObjectURL(await cachedResponse.blob());
            }

            // Si pas en cache, télécharger et mettre en cache
            if (navigator.onLine) {
                const response = await fetch(imageUrl);
                if (response.ok) {
                    // Vérifier la taille avant de mettre en cache
                    const contentLength = response.headers.get('content-length');
                    if (contentLength && parseInt(contentLength) > MAX_CACHE_SIZE) {
                        // Image trop grande, ne pas mettre en cache
                        return imageUrl;
                    }

                    // Cloner la réponse pour le cache
                    const responseToCache = response.clone();
                    await this.cache.put(imageUrl, responseToCache);
                    
                    return URL.createObjectURL(await response.blob());
                }
            }

            // Si offline et pas en cache, retourner l'URL originale
            return imageUrl;
        } catch (error) {
            console.warn('Error fetching image:', error);
            return imageUrl;
        }
    }

    /**
     * Mettre une image en cache explicitement
     */
    async cacheImage(imageUrl) {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache || !navigator.onLine) {
            return false;
        }

        try {
            // Vérifier si déjà en cache
            if (await this.isCached(imageUrl)) {
                return true;
            }

            const response = await fetch(imageUrl);
            if (response.ok) {
                const contentLength = response.headers.get('content-length');
                if (contentLength && parseInt(contentLength) > MAX_CACHE_SIZE) {
                    return false;
                }

                await this.cache.put(imageUrl, response.clone());
                return true;
            }
        } catch (error) {
            console.warn('Error caching image:', error);
        }

        return false;
    }

    /**
     * Supprimer une image du cache
     */
    async removeImage(imageUrl) {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache) {
            return false;
        }

        try {
            return await this.cache.delete(imageUrl);
        } catch (error) {
            console.warn('Error removing image from cache:', error);
            return false;
        }
    }

    /**
     * Nettoyer le cache (supprimer les anciennes images)
     */
    async cleanCache() {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache) {
            return;
        }

        try {
            const keys = await this.cache.keys();
            // Garder seulement les 100 dernières images
            if (keys.length > 100) {
                const toDelete = keys.slice(0, keys.length - 100);
                await Promise.all(toDelete.map(key => this.cache.delete(key)));
            }
        } catch (error) {
            console.warn('Error cleaning cache:', error);
        }
    }

    /**
     * Obtenir la taille du cache
     */
    async getCacheSize() {
        if (!this.cache) {
            await this.init();
        }

        if (!this.cache) {
            return 0;
        }

        try {
            const keys = await this.cache.keys();
            let totalSize = 0;

            for (const key of keys) {
                const response = await this.cache.match(key);
                if (response) {
                    const blob = await response.blob();
                    totalSize += blob.size;
                }
            }

            return totalSize;
        } catch (error) {
            console.warn('Error calculating cache size:', error);
            return 0;
        }
    }
}

export default new ImageCache();
