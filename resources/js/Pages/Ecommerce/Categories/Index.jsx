import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Package, FolderOpen, ShoppingCart, Upload, Download, Plus } from 'lucide-react';
import ImportModal from '@/Components/ImportModal';
import EcommerceCategoryDrawer from '@/Components/Ecommerce/CategoryDrawer';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';
import EcommerceActionButton from '@/Components/Ecommerce/EcommerceActionButton';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function EcommerceCategoriesIndex({ categories = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        if (permissions.includes('*')) return true;
        return permissions.includes(permission);
    };

    const canManageCategories =
        hasPermission('ecommerce.category.manage') ||
        hasPermission('ecommerce.category.create') ||
        hasPermission('module.ecommerce');

    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [categoryDrawerOpen, setCategoryDrawerOpen] = useState(false);

    const handleOpenImport = () => {
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
            const res = await axios.post(route('ecommerce.categories.import.preview'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setImportPreview(res.data);
        } catch (err) {
            toast.error(err.response?.data?.message || "Erreur lors de l'aperçu.");
        } finally {
            setPreviewLoading(false);
        }
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
            const { data } = await axios.post(route('ecommerce.categories.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import catégories terminé.');
            if (data.errors?.length) {
                console.warn('Erreurs import catégories:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            setImportPreview(null);
            router.reload();
        } catch (err) {
            toast.error(err.response?.data?.message || "Erreur lors de l'import des catégories.");
        } finally {
            setImporting(false);
        }
    };

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Catégories E-commerce" icon={FolderOpen}>
                    <EcommerceActionButton
                        icon={Download}
                        label="Export Excel"
                        variant="outline"
                        className="border-emerald-500 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                        onClick={() => window.open(route('ecommerce.exports.categories.excel'), '_blank')}
                    />
                    <EcommerceActionButton
                        icon={Download}
                        label="Export PDF"
                        variant="outline"
                        className="border-red-500 text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-300 dark:hover:bg-red-900/20"
                        onClick={() => window.open(route('ecommerce.exports.categories.pdf'), '_blank')}
                    />
                    <EcommerceActionButton icon={Upload} label="Importer" variant="outline" onClick={handleOpenImport} />
                    {canManageCategories && (
                        <EcommerceActionButton
                            icon={Plus}
                            label="Nouvelle catégorie"
                            onClick={() => setCategoryDrawerOpen(true)}
                        />
                    )}
                    <Button asChild variant="outline" size="sm" className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0">
                        <Link href={route('ecommerce.catalog.index')} title="Voir le catalogue">
                            <ShoppingCart className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Voir le catalogue</span>
                        </Link>
                    </Button>
                </EcommercePageHeader>
            }
        >
            <Head title="Catégories - E-commerce" />

            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FolderOpen className="h-5 w-5" />
                            {categories.length} catégorie(s)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {categories.length === 0 ? (
                            <p className="text-gray-500 dark:text-gray-400 text-center py-8">
                                Aucune catégorie. Les catégories sont gérées via le module Commerce (GlobalCommerce).
                            </p>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {categories.map((c) => (
                                    <Link
                                        key={c.id}
                                        href={route('ecommerce.catalog.index', { category_id: c.id })}
                                        className="block p-4 rounded-lg border border-gray-200 dark:border-slate-700 hover:border-amber-500 dark:hover:border-amber-500 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                                                <Package className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{c.name}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {c.product_count ?? 0} produit(s)
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); setImportPreview(null); }}
                title="Importer des catégories"
                summaryItems={[
                    'Importez vos catégories via un fichier Excel (.xlsx) ou CSV.',
                    'Colonne obligatoire : nom. Consultez le modèle pour le format.',
                    'Ne pas modifier les en-têtes du modèle.',
                ]}
                examples={[
                    { values: { nom: 'Électronique', description: 'Produits électroniques' } },
                ]}
                templateUrl={route('ecommerce.categories.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onGeneratePreview={handleGeneratePreview}
                previewLoading={previewLoading}
                preview={importPreview}
                onConfirmImport={handleConfirmImport}
                confirmingImport={importing}
            />
            {canManageCategories && (
                <div className="md:hidden fixed bottom-20 right-4 z-30">
                    <Button
                        onClick={() => setCategoryDrawerOpen(true)}
                        className="h-14 w-14 rounded-full bg-amber-500 hover:bg-amber-600 text-white shadow-lg"
                        size="icon"
                        title="Nouvelle catégorie"
                    >
                        <Plus className="h-6 w-6" />
                    </Button>
                </div>
            )}
            <EcommerceCategoryDrawer
                isOpen={categoryDrawerOpen}
                onClose={() => setCategoryDrawerOpen(false)}
                parentOptions={categories}
            />
        </AppLayout>
    );
}
