import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Drawer from '@/Components/Drawer';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FlashMessages from '@/Components/FlashMessages';
import Swal from 'sweetalert2';
import { Plus, Edit, Trash2, Tag, Package } from 'lucide-react';

export default function Categories({ categories }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState(null);

    const categoryForm = useForm({
        name: '',
        slug: '',
        description: '',
        parent_id: '',
        sort_order: 0,
        is_active: true,
    });

    const openDrawer = (category = null) => {
        setSelectedCategory(category);
        if (category) {
            categoryForm.setData({
                name: category.name,
                slug: category.slug,
                description: category.description || '',
                parent_id: category.parent_id || '',
                sort_order: category.sort_order || 0,
                is_active: category.is_active,
            });
        } else {
            categoryForm.reset();
            categoryForm.setData('is_active', true);
        }
        setDrawerOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (selectedCategory) {
            categoryForm.put(route('categories.update', selectedCategory.id), {
                onSuccess: () => {
                    setDrawerOpen(false);
                    categoryForm.reset();
                    setSelectedCategory(null);
                },
            });
        } else {
            categoryForm.post(route('categories.store'), {
                onSuccess: () => {
                    setDrawerOpen(false);
                    categoryForm.reset();
                },
            });
        }
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('categories.destroy', id));
            }
        });
    };

    // Filter root categories and children
    const rootCategories = categories.filter(c => !c.parent_id);
    const getChildren = (parentId) => categories.filter(c => c.parent_id === parentId);

    return (
        <AppLayout
            header={
                <div className="flex flex-row justify-between items-center gap-4">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Catégories de produits
                    </h2>
                    <button
                        onClick={() => openDrawer()}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md whitespace-nowrap"
                    >
                        <Plus className="h-4 w-4" />
                        <span className="hidden sm:inline">Ajouter une catégorie</span>
                        <span className="sm:hidden">Ajouter</span>
                    </button>
                </div>
            }
        >
            <Head title="Catégories" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <FlashMessages />

                    {categories.length > 0 ? (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Nom
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Parent
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Produits
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Ordre
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {categories.map((category) => (
                                            <tr key={category.id} className={category.parent_id ? 'bg-gray-50 dark:bg-gray-900/50' : ''}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        {category.parent_id && <span className="text-gray-400">└─</span>}
                                                        <Tag className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {category.name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {category.parent_name || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    <div className="flex items-center gap-1">
                                                        <Package className="h-4 w-4" />
                                                        {category.products_count}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {category.sort_order}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {category.is_active ? (
                                                        <span className="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded text-xs">
                                                            Active
                                                        </span>
                                                    ) : (
                                                        <span className="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400 rounded text-xs">
                                                            Inactive
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <button
                                                            onClick={() => openDrawer(category)}
                                                            className="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                                                            title="Modifier"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(category.id)}
                                                            className="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                            title="Supprimer"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <Tag className="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
                            <p className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucune catégorie
                            </p>
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                Créez votre première catégorie pour organiser vos produits.
                            </p>
                            <button
                                onClick={() => openDrawer()}
                                className="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md"
                            >
                                <Plus className="h-5 w-5" />
                                Créer une catégorie
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* Category Drawer */}
            <Drawer
                isOpen={drawerOpen}
                onClose={() => {
                    setDrawerOpen(false);
                    categoryForm.reset();
                    setSelectedCategory(null);
                }}
                title={selectedCategory ? 'Modifier la catégorie' : 'Ajouter une catégorie'}
                size="md"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Name */}
                    <div>
                        <InputLabel htmlFor="name" value="Nom" />
                        <TextInput
                            id="name"
                            type="text"
                            value={categoryForm.data.name}
                            onChange={(e) => categoryForm.setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Nom de la catégorie"
                            required
                        />
                        <InputError message={categoryForm.errors.name} className="mt-2" />
                    </div>

                    {/* Slug */}
                    <div>
                        <InputLabel htmlFor="slug" value="Slug (optionnel)" />
                        <TextInput
                            id="slug"
                            type="text"
                            value={categoryForm.data.slug}
                            onChange={(e) => categoryForm.setData('slug', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Auto-généré si vide"
                        />
                        <InputError message={categoryForm.errors.slug} className="mt-2" />
                    </div>

                    {/* Description */}
                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <textarea
                            id="description"
                            value={categoryForm.data.description}
                            onChange={(e) => categoryForm.setData('description', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            rows="3"
                            placeholder="Description de la catégorie"
                        />
                        <InputError message={categoryForm.errors.description} className="mt-2" />
                    </div>

                    {/* Parent Category */}
                    <div>
                        <InputLabel htmlFor="parent_id" value="Catégorie parente (optionnel)" />
                        <select
                            id="parent_id"
                            value={categoryForm.data.parent_id}
                            onChange={(e) => categoryForm.setData('parent_id', e.target.value || null)}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">Aucune (catégorie racine)</option>
                            {categories
                                .filter(c => !selectedCategory || c.id !== selectedCategory.id)
                                .map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                        </select>
                        <InputError message={categoryForm.errors.parent_id} className="mt-2" />
                    </div>

                    {/* Sort Order */}
                    <div>
                        <InputLabel htmlFor="sort_order" value="Ordre d'affichage" />
                        <TextInput
                            id="sort_order"
                            type="number"
                            min="0"
                            value={categoryForm.data.sort_order}
                            onChange={(e) => categoryForm.setData('sort_order', parseInt(e.target.value) || 0)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={categoryForm.errors.sort_order} className="mt-2" />
                    </div>

                    {/* Is Active */}
                    <div className="flex items-center">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={categoryForm.data.is_active}
                            onChange={(e) => categoryForm.setData('is_active', e.target.checked)}
                            className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        <InputLabel htmlFor="is_active" value="Catégorie active" className="ml-2" />
                    </div>
                    <InputError message={categoryForm.errors.is_active} className="mt-2" />

                    {/* Submit Button */}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setDrawerOpen(false);
                                categoryForm.reset();
                                setSelectedCategory(null);
                            }}
                            className="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                        >
                            Annuler
                        </button>
                        <PrimaryButton disabled={categoryForm.processing}>
                            {selectedCategory ? 'Modifier' : 'Ajouter'}
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}




