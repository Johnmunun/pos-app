import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    Tag,
    X,
    WifiOff,
    CloudUpload
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import offlineStorage from '@/lib/offlineStorage';
import syncService from '@/lib/syncService';

export default function CategoryDrawer({ isOpen, onClose, category = null, categories = [], canCreate = false, canUpdate = false }) {
    const isEditing = !!category;
    
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: category?.name || '',
        description: category?.description || '',
        parent_id: category?.parent_id || '',
        sort_order: category?.sort_order || 0,
        is_active: category?.is_active !== undefined ? category.is_active : true
    });

    const [offlineStatus, setOfflineStatus] = useState(!navigator.onLine);
    const [syncStatus, setSyncStatus] = useState('idle');

    // Reset form when category changes
    useEffect(() => {
        if (category) {
            setData({
                name: category.name || '',
                description: category.description || '',
                parent_id: category.parent_id || '',
                sort_order: category.sort_order || 0,
                is_active: category.is_active !== undefined ? category.is_active : true
            });
        } else {
            reset();
        }
    }, [category]);

    // Vérifier le statut réseau
    useEffect(() => {
        const handleOnline = () => {
            setOfflineStatus(false);
            // Synchroniser automatiquement les données en attente
            syncService.syncPendingCategories().then(() => {
                setSyncStatus('synced');
            }).catch(() => {
                setSyncStatus('error');
            });
        };

        const handleOffline = () => {
            setOfflineStatus(true);
            setSyncStatus('offline');
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Vérifier les permissions
        if (isEditing && !canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier une catégorie');
            return;
        }

        if (!isEditing && !canCreate) {
            toast.error('Vous n\'avez pas la permission de créer une catégorie');
            return;
        }

        // Si offline, sauvegarder localement
        if (offlineStatus || !navigator.onLine) {
            try {
                const categoryData = {
                    ...data,
                    id: category?.id || `pending_${Date.now()}`,
                    shop_id: null, // Sera rempli lors de la synchronisation
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                };

                await offlineStorage.savePendingCategory(categoryData);
                toast.success('Catégorie enregistrée localement. Synchronisation automatique à la reconnexion.');
                onClose();
                reset();
                
                // Recharger la page pour afficher les données mises à jour
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (error) {
                console.error('Error saving category offline:', error);
                toast.error('Erreur lors de l\'enregistrement local');
            }
            return;
        }

        // Si online, soumettre normalement
        try {
            if (isEditing) {
                await put(route('pharmacy.categories.update', category.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Le toast sera affiché par FlashMessages depuis le backend
                        onClose();
                        reset();
                    },
                    onError: (errors) => {
                        // Afficher uniquement les erreurs de validation
                        if (errors.message) {
                            toast.error(errors.message);
                        }
                    }
                });
            } else {
                await post(route('pharmacy.categories.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Le toast sera affiché par FlashMessages depuis le backend
                        onClose();
                        reset();
                    },
                    onError: (errors) => {
                        // Afficher uniquement les erreurs de validation
                        if (errors.message) {
                            toast.error(errors.message);
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error submitting category:', error);
            toast.error('Erreur lors de la soumission');
        }
    };

    // Filtrer les catégories pour exclure la catégorie en cours d'édition (éviter les boucles)
    const availableCategories = categories.filter(cat => 
        !category || cat.id !== category.id
    );

    return (
        <Drawer isOpen={isOpen} onClose={onClose} title={isEditing ? 'Modifier la catégorie' : 'Ajouter une catégorie'}>
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                {/* Status indicators */}
                <div className="mb-4 flex items-center gap-2 text-sm">
                    {offlineStatus ? (
                        <div className="flex items-center text-amber-600 dark:text-amber-400">
                            <WifiOff className="h-4 w-4 mr-1" />
                            <span>Mode hors ligne</span>
                        </div>
                    ) : syncStatus === 'syncing' ? (
                        <div className="flex items-center text-blue-600 dark:text-blue-400">
                            <CloudUpload className="h-4 w-4 mr-1 animate-spin" />
                            <span>Synchronisation...</span>
                        </div>
                    ) : null}
                </div>

                <div className="flex-1 overflow-y-auto space-y-4">
                    {/* Name */}
                    <div>
                        <Label htmlFor="name" className="text-gray-700 dark:text-gray-300">
                            Nom <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                            required
                            disabled={processing}
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                        )}
                    </div>

                    {/* Description */}
                    <div>
                        <Label htmlFor="description" className="text-gray-700 dark:text-gray-300">
                            Description
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1"
                            rows={3}
                            disabled={processing}
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                        )}
                    </div>

                    {/* Parent Category */}
                    <div>
                        <Label htmlFor="parent_id" className="text-gray-700 dark:text-gray-300">
                            Catégorie parente
                        </Label>
                        <select
                            id="parent_id"
                            value={data.parent_id}
                            onChange={(e) => setData('parent_id', e.target.value || '')}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                            disabled={processing}
                        >
                            <option value="">Aucune (catégorie principale)</option>
                            {availableCategories.map((cat) => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.name}
                                </option>
                            ))}
                        </select>
                        {errors.parent_id && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.parent_id}</p>
                        )}
                    </div>

                    {/* Sort Order */}
                    <div>
                        <Label htmlFor="sort_order" className="text-gray-700 dark:text-gray-300">
                            Ordre d'affichage
                        </Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                            className="mt-1"
                            min="0"
                            disabled={processing}
                        />
                        {errors.sort_order && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.sort_order}</p>
                        )}
                    </div>

                    {/* Active Status */}
                    <div>
                        <Label className="flex items-center space-x-2 text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                                disabled={processing}
                            />
                            <span>Catégorie active</span>
                        </Label>
                        {errors.is_active && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.is_active}</p>
                        )}
                    </div>
                </div>

                {/* Footer Actions */}
                <div className="mt-6 flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="w-full sm:w-auto"
                        disabled={processing}
                    >
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        className="w-full sm:w-auto bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-700"
                        disabled={processing}
                    >
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Enregistrement...' : (isEditing ? 'Enregistrer' : 'Créer')}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
