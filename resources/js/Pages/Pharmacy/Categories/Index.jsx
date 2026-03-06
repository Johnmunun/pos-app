import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import ImportModal from '@/Components/ImportModal';
import CategoryDrawer from '@/Components/Pharmacy/CategoryDrawer';
import { Pagination } from '@/Components/ui/pagination';
import { 
  Search, 
  Plus, 
  Edit, 
  Trash2, 
  Tag,
  RefreshCw,
  FileDown,
  Upload,
  X,
  XCircle,
  CheckCircle,
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

export default function CategoriesIndex({ auth, categories, pagination, filters, permissions, routePrefix = 'pharmacy' }) {
    const { url } = usePage();
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);

    // Import (Pharmacy uniquement)
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [confirmingImport, setConfirmingImport] = useState(false);

    // Vérifier les permissions
    const canView = permissions?.view || false;
    const canCreate = permissions?.create || false;
    const canUpdate = permissions?.update || false;
    const canDelete = permissions?.delete || false;
    const canImport = routePrefix === 'pharmacy' && (permissions?.import || false);

    // Si pas de permission view, rediriger
    if (!canView) {
        return (
            <AppLayout>
                <Head title="Accès refusé" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <Card className="bg-white dark:bg-gray-800">
                            <CardContent className="p-6 text-center">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Accès refusé
                                </h2>
                                <p className="text-gray-600 dark:text-gray-400">
                                    Vous n'avez pas la permission de voir les catégories.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route(`${routePrefix}.categories.index`), {
            search: searchTerm
        });
    };

    const handleCreate = () => {
        if (!canCreate) {
            toast.error('Vous n\'avez pas la permission de créer une catégorie');
            return;
        }
        setEditingCategory(null);
        setDrawerOpen(true);
    };

    const handleEdit = (category) => {
        if (!canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier une catégorie');
            return;
        }
        setEditingCategory(category);
        setDrawerOpen(true);
    };

    const handleDelete = (category) => {
        if (!canDelete) {
            toast.error('Vous n\'avez pas la permission de supprimer une catégorie');
            return;
        }

        toast.custom((t) => (
            <div className={`${t.visible ? 'animate-enter' : 'animate-leave'} max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}>
                <div className="flex-1 w-0 p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <Trash2 className="h-6 w-6 text-red-600" />
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                Supprimer la catégorie
                            </p>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Êtes-vous sûr de vouloir supprimer "{category.name}" ? Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 flex space-x-3">
                        <button
                            onClick={async () => {
                                toast.dismiss(t.id);
                                try {
                                    router.delete(route(`${routePrefix}.categories.destroy`, category.id), {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            // Le toast sera affiché par FlashMessages depuis le backend
                                        },
                                        onError: (errors) => {
                                            if (errors.message) {
                                                toast.error(errors.message);
                                            }
                                        }
                                    });
                                } catch (error) {
                                    toast.error('Erreur lors de la suppression');
                                }
                            }}
                            className="flex-1 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition"
                        >
                            Supprimer
                        </button>
                        <button
                            onClick={() => toast.dismiss(t.id)}
                            className="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        ), {
            duration: Infinity,
        });
    };

    const handleRefresh = () => {
        router.reload();
    };

    const handleOpenImport = () => {
        if (!canImport) {
            toast.error('Vous n\'avez pas la permission d\'importer des catégories.');
            return;
        }
        setImportOpen(true);
        setImportFile(null);
        setImportPreview(null);
    };

    const handleGeneratePreview = async (e) => {
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
    };

    const handleConfirmImport = async () => {
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
                router.reload();
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
    };

    const handleExportPdf = () => {
        const params = new URLSearchParams();
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        const url = route(`${routePrefix}.categories.export.pdf`) + (params.toString() ? '?' + params.toString() : '');
        window.open(url, '_blank');
    };

    return (
        <AppLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Gestion des Catégories
                    </h2>
                    <div className="flex gap-2">
                        {canView && (
                            <button
                                onClick={handleExportPdf}
                                className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 h-10"
                            >
                                <FileDown className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Exporter PDF</span>
                                <span className="sm:hidden">PDF</span>
                            </button>
                        )}
                        <button
                            onClick={handleRefresh}
                            className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 h-10"
                        >
                            <RefreshCw className="h-4 w-4 mr-2" />
                            <span className="hidden sm:inline">Actualiser</span>
                        </button>
                        {canImport && (
                            <button
                                onClick={handleOpenImport}
                                className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                            >
                                <Upload className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Importer</span>
                                <span className="sm:hidden">Import</span>
                            </button>
                        )}
                        {canCreate && (
                            <button
                                onClick={handleCreate}
                                className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Ajouter une catégorie</span>
                                <span className="sm:hidden">Ajouter</span>
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Gestion des Catégories" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Search className="h-5 w-5 mr-2" />
                                Rechercher
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
                                <button
                                    type="submit"
                                    className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md w-full sm:w-auto"
                                >
                                    <Search className="h-4 w-4 mr-2" />
                                    <span>Rechercher</span>
                                </button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Categories List */}
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
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Aucune catégorie
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {searchTerm ? 'Aucune catégorie ne correspond à votre recherche.' : 'Commencez par créer une catégorie.'}
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead className="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Nom
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Description
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Produits
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Statut
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                {categories.map((category) => (
                                                <tr key={category.id} className="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <Tag className="h-5 w-5 text-indigo-600 dark:text-indigo-400 mr-2" />
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {category.name}
                                                                </div>
                                                                {category.parent && (
                                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                        Sous-catégorie de: {category.parent.name}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                                            {category.description || '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge variant="outline">
                                                            {category.products_count || 0}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {category.is_active ? (
                                                            <Badge variant="success">Active</Badge>
                                                        ) : (
                                                            <Badge variant="destructive">Inactive</Badge>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex justify-end gap-2">
                                                            {canUpdate && (
                                                                <button
                                                                    onClick={() => handleEdit(category)}
                                                                    className="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                                                    title="Modifier"
                                                                >
                                                                    <Edit className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                            {canDelete && (
                                                                <button
                                                                    onClick={() => handleDelete(category)}
                                                                    className="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                                    title="Supprimer"
                                                                >
                                                                    <Trash2 className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    {pagination && <Pagination pagination={pagination} filters={filters} />}
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Drawer */}
            <CategoryDrawer
                isOpen={drawerOpen}
                onClose={() => {
                    setDrawerOpen(false);
                    setEditingCategory(null);
                }}
                category={editingCategory}
                categories={categories}
                canCreate={canCreate}
                canUpdate={canUpdate}
                routePrefix={routePrefix}
            />

            {/* Modal Import Catégories */}
            {canImport && (
                <ImportModal
                    show={importOpen}
                    onClose={() => {
                        setImportOpen(false);
                        setImportFile(null);
                        setImportPreview(null);
                    }}
                    title="Importer des catégories"
                    summaryItems={[
                        'Importez vos catégories via un fichier Excel (.xlsx) ou CSV.',
                        'Colonne obligatoire : nom. Les autres colonnes sont optionnelles.',
                        'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                        'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                    ]}
                    examples={[
                        { values: { nom: 'Médicaments génériques' } },
                        { values: { nom: 'Parapharmacie', description: 'Produits de soins' } },
                    ]}
                    templateUrl={route(`${routePrefix}.categories.import.template`)}
                    accept=".xlsx,.csv,.txt"
                    file={importFile}
                    onFileChange={setImportFile}
                    onGeneratePreview={handleGeneratePreview}
                    previewLoading={previewLoading}
                    preview={importPreview}
                    onConfirmImport={handleConfirmImport}
                    confirmingImport={confirmingImport}
                />
            )}
        </AppLayout>
    );
}
