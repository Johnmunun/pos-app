import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Plus, Search, Tag, FolderTree, Pencil, Trash2, AlertTriangle } from 'lucide-react';
import { toast } from 'react-hot-toast';
import CommerceCategoryDrawer from '@/Components/Commerce/CategoryDrawer';
import ImportModal from '@/Components/ImportModal';
import axios from 'axios';

function CategoryTree({ nodes, level = 0 }) {
    if (!nodes?.length) return null;
    return (
        <ul className={level ? 'ml-4 mt-1 border-l border-gray-200 dark:border-gray-700 pl-3' : 'space-y-1'}>
            {nodes.map((node) => (
                <li key={node.id} className="py-1">
                    <div className="flex items-center gap-2">
                        <Tag className="h-4 w-4 text-gray-400" />
                        <span className="font-medium text-gray-900 dark:text-white">{node.name}</span>
                        {!node.is_active && <Badge variant="secondary">Inactif</Badge>}
                    </div>
                    {node.children?.length > 0 && <CategoryTree nodes={node.children} level={level + 1} />}
                </li>
            ))}
        </ul>
    );
}

export default function CommerceCategoriesIndex({ tree = [], categories = [] }) {
    const [search, setSearch] = useState('');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);

    const filtered = search
        ? categories.filter(
              (c) =>
                  c.name.toLowerCase().includes(search.toLowerCase()) ||
                  (c.description &&
                      c.description.toLowerCase().includes(search.toLowerCase()))
          )
        : categories;

    const handleCreate = () => {
        setEditingCategory(null);
        setDrawerOpen(true);
    };

    const handleEdit = (category) => {
        setEditingCategory(category);
        setDrawerOpen(true);
    };

    const handleOpenImport = () => {
        setImportOpen(true);
        setImportFile(null);
    };

    const handleConfirmImport = async () => {
        if (!importFile) {
            toast.error('Veuillez sélectionner un fichier.');
            return;
        }
        setImporting(true);
        try {
            const formData = new FormData();
            formData.append('file', importFile);
            const { data } = await axios.post(route('commerce.categories.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import catégories terminé.');
            if (data.errors && data.errors.length) {
                console.warn('Erreurs import catégories:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            router.reload({ only: ['categories'] });
        } catch (err) {
            const message =
                err.response?.data?.message ||
                "Erreur lors de l'import des catégories.";
            toast.error(message);
        } finally {
            setImporting(false);
        }
    };

    const handleDelete = (category) => {
        toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}
                >
                    <div className="flex-1 w-0 p-4">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div className="ml-3 flex-1">
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    Confirmer la suppression
                                </p>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Êtes-vous sûr de vouloir supprimer « {category.name} » ? Si des
                                    produits y sont liés, la suppression échouera.
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={() => {
                                    toast.dismiss(t.id);
                                    router.delete(route('commerce.categories.destroy', category.id), {
                                        preserveScroll: true,
                                        onSuccess: () => toast.success('Catégorie supprimée'),
                                        onError: (e) =>
                                            toast.error(
                                                e?.message || 'Erreur lors de la suppression.'
                                            ),
                                    });
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
            ),
            { duration: Infinity }
        );
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Catégories — GlobalCommerce
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            asChild
                            className="inline-flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.categories.pdf')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter PDF</span>
                            </a>
                        </Button>
                        <Button
                            type="button"
                            asChild
                            className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.categories.excel')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter Excel</span>
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="inline-flex items-center gap-2"
                            onClick={handleOpenImport}
                            disabled={importing}
                        >
                            <span>Importer</span>
                        </Button>
                        <Button
                            type="button"
                            onClick={handleCreate}
                            className="inline-flex items-center gap-2"
                        >
                            <Plus className="h-4 w-4" />
                            <span>Nouvelle catégorie</span>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Catégories - Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="flex gap-4">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder="Rechercher..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </div>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <FolderTree className="h-5 w-5 mr-2" /> Arborescence
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CategoryTree nodes={tree} />
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="text-gray-900 dark:text-white">Liste des catégories</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto -mx-2 sm:mx-0">
                                <table className="w-full text-sm bg-white dark:bg-slate-900">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/70">
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Nom
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Description
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Ordre
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Statut
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 w-24">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((c) => (
                                            <tr
                                                key={c.id}
                                                className="border-b border-gray-100 dark:border-gray-800"
                                            >
                                                <td className="py-2 px-2 text-gray-900 dark:text-gray-100">
                                                    {c.name}
                                                </td>
                                                <td className="py-2 px-2 text-gray-500 dark:text-gray-400">
                                                    {c.description || '—'}
                                                </td>
                                                <td className="py-2 px-2">{c.sort_order}</td>
                                                <td className="py-2 px-2">
                                                    {c.is_active ? (
                                                        <Badge variant="default">Actif</Badge>
                                                    ) : (
                                                        <Badge variant="secondary">Inactif</Badge>
                                                    )}
                                                </td>
                                                <td className="py-2 px-2 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEdit(c)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                            onClick={() => handleDelete(c)}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
            <CommerceCategoryDrawer
                isOpen={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                category={editingCategory}
                parentOptions={categories}
            />
            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); }}
                title="Importer des catégories"
                summaryItems={[
                    'Importez vos catégories via un fichier Excel (.xlsx) ou CSV.',
                    'Colonne obligatoire : nom. Les autres colonnes sont optionnelles.',
                    'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                    'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                ]}
                examples={[
                    { values: { nom: 'Électronique', parent_id: '', description: 'Produits électroniques' } },
                    { values: { nom: 'Alimentation', parent_id: '', description: 'Produits alimentaires' } },
                ]}
                templateUrl={route('commerce.categories.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onConfirmImport={handleConfirmImport}
                confirmingImport={importing}
                directImport
            />
        </AppLayout>
    );
}
