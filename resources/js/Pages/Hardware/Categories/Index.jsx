import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import HardwareCategoryDrawer from '@/Components/Hardware/CategoryDrawer';
import { Search, Plus, Edit, Trash2, Tag, RefreshCw, Upload, X, XCircle } from 'lucide-react';
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

    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [confirmingImport, setConfirmingImport] = useState(false);

    const canView = permissions?.view !== false;
    const canCreate = permissions?.create !== false;
    const canUpdate = permissions?.update !== false;
    const canDelete = permissions?.delete !== false;
    const canImport = permissions?.import !== false;

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
                        {canImport && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setImportOpen(true);
                                    setImportFile(null);
                                    setImportPreview(null);
                                }}
                                className="border-blue-500 text-blue-600 hover:bg-blue-50 dark:border-blue-600 dark:text-blue-300 dark:hover:bg-blue-900/30"
                            >
                                <Upload className="h-4 w-4 mr-2" /> Importer
                            </Button>
                        )}
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
                    <Card className="mb-6 bg-white dark:bg-gray-800 shadow-md dark:shadow-lg dark:shadow-gray-900/50">
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

                    <Card className="bg-white dark:bg-gray-800 shadow-md dark:shadow-lg dark:shadow-gray-900/50">
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

            {canImport && (
                <Modal
                    show={importOpen}
                    onClose={() => {
                        setImportOpen(false);
                        setImportFile(null);
                        setImportPreview(null);
                    }}
                    maxWidth="2xl"
                >
                    <div className="p-6">
                        <div className="flex justify-between items-start mb-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Importer des catégories (Hardware)
                            </h3>
                            <button
                                type="button"
                                onClick={() => setImportOpen(false)}
                                className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Importez vos catégories via un fichier Excel (.xlsx) ou CSV. Téléchargez le modèle pour respecter la structure requise.
                        </p>
                        <div className="space-y-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                    Colonne obligatoire : <strong>nom</strong>. Les autres colonnes sont optionnelles.
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        window.location.href = route(`${routePrefix}.categories.import.template`);
                                    }}
                                >
                                    Télécharger le modèle
                                </Button>
                            </div>

                            <form
                                onSubmit={async (e) => {
                                    e.preventDefault();
                                    if (!importFile) {
                                        toast.error('Veuillez sélectionner un fichier.');
                                        return;
                                    }
                                    setPreviewLoading(true);
                                    setImportPreview(null);
                                    try {
                                        const formData = new FormData();
                                        formData.append('file', importFile);
                                        const res = await axios.post(route(`${routePrefix}.categories.import.preview`), formData, {
                                            headers: { 'Content-Type': 'multipart/form-data' },
                                        });
                                        setImportPreview(res.data);
                                    } catch (err) {
                                        const msg = err.response?.data?.message || 'Erreur lors de l\'aperçu.';
                                        toast.error(msg);
                                    } finally {
                                        setPreviewLoading(false);
                                    }
                                }}
                                className="space-y-4"
                            >
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fichier
                                    </label>
                                    <input
                                        type="file"
                                        accept=".xlsx,.csv,.txt"
                                        onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400"
                                    />
                                </div>
                                <div className="flex justify-end gap-2">
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={!importFile || previewLoading}
                                    >
                                        {previewLoading ? 'Analyse en cours...' : 'Générer un aperçu'}
                                    </Button>
                                </div>
                            </form>

                            {importPreview && (
                                <div className="space-y-4 mt-4">
                                    <div className="flex flex-wrap items-center gap-4 text-sm">
                                        <span className="flex items-center gap-1 text-gray-700 dark:text-gray-200">
                                            Total lignes : <strong>{importPreview.total}</strong>
                                        </span>
                                        <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                            Valides : <strong>{importPreview.valid}</strong>
                                        </span>
                                        <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                            En erreur : <strong>{importPreview.invalid}</strong>
                                        </span>
                                    </div>

                                    {importPreview.sample && importPreview.sample.header && importPreview.sample.header.length > 0 && (
                                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto max-h-64">
                                            <table className="min-w-full text-xs">
                                                <thead className="bg-gray-50 dark:bg-gray-800">
                                                    <tr>
                                                        {importPreview.sample.header.map((h, idx) => (
                                                            <th
                                                                key={idx}
                                                                className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300"
                                                            >
                                                                {h || `Col ${idx + 1}`}
                                                            </th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                                    {importPreview.sample.rows.map((row, rIdx) => (
                                                        <tr key={rIdx} className="bg-white dark:bg-gray-900">
                                                            {row.map((cell, cIdx) => (
                                                                <td key={cIdx} className="px-3 py-1 text-gray-700 dark:text-gray-200">
                                                                    {cell}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}

                                    {importPreview.errors && importPreview.errors.length > 0 && (
                                        <div className="border border-red-200 dark:border-red-700 rounded-lg p-3 max-h-40 overflow-y-auto bg-red-50 dark:bg-red-900/20">
                                            <div className="flex items-center gap-2 mb-2 text-sm font-semibold text-red-700 dark:text-red-300">
                                                <XCircle className="h-4 w-4" />
                                                Lignes en erreur (non importées)
                                            </div>
                                            <ul className="text-xs text-red-700 dark:text-red-300 space-y-1">
                                                {importPreview.errors.map((err, idx) => (
                                                    <li key={idx}>
                                                        {err.line && <strong>Ligne {err.line} :</strong>}{' '}
                                                        {err.field && <span>[{err.field}] </span>}
                                                        {err.message}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setImportOpen(false)}
                                        >
                                            Annuler
                                        </Button>
                                        <Button
                                            type="button"
                                            disabled={!importFile || confirmingImport}
                                            onClick={async () => {
                                                if (!importFile) {
                                                    toast.error('Veuillez sélectionner un fichier.');
                                                    return;
                                                }
                                                setConfirmingImport(true);
                                                try {
                                                    const formData = new FormData();
                                                    formData.append('file', importFile);
                                                    const res = await axios.post(route(`${routePrefix}.categories.import`), formData, {
                                                        headers: { 'Content-Type': 'multipart/form-data' },
                                                    });
                                                    if (res.data.success > 0) {
                                                        toast.success(`${res.data.success} catégorie(s) importée(s) avec succès.`);
                                                        setImportOpen(false);
                                                        setImportFile(null);
                                                        setImportPreview(null);
                                                        router.reload({ only: ['categories'] });
                                                    }
                                                    if (res.data.failed > 0 && res.data.errors?.length) {
                                                        toast.error(`${res.data.failed} ligne(s) en erreur.`);
                                                    }
                                                } catch (err) {
                                                    const msg = err.response?.data?.message || 'Erreur lors de l\'import.';
                                                    toast.error(msg);
                                                } finally {
                                                    setConfirmingImport(false);
                                                }
                                            }}
                                        >
                                            {confirmingImport ? 'Import en cours...' : "Confirmer l'importation"}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}
