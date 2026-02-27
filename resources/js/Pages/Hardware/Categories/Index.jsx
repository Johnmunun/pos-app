import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import HardwareCategoryDrawer from '@/Components/Hardware/CategoryDrawer';
import { Search, Plus, Edit, Trash2, Tag, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Pagination pour la liste Hardware (route hardware.categories.index).
 */
function HardwareCategoriesPagination({ pagination, filters }) {
    if (!pagination || pagination.last_page <= 1) return null;
    const { current_page, last_page } = pagination;

    const handlePage = (page) => {
        router.get(route('hardware.categories.index'), { ...filters, page }, { preserveState: true, preserveScroll: true });
    };

    return (
        <div className="flex items-center justify-between px-6 py-3 border-t border-gray-200 dark:border-gray-700">
            <p className="text-sm text-gray-500 dark:text-gray-400">
                Page {current_page} / {last_page}
            </p>
            <div className="flex gap-2">
                <Button variant="outline" size="sm" disabled={current_page <= 1} onClick={() => handlePage(current_page - 1)}>
                    Précédent
                </Button>
                <Button variant="outline" size="sm" disabled={current_page >= last_page} onClick={() => handlePage(current_page + 1)}>
                    Suivant
                </Button>
            </div>
        </div>
    );
}

/**
 * Page liste des catégories — Module Quincaillerie.
 * Vue dédiée, aucun import Pharmacy.
 */
export default function HardwareCategoriesIndex({ categories = [], pagination, filters = {}, permissions = {}, routePrefix = 'hardware' }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);

    const canView = permissions?.view !== false;
    const canCreate = permissions?.create !== false;
    const canUpdate = permissions?.update !== false;
    const canDelete = permissions?.delete !== false;

    if (!canView) {
        return (
            <AppLayout>
                <Head title="Accès refusé" />
                <div className="py-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">Vous n&apos;avez pas la permission de voir les catégories.</p>
                </div>
            </AppLayout>
        );
    }

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('hardware.categories.index'), { search: searchTerm });
    };

    const handleCreate = () => {
        if (!canCreate) return toast.error('Permission refusée');
        setEditingCategory(null);
        setDrawerOpen(true);
    };

    const handleEdit = (category) => {
        if (!canUpdate) return toast.error('Permission refusée');
        setEditingCategory(category);
        setDrawerOpen(true);
    };

    const handleDelete = (category) => {
        if (!canDelete) return toast.error('Permission refusée');
        toast.custom((t) => (
            <div className="max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-4 flex flex-col gap-3">
                <p className="text-sm font-medium text-gray-900 dark:text-white">Supprimer la catégorie</p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    Êtes-vous sûr de vouloir supprimer &quot;{category.name}&quot; ? Cette action est irréversible.
                </p>
                <div className="flex gap-2">
                    <button
                        onClick={() => {
                            toast.dismiss(t.id);
                            router.delete(route('hardware.categories.destroy', category.id), {
                                preserveScroll: true,
                                onSuccess: () => toast.success('Catégorie supprimée'),
                                onError: (err) => toast.error(err?.message || 'Erreur'),
                            });
                        }}
                        className="flex-1 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700"
                    >
                        Supprimer
                    </button>
                    <button onClick={() => toast.dismiss(t.id)} className="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium">
                        Annuler
                    </button>
                </div>
            </div>
        ), { duration: Infinity });
    };

    const handleRefresh = () => router.reload();

    return (
        <AppLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Catégories — Quincaillerie
                    </h2>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={handleRefresh}>
                            <RefreshCw className="h-4 w-4 mr-2" /> Actualiser
                        </Button>
                        {canCreate && (
                            <Button onClick={handleCreate} className="bg-amber-500 hover:bg-amber-600 text-white">
                                <Plus className="h-4 w-4 mr-2" /> Ajouter une catégorie
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Catégories - Quincaillerie" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Search className="h-5 w-5 mr-2" /> Rechercher
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-4">
                                <Input
                                    placeholder="Rechercher par nom ou description..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="flex-1"
                                />
                                <Button type="submit">
                                    <Search className="h-4 w-4 mr-2" /> Rechercher
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="text-gray-900 dark:text-white">
                                Catégories {pagination ? `(${pagination.total})` : `(${categories.length})`}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {categories.length === 0 ? (
                                <div className="text-center py-12">
                                    <Tag className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune catégorie</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {searchTerm ? 'Aucun résultat.' : 'Créez une catégorie pour commencer.'}
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead className="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produits</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                {categories.map((category) => (
                                                    <tr key={category.id} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="flex items-center">
                                                                <Tag className="h-5 w-5 text-amber-600 dark:text-amber-400 mr-2" />
                                                                <div>
                                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">{category.name}</div>
                                                                    {category.parent && (
                                                                        <div className="text-xs text-gray-500 dark:text-gray-400">Sous-catégorie de: {category.parent.name}</div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{category.description || '—'}</div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <Badge variant="outline">{category.products_count ?? 0}</Badge>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            {category.is_active ? <Badge variant="success">Active</Badge> : <Badge variant="destructive">Inactive</Badge>}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            <div className="flex justify-end gap-2">
                                                                {canUpdate && (
                                                                    <Button variant="ghost" size="sm" onClick={() => handleEdit(category)} title="Modifier">
                                                                        <Edit className="h-5 w-5" />
                                                                    </Button>
                                                                )}
                                                                {canDelete && (
                                                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700" onClick={() => handleDelete(category)} title="Supprimer">
                                                                        <Trash2 className="h-5 w-5" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <HardwareCategoriesPagination pagination={pagination} filters={filters} />
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <HardwareCategoryDrawer
                isOpen={drawerOpen}
                onClose={() => { setDrawerOpen(false); setEditingCategory(null); }}
                category={editingCategory}
                categories={categories}
                canCreate={canCreate}
                canUpdate={canUpdate}
            />
        </AppLayout>
    );
}
