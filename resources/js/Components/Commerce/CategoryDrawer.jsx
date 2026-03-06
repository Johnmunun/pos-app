import React, { useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Tag } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function CommerceCategoryDrawer({ isOpen, onClose, category = null, parentOptions = [] }) {
    const isEditing = !!category;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: category?.name ?? '',
        description: category?.description ?? '',
        parent_id: category?.parent_id ?? '',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
    });

    useEffect(() => {
        if (isEditing && category) {
            setData({
                name: category.name ?? '',
                description: category.description ?? '',
                parent_id: category.parent_id ?? '',
                sort_order: category.sort_order ?? 0,
                is_active: category.is_active ?? true,
            });
        } else if (!isOpen) {
            reset();
        }
    }, [category?.id, isEditing, isOpen]);

    const handleSubmit = (e) => {
        e.preventDefault();

        const payload = {
            ...data,
        };

        if (isEditing) {
            put(route('commerce.categories.update', category.id), {
                preserveScroll: false,
                data: payload,
                onSuccess: () => {
                    toast.success('Catégorie mise à jour');
                    reset();
                    onClose();
                    router.reload({ only: ['categories', 'tree'] });
                },
                onError: (errs) => {
                    const firstError =
                        errs?.message ||
                        (errs && typeof errs === 'object' ? Object.values(errs)[0] : null);
                    if (firstError) {
                        toast.error(String(firstError));
                    } else {
                        toast.error('Erreur lors de la mise à jour de la catégorie.');
                    }
                },
            });
        } else {
            post(route('commerce.categories.store'), {
                preserveScroll: false,
                data: {
                    name: data.name,
                    description: data.description,
                    parent_id: data.parent_id || null,
                    sort_order: data.sort_order ?? 0,
                },
                onSuccess: () => {
                    toast.success('Catégorie créée');
                    reset();
                    onClose();
                    router.reload({ only: ['categories', 'tree'] });
                },
                onError: (errs) => {
                    const firstError =
                        errs?.message ||
                        (errs && typeof errs === 'object' ? Object.values(errs)[0] : null);
                    if (firstError) {
                        toast.error(String(firstError));
                    } else {
                        toast.error('Erreur lors de la création de la catégorie.');
                    }
                },
            });
        }
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    const parentOptionsFiltered = parentOptions.filter(
        (c) => !category || c.id !== category.id
    );

    return (
        <Drawer
            isOpen={isOpen}
            onClose={handleClose}
            title={isEditing ? 'Modifier la catégorie' : 'Nouvelle catégorie'}
            size="md"
        >
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <Tag className="h-5 w-5" />
                        Informations catégorie
                    </h3>
                    <div className="space-y-2">
                        <Label htmlFor="name">Nom *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Nom de la catégorie"
                            className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            required
                        />
                        {errors.name && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.name}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                            placeholder="Description..."
                            className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                        />
                        {errors.description && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="parent_id">Catégorie parente</Label>
                        <select
                            id="parent_id"
                            value={data.parent_id}
                            onChange={(e) => setData('parent_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                        >
                            <option value="">— Aucune —</option>
                            {parentOptionsFiltered.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                        {errors.parent_id && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.parent_id}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="sort_order">Ordre d&apos;affichage</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(e) =>
                                setData('sort_order', parseInt(e.target.value, 10) || 0)
                            }
                            className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                        />
                        {errors.sort_order && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.sort_order}
                            </p>
                        )}
                    </div>
                    {isEditing && (
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-gray-300 dark:border-slate-600"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-200">
                                Catégorie active
                            </span>
                        </label>
                    )}
                </div>

                <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleClose}
                        className="w-full sm:w-auto"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full sm:w-auto"
                    >
                        {isEditing ? 'Enregistrer' : 'Créer'}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}

