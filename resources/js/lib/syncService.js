/**
 * Service: SyncService
 * 
 * Gère la synchronisation automatique des produits et images en attente
 * S'exécute automatiquement à la reconnexion
 */

import offlineStorage from './offlineStorage';
import { toast } from 'react-hot-toast';
import axios from 'axios';

class SyncService {
    constructor() {
        this.isSyncing = false;
        this.syncListeners = [];
        this.setupOnlineListener();
    }

    /**
     * Configurer l'écouteur de reconnexion
     */
    setupOnlineListener() {
        window.addEventListener('online', () => {
            console.log('Connection restored, starting sync...');
            this.syncPendingItems();
        });

        // Synchroniser au chargement de la page si en ligne
        if (navigator.onLine) {
            // Attendre un peu pour que l'app soit prête
            setTimeout(() => {
                this.syncPendingItems();
            }, 2000);
        }
    }

    /**
     * Synchroniser tous les éléments en attente
     */
    async syncPendingItems() {
        if (this.isSyncing || !navigator.onLine) {
            return;
        }

        this.isSyncing = true;
        this.notifyListeners({ type: 'sync_start' });

        try {
            // Synchroniser les produits
            const pendingProducts = await offlineStorage.getPendingProducts();
            let syncedProducts = 0;
            let errorProducts = 0;

            for (const product of pendingProducts) {
                try {
                    await this.syncProduct(product);
                    syncedProducts++;
                } catch (error) {
                    console.error('Error syncing product:', error);
                    errorProducts++;
                }
            }

            // Nettoyer les produits synchronisés
            for (const product of pendingProducts) {
                if (product.synced) {
                    await offlineStorage.removeSyncedProduct(product.id);
                }
            }

            // Synchroniser les catégories
            const pendingCategories = await offlineStorage.getPendingCategories();
            let syncedCategories = 0;
            let errorCategories = 0;

            for (const category of pendingCategories) {
                try {
                    await this.syncCategory(category);
                    syncedCategories++;
                } catch (error) {
                    console.error('Error syncing category:', error);
                    errorCategories++;
                }
            }

            // Nettoyer les catégories synchronisées
            for (const category of pendingCategories) {
                if (category.synced) {
                    await offlineStorage.removeSyncedCategory(category.id);
                }
            }

            // Synchroniser les paramètres
            const pendingSettings = await offlineStorage.getPendingSettings();
            let syncedSettings = 0;
            let errorSettings = 0;

            for (const settings of pendingSettings) {
                try {
                    await this.syncSettings(settings);
                    syncedSettings++;
                } catch (error) {
                    console.error('Error syncing settings:', error);
                    errorSettings++;
                }
            }

            // Nettoyer les paramètres synchronisés
            for (const settings of pendingSettings) {
                if (settings.synced) {
                    await offlineStorage.removeSyncedSettings(settings.id);
                }
            }

            // Notifications
            const totalSynced = syncedProducts + syncedCategories + syncedSettings;
            const totalErrors = errorProducts + errorCategories + errorSettings;

            if (totalSynced > 0) {
                const messages = [];
                if (syncedProducts > 0) messages.push(`${syncedProducts} produit(s)`);
                if (syncedCategories > 0) messages.push(`${syncedCategories} catégorie(s)`);
                if (syncedSettings > 0) messages.push(`${syncedSettings} paramètre(s)`);
                toast.success(`${messages.join(' et ')} synchronisé(s)`, {
                    duration: 3000
                });
            }

            if (totalErrors > 0) {
                toast.error(`${totalErrors} erreur(s) lors de la synchronisation`, {
                    duration: 4000
                });
            }

            this.notifyListeners({ 
                type: 'sync_complete', 
                synced: totalSynced, 
                errors: totalErrors 
            });
        } catch (error) {
            console.error('Error during sync:', error);
            this.notifyListeners({ type: 'sync_error', error });
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Synchroniser un produit spécifique
     */
    async syncProduct(product) {
        const productId = product.id;
        const isNew = productId.startsWith('pending_');

        // Récupérer l'image en attente si elle existe
        const pendingImage = await offlineStorage.getPendingImage(productId);

        // Préparer les données
        const formData = new FormData();
        
        // Ajouter les champs du produit
        Object.keys(product).forEach(key => {
            if (key !== 'id' && key !== 'status' && key !== 'created_at' && key !== 'synced' && key !== 'synced_at') {
                const value = product[key];
                if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            }
        });

        // Ajouter l'image si elle existe
        if (pendingImage && pendingImage.image) {
            const imageFile = offlineStorage.base64ToFile(
                pendingImage.image,
                pendingImage.filename,
                pendingImage.mimeType
            );
            formData.append('image', imageFile);
        }

        // Envoyer au backend
        const url = isNew 
            ? route('pharmacy.products.store')
            : route('pharmacy.products.update', productId.replace('pending_', ''));

        const method = isNew ? 'post' : 'put';

        try {
            const response = await axios[method](url, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            // Marquer comme synchronisé
            await offlineStorage.markProductSynced(productId);

            return response.data;
        } catch (error) {
            // Si erreur 422 (validation), le produit reste en attente
            if (error.response?.status === 422) {
                throw new Error('Validation error: ' + (error.response.data.message || 'Invalid data'));
            }
            throw error;
        }
    }

    /**
     * Synchroniser manuellement
     */
    async syncNow() {
        if (!navigator.onLine) {
            toast.error('Vous devez être en ligne pour synchroniser');
            return;
        }

        await this.syncPendingItems();
    }

    /**
     * Ajouter un écouteur de synchronisation
     */
    addSyncListener(callback) {
        this.syncListeners.push(callback);
    }

    /**
     * Retirer un écouteur
     */
    removeSyncListener(callback) {
        this.syncListeners = this.syncListeners.filter(cb => cb !== callback);
    }

    /**
     * Notifier les écouteurs
     */
    notifyListeners(event) {
        this.syncListeners.forEach(callback => {
            try {
                callback(event);
            } catch (error) {
                console.error('Error in sync listener:', error);
            }
        });
    }

    /**
     * Synchroniser une catégorie spécifique
     */
    async syncCategory(category) {
        const categoryId = category.id;
        const isNew = categoryId.startsWith('pending_');

        // Préparer les données
        const categoryData = {
            name: category.name,
            description: category.description || '',
            parent_id: category.parent_id || null,
            sort_order: category.sort_order || 0,
            is_active: category.is_active !== undefined ? category.is_active : true
        };

        // Envoyer au backend
        const url = isNew 
            ? route('pharmacy.categories.store')
            : route('pharmacy.categories.update', categoryId.replace('pending_', ''));

        const method = isNew ? 'post' : 'put';

        try {
            const response = await axios[method](url, categoryData);

            // Marquer comme synchronisé
            await offlineStorage.markCategorySynced(categoryId);

            return response.data;
        } catch (error) {
            // Si erreur 422 (validation), la catégorie reste en attente
            if (error.response?.status === 422) {
                throw new Error('Validation error: ' + (error.response.data.message || 'Invalid data'));
            }
            throw error;
        }
    }

    /**
     * Synchroniser uniquement les catégories en attente
     */
    async syncPendingCategories() {
        if (!navigator.onLine) {
            return;
        }

        try {
            const pendingCategories = await offlineStorage.getPendingCategories();
            
            for (const category of pendingCategories) {
                try {
                    await this.syncCategory(category);
                } catch (error) {
                    console.error('Error syncing category:', error);
                }
            }

            // Nettoyer les catégories synchronisées
            for (const category of pendingCategories) {
                if (category.synced) {
                    await offlineStorage.removeSyncedCategory(category.id);
                }
            }
        } catch (error) {
            console.error('Error during category sync:', error);
        }
    }

    /**
     * Synchroniser uniquement les paramètres en attente
     */
    async syncPendingSettings() {
        if (!navigator.onLine) {
            return;
        }

        try {
            const pendingSettings = await offlineStorage.getPendingSettings();
            let syncedSettings = 0;
            let errorSettings = 0;

            for (const settings of pendingSettings) {
                try {
                    await this.syncSettings(settings);
                    syncedSettings++;
                } catch (error) {
                    console.error('Error syncing settings:', error);
                    errorSettings++;
                }
            }

            // Nettoyer les paramètres synchronisés
            for (const settings of pendingSettings) {
                if (settings.synced) {
                    await offlineStorage.removeSyncedSettings(settings.id);
                }
            }

            if (syncedSettings > 0) {
                toast.success(`${syncedSettings} paramètre(s) synchronisé(s)`);
            }
        } catch (error) {
            console.error('Error syncing pending settings:', error);
        }
    }

    /**
     * Synchroniser des paramètres
     */
    async syncSettings(settingsData) {
        const formData = new FormData();
        
        // Convertir logo_base64 en File si présent
        if (settingsData.logo_base64) {
            const file = offlineStorage.base64ToFile(
                settingsData.logo_base64,
                'logo.png',
                'image/png'
            );
            formData.append('logo', file);
        }

        // Ajouter les autres champs
        Object.keys(settingsData).forEach(key => {
            if (key !== 'logo_base64' && key !== 'id' && key !== 'status' && key !== 'created_at' && key !== 'synced' && key !== 'synced_at') {
                const value = settingsData[key];
                if (value !== null && value !== undefined && value !== '') {
                    formData.append(key, value);
                }
            }
        });

        const response = await axios.put(route('settings.update'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.status === 200 || response.status === 204) {
            await offlineStorage.markSettingsSynced(settingsData.id);
            return true;
        }

        throw new Error('Failed to sync settings');
    }

    /**
     * Obtenir le statut de synchronisation
     */
    async getSyncStatus() {
        const pendingProducts = await offlineStorage.getPendingProducts();
        const pendingCategories = await offlineStorage.getPendingCategories();
        const pendingSettings = await offlineStorage.getPendingSettings();
        return {
            isSyncing: this.isSyncing,
            pendingProducts: pendingProducts.length,
            pendingCategories: pendingCategories.length,
            pendingSettings: pendingSettings.length,
            pendingCount: pendingProducts.length + pendingCategories.length + pendingSettings.length,
            isOnline: navigator.onLine
        };
    }
}

export default new SyncService();
