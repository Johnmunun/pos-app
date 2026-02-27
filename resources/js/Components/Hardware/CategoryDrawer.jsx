import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Save, Tag, X } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Drawer création/édition catégorie — Module Quincaillerie.
 * Aucune dépendance aux composants Pharmacy.
 */
export default function HardwareCategoryDrawer({ isOpen, onClose, category = null, categories = [], canCreate = true, canUpdate = true }) {
    const isEditing = !!category;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: category?.name || '',
        description: category?.description || '',
        parent_id: category?.parent_id || '',
        sort_order: category?.sort_order ?? 0,
    });

    useEffect(() => {
        if (category) {
            setData({
                name: category.name || '',
                description: category.description || '',
                parent_id: category.parent_id || '',
                sort_order: category.sort_order ?? 0,
            });
        } else {
            reset();
        }
    }, [category]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (isEditing && !canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier une catégorie');
            return;
        }
        if (!isEditing && !canCreate) {
            toast.error('Vous n\'avez pas la permission de créer une catégorie');
            return;
        }
        try {
            if (isEditing) {
                await put(route('hardware.categories.update', category.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Catégorie mise à jour');
                        onClose();
                        reset();
                    },
                    onError: (err) => err.message && toast.error(err.message),
                });
            } else {
                await post(route('hardware.categories.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Catégorie créée');
                        onClose();
                        reset();
                    },
                    onError: (err) => err.message && toast.error(err.message),
                });
            }
        } catch (error) {
            toast.error('Erreur lors de la soumission');
        }
    };

    const availableCategories = categories.filter((cat) => !category || cat.id !== category.id);

    return (
        <Drawer isOpen={isOpen} onClose={() => { reset(); onClose(); }} title={isEditing ? 'Modifier la catégorie' : 'Ajouter une catégorie'}>
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 overflow-y-auto space-y-4">
                    <div>
                        <Label htmlFor="name">Nom <span className="text-red-500">*</span></Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                            required
                            disabled={processing}
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                    </div>
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1"
                            rows={3}
                            disabled={processing}
                        />
                        {errors.description && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                    </div>
                    <div>
                        <Label htmlFor="parent_id">Catégorie parente</Label>
                        <select
                            id="parent_id"
                            value={data.parent_id}
                            onChange={(e) => setData('parent_id', e.target.value || '')}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                            disabled={processing}
                        >
                            <option value="">Aucune (catégorie principale)</option>
                            {availableCategories.map((cat) => (
                                <option key={cat.id} value={cat.id}>{cat.name}</option>
                            ))}
                        </select>
                        {errors.parent_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.parent_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="sort_order">Ordre d'affichage</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                            className="mt-1"
                            min={0}
                            disabled={processing}
                        />
                        {errors.sort_order && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.sort_order}</p>}
                    </div>
                </div>
                <div className="mt-6 flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button type="button" variant="outline" onClick={onClose} disabled={processing}>
                        <X className="h-4 w-4 mr-2" /> Annuler
                    </Button>
                    <Button type="submit" className="bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-700" disabled={processing}>
                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Enregistrement...' : isEditing ? 'Enregistrer' : 'Créer'}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
