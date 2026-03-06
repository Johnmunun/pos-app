import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Plus, Search, Package, Pencil, Trash2, AlertTriangle, Eye, Activity, Upload } from 'lucide-react';
import { toast } from 'react-hot-toast';
import CommerceProductDrawer from '@/Components/Commerce/ProductDrawer';
import ViewProductsModal from '@/Components/Commerce/ViewProductsModal';
import ProductMovementsMacModal from '@/Components/Commerce/ProductMovementsMacModal';
import ProductDetailsModal from '@/Components/Commerce/ProductDetailsModal';
import ImportModal from '@/Components/ImportModal';
import axios from 'axios';

export default function CommerceProductsIndex({ products = [], categories = [], filters = {} }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [categoryId, setCategoryId] = useState(filters?.category_id || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [movementsOpen, setMovementsOpen] = useState(false);
    const [detailsProduct, setDetailsProduct] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('commerce.products.index'), { search, category_id: categoryId || undefined }, { preserveState: true });
    };

    const getCategoryName = (id) => categories.find((c) => c.id === id)?.name ?? '—';

    const handleCreate = () => {
        setEditingProduct(null);
        setDrawerOpen(true);
    };

    const handleEdit = (product) => {
        setEditingProduct(product);
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
            const { data } = await axios.post(route('commerce.products.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import produits terminé.');
            if (data.errors && data.errors.length) {
                console.warn('Erreurs import produits:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            router.reload({ only: ['products'] });
        } catch (err) {
            const message =
                err.response?.data?.message ||
                "Erreur lors de l'import des produits.";
            toast.error(message);
        } finally {
            setImporting(false);
        }
    };

    const handleDelete = (product) => {
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
                                    Êtes-vous sûr de vouloir supprimer « {product.name} » ? Cette action est
                                    irréversible.
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={() => {
                                    toast.dismiss(t.id);
                                    router.delete(route('commerce.products.destroy', product.id), {
                                        preserveScroll: true,
                                        onSuccess: () => toast.success('Produit supprimé'),
                                        onError: (e) =>
                                            toast.error(
                                                e?.message || 'Erreur lors de la suppression du produit.'
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
                        Produits — GlobalCommerce
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="inline-flex items-center gap-2"
                            onClick={() => setViewModalOpen(true)}
                        >
                            <Eye className="h-4 w-4" />
                            <span>View products</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="inline-flex items-center gap-2"
                            onClick={() => setMovementsOpen(true)}
                        >
                            <Activity className="h-4 w-4" />
                            <span>Mouvements</span>
                        </Button>
                        <Button
                            type="button"
                            asChild
                            className="inline-flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.products.pdf')}
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
                                href={route('commerce.exports.products.excel')}
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
                            <span>Nouveau produit</span>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Produits - Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <form onSubmit={handleSearch} className="flex flex-wrap gap-4">
                        <div className="relative flex-1 min-w-[200px]">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder="Rechercher (nom, SKU, code-barres)..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        <select
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Toutes les catégories</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                        <Button type="submit">Filtrer</Button>
                    </form>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" />
                                <span>Liste des produits</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto -mx-2 sm:mx-0">
                                <table className="w-full text-sm bg-white dark:bg-slate-900 min-w-[520px]">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/70">
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                SKU
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Image
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Nom
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Catégorie
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Prix vente
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Stock
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
                                        {products.map((p) => (
                                            <tr key={p.id} className="border-b border-gray-100 dark:border-gray-800">
                                                <td className="py-2 px-2 font-mono text-gray-900 dark:text-gray-100">
                                                    {p.sku}
                                                </td>
                                                <td className="py-2 px-2">
                                                    {p.image_url ? (
                                                        <img
                                                            src={p.image_url}
                                                            alt={p.name}
                                                            loading="lazy"
                                                            className="h-9 w-9 rounded-md object-cover border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900"
                                                        />
                                                    ) : (
                                                        <div className="h-9 w-9 rounded-md border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-slate-800" />
                                                    )}
                                                </td>
                                                <td className="py-2 px-2 text-gray-900 dark:text-gray-100">
                                                    {p.name}
                                                </td>
                                                <td className="py-2 px-2 text-gray-500 dark:text-gray-400">
                                                    {getCategoryName(p.category_id)}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                    {p.sale_price_amount} {p.sale_price_currency}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                    {p.stock}
                                                </td>
                                                <td className="py-2 px-2">
                                                    {p.is_active ? <Badge variant="default">Actif</Badge> : <Badge variant="secondary">Inactif</Badge>}
                                                </td>
                                                <td className="py-2 px-2 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setDetailsProduct(p)}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEdit(p)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                            onClick={() => handleDelete(p)}
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
                            {products.length === 0 && (
                                <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                                    Aucun produit. Créez-en un ou modifiez les filtres.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
            <CommerceProductDrawer
                key={editingProduct?.id ?? (drawerOpen ? 'create' : 'closed')}
                isOpen={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                product={editingProduct}
                categories={categories}
            />
            <ViewProductsModal
                isOpen={viewModalOpen}
                onClose={() => setViewModalOpen(false)}
                products={products}
                categories={categories}
                onEditProduct={(p) => {
                    setEditingProduct(p);
                    setDrawerOpen(true);
                    setViewModalOpen(false);
                }}
            />
            <ProductMovementsMacModal
                isOpen={movementsOpen}
                onClose={() => setMovementsOpen(false)}
            />
            <ProductDetailsModal
                isOpen={!!detailsProduct}
                onClose={() => setDetailsProduct(null)}
                product={detailsProduct}
            />
            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); }}
                title="Importer des produits"
                summaryItems={[
                    'Importez vos produits via un fichier Excel (.xlsx) ou CSV.',
                    'Colonnes obligatoires : sku, name, category_id, sale_price_amount, sale_price_currency.',
                    'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                    'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                ]}
                examples={[
                    { values: { sku: 'PROD001', name: 'Produit A', category_id: '1', sale_price_amount: '1500', sale_price_currency: 'CDF' } },
                    { values: { sku: 'PROD002', name: 'Produit B', category_id: '2', sale_price_amount: '2500', sale_price_currency: 'CDF' } },
                ]}
                templateUrl={route('commerce.products.import.template')}
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
