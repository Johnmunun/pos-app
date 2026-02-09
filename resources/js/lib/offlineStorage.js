/**
 * Service: OfflineStorage
 * 
 * Gère le stockage offline des produits et images via IndexedDB
 * Respecte les bonnes pratiques PWA
 */

const DB_NAME = 'pos-saas-offline';
const DB_VERSION = 3; // Incrémenté pour ajouter STORE_SETTINGS
const STORE_PRODUCTS = 'pending_products';
const STORE_IMAGES = 'pending_images';
const STORE_CATEGORIES = 'pending_categories';
const STORE_SETTINGS = 'pending_settings';

class OfflineStorage {
    constructor() {
        this.db = null;
        this.initPromise = null;
    }

    /**
     * Initialiser IndexedDB
     */
    async init() {
        if (this.initPromise) {
            return this.initPromise;
        }

        this.initPromise = new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store pour les produits en attente
                if (!db.objectStoreNames.contains(STORE_PRODUCTS)) {
                    const productStore = db.createObjectStore(STORE_PRODUCTS, { keyPath: 'id', autoIncrement: false });
                    productStore.createIndex('status', 'status', { unique: false });
                    productStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store pour les images en attente
                if (!db.objectStoreNames.contains(STORE_IMAGES)) {
                    const imageStore = db.createObjectStore(STORE_IMAGES, { keyPath: 'product_id', unique: true });
                    imageStore.createIndex('status', 'status', { unique: false });
                }

                // Store pour les catégories en attente
                if (!db.objectStoreNames.contains(STORE_CATEGORIES)) {
                    const categoryStore = db.createObjectStore(STORE_CATEGORIES, { keyPath: 'id', autoIncrement: false });
                    categoryStore.createIndex('status', 'status', { unique: false });
                    categoryStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store pour les paramètres en attente
                if (!db.objectStoreNames.contains(STORE_SETTINGS)) {
                    const settingsStore = db.createObjectStore(STORE_SETTINGS, { keyPath: 'id', autoIncrement: true });
                    settingsStore.createIndex('status', 'status', { unique: false });
                    settingsStore.createIndex('created_at', 'created_at', { unique: false });
                }
            };
        });

        return this.initPromise;
    }

    /**
     * Vérifier si on est en ligne
     */
    isOnline() {
        return navigator.onLine;
    }

    /**
     * Stocker un produit en attente de synchronisation
     */
    async savePendingProduct(productData, imageFile = null) {
        await this.init();

        const transaction = this.db.transaction([STORE_PRODUCTS, STORE_IMAGES], 'readwrite');
        const productStore = transaction.objectStore(STORE_PRODUCTS);
        const imageStore = transaction.objectStore(STORE_IMAGES);

        // Préparer les données du produit
        const pendingProduct = {
            id: productData.id || `pending_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            ...productData,
            status: 'pending',
            created_at: new Date().toISOString(),
            synced: false
        };

        // Stocker le produit
        await productStore.put(pendingProduct);

        // Stocker l'image si fournie
        if (imageFile) {
            const imageData = await this.fileToBase64(imageFile);
            await imageStore.put({
                product_id: pendingProduct.id,
                image: imageData,
                filename: imageFile.name,
                mimeType: imageFile.type,
                size: imageFile.size,
                status: 'pending',
                created_at: new Date().toISOString()
            });
        }

        return pendingProduct.id;
    }

    /**
     * Récupérer tous les produits en attente
     */
    async getPendingProducts() {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_PRODUCTS], 'readonly');
            const store = transaction.objectStore(STORE_PRODUCTS);
            const index = store.index('status');
            const request = index.getAll('pending');

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Récupérer une image en attente
     */
    async getPendingImage(productId) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_IMAGES], 'readonly');
            const store = transaction.objectStore(STORE_IMAGES);
            const request = store.get(productId);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marquer un produit comme synchronisé
     */
    async markProductSynced(productId) {
        await this.init();

        const transaction = this.db.transaction([STORE_PRODUCTS, STORE_IMAGES], 'readwrite');
        const productStore = transaction.objectStore(STORE_PRODUCTS);
        const imageStore = transaction.objectStore(STORE_IMAGES);

        // Mettre à jour le produit
        const product = await new Promise((resolve, reject) => {
            const request = productStore.get(productId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        if (product) {
            product.status = 'synced';
            product.synced = true;
            product.synced_at = new Date().toISOString();
            await productStore.put(product);
        }

        // Mettre à jour l'image
        const image = await new Promise((resolve, reject) => {
            const request = imageStore.get(productId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        if (image) {
            image.status = 'synced';
            image.synced_at = new Date().toISOString();
            await imageStore.put(image);
        }
    }

    /**
     * Supprimer un produit synchronisé
     */
    async removeSyncedProduct(productId) {
        await this.init();

        const transaction = this.db.transaction([STORE_PRODUCTS, STORE_IMAGES], 'readwrite');
        const productStore = transaction.objectStore(STORE_PRODUCTS);
        const imageStore = transaction.objectStore(STORE_IMAGES);

        await productStore.delete(productId);
        await imageStore.delete(productId);
    }

    /**
     * Convertir un fichier en base64
     */
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    /**
     * Convertir base64 en File
     */
    base64ToFile(base64, filename, mimeType) {
        const arr = base64.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, { type: mimeType || mime });
    }

    /**
     * Stocker une catégorie en attente de synchronisation
     */
    async savePendingCategory(categoryData) {
        await this.init();

        const transaction = this.db.transaction([STORE_CATEGORIES], 'readwrite');
        const categoryStore = transaction.objectStore(STORE_CATEGORIES);

        const pendingCategory = {
            id: categoryData.id || `pending_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            ...categoryData,
            status: 'pending',
            created_at: categoryData.created_at || new Date().toISOString(),
            synced: false
        };

        await categoryStore.put(pendingCategory);
        return pendingCategory.id;
    }

    /**
     * Récupérer toutes les catégories en attente
     */
    async getPendingCategories() {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_CATEGORIES], 'readonly');
            const store = transaction.objectStore(STORE_CATEGORIES);
            const index = store.index('status');
            const request = index.getAll('pending');

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marquer une catégorie comme synchronisée
     */
    async markCategorySynced(categoryId) {
        await this.init();

        const transaction = this.db.transaction([STORE_CATEGORIES], 'readwrite');
        const categoryStore = transaction.objectStore(STORE_CATEGORIES);

        const category = await new Promise((resolve, reject) => {
            const request = categoryStore.get(categoryId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        if (category) {
            category.status = 'synced';
            category.synced = true;
            category.synced_at = new Date().toISOString();
            await categoryStore.put(category);
        }
    }

    /**
     * Supprimer une catégorie synchronisée
     */
    async removeSyncedCategory(categoryId) {
        await this.init();

        const transaction = this.db.transaction([STORE_CATEGORIES], 'readwrite');
        const categoryStore = transaction.objectStore(STORE_CATEGORIES);

        await categoryStore.delete(categoryId);
    }

    /**
     * Stocker les paramètres en attente de synchronisation
     */
    async savePendingSettings(settingsData) {
        await this.init();
        const transaction = this.db.transaction([STORE_SETTINGS], 'readwrite');
        const store = transaction.objectStore(STORE_SETTINGS);

        const pendingSettings = {
            ...settingsData,
            status: 'pending',
            created_at: new Date().toISOString(),
            synced: false
        };

        await store.put(pendingSettings);
        return pendingSettings;
    }

    /**
     * Récupérer les paramètres en attente
     */
    async getPendingSettings() {
        await this.init();
        const transaction = this.db.transaction([STORE_SETTINGS], 'readonly');
        const store = transaction.objectStore(STORE_SETTINGS);
        const index = store.index('status');

        return new Promise((resolve, reject) => {
            const request = index.getAll('pending');
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marquer les paramètres comme synchronisés
     */
    async markSettingsSynced(settingsId) {
        await this.init();
        const transaction = this.db.transaction([STORE_SETTINGS], 'readwrite');
        const store = transaction.objectStore(STORE_SETTINGS);

        const getRequest = store.get(settingsId);
        getRequest.onsuccess = () => {
            const settings = getRequest.result;
            if (settings) {
                settings.status = 'synced';
                settings.synced = true;
                settings.synced_at = new Date().toISOString();
                store.put(settings);
            }
        };
    }

    /**
     * Supprimer les paramètres synchronisés
     */
    async removeSyncedSettings(settingsId) {
        await this.init();
        const transaction = this.db.transaction([STORE_SETTINGS], 'readwrite');
        const store = transaction.objectStore(STORE_SETTINGS);
        await store.delete(settingsId);
    }
}

export default new OfflineStorage();
