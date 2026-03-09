import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Package, Search, ShoppingCart, ExternalLink, Upload, Plus, Download, Pencil, Trash2, Power, AlertTriangle, MoreVertical } from 'lucide-react';
import ImportModal from '@/Components/ImportModal';
import EcommerceProductDrawer from '@/Components/Ecommerce/ProductDrawer';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';
import EcommerceActionButton from '@/Components/Ecommerce/EcommerceActionButton';
import Dropdown from '@/Components/Dropdown';
import axios from 'axios';
import { toast } from 'react-hot-toast';

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency || 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

export default function EcommerceProductsIndex({ products = [], categories = [], filters = {} }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        if (permissions.includes('*')) return true;
        return permissions.includes(permission);
    };

    const canManageCommerceProducts =
        hasPermission('ecommerce.product.manage') ||
        hasPermission('ecommerce.product.create') ||
        hasPermission('ecommerce.product.update') ||
        hasPermission('module.ecommerce');
    const canDelete = hasPermission('ecommerce.product.delete') || hasPermission('ecommerce.product.manage') || hasPermission('module.ecommerce');
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [confirmingImport, setConfirmingImport] = useState(false);
    const [productDrawerOpen, setProductDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [togglingStatus, setTogglingStatus] = useState({});
    const [togglingPublish, setTogglingPublish] = useState({});

    const handleCreate = () => {
        setEditingProduct(null);
        setProductDrawerOpen(true);
    };

    const handleEdit = (product) => {
        setEditingProduct(product);
        setProductDrawerOpen(true);
    };

    const handleDrawerClose = () => {
        setProductDrawerOpen(false);
        setEditingProduct(null);
    };

    const handleToggleStatus = async (product) => {
        setTogglingStatus((prev) => ({ ...prev, [product.id]: true }));
        try {
            const response = await axios.post(route('ecommerce.products.toggle-status', product.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload({ only: ['products'] });
            } else {
                toast.error(response.data.message || 'Erreur lors du changement de statut.');
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors du changement de statut.');
        } finally {
            setTogglingStatus((prev) => ({ ...prev, [product.id]: false }));
        }
    };

    const handleTogglePublish = async (product) => {
        setTogglingPublish((prev) => ({ ...prev, [product.id]: true }));
        try {
            const response = await axios.post(route('ecommerce.products.toggle-publish', product.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload({ only: ['products'] });
            } else {
                toast.error(response.data.message || 'Erreur lors du changement de publication.');
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors du changement de publication.');
        } finally {
            setTogglingPublish((prev) => ({ ...prev, [product.id]: false }));
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
                                    Êtes-vous sûr de vouloir supprimer « {product.name} » ? Cette action est irréversible.
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={() => {
                                    toast.dismiss(t.id);
                                    router.delete(route('ecommerce.products.destroy', product.id), {
                                        preserveScroll: true,
                                        onSuccess: () => toast.success('Produit supprimé'),
                                        onError: (e) =>
                                            toast.error(e?.message || 'Erreur lors de la suppression du produit.'),
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
            const res = await axios.post(route('ecommerce.products.import.preview'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setImportPreview(res.data);
        } catch (err) {
            const msg = err.response?.data?.message || "Erreur lors de l'aperçu.";
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
            const res = await axios.post(route('ecommerce.products.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (res.data.success > 0) {
                toast.success(`${res.data.success} produit(s) importé(s) avec succès.`);
                setImportOpen(false);
                setImportFile(null);
                setImportPreview(null);
                router.reload();
            }
            if (res.data.failed > 0 && res.data.errors?.length) {
                toast.error(`${res.data.failed} ligne(s) en erreur.`);
            }
        } catch (err) {
            toast.error(err.response?.data?.message || "Erreur lors de l'import des produits.");
        } finally {
            setConfirmingImport(false);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const form = e.target;
        const search = form.search?.value || '';
        const categoryId = form.category_id?.value || '';
        router.get(route('ecommerce.products.index'), { search: search || undefined, category_id: categoryId || undefined }, { preserveState: true });
    };

    const getCategoryName = (id) => categories.find((c) => c.id === id)?.name ?? '—';

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Produits E-commerce" icon={Package}>
                    <EcommerceActionButton
                        icon={Download}
                        label="Export Excel"
                        variant="outline"
                        className="border-emerald-500 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                        onClick={() => window.open(route('ecommerce.exports.products.excel'), '_blank')}
                    />
                    <EcommerceActionButton
                        icon={Download}
                        label="Export PDF"
                        variant="outline"
                        className="border-red-500 text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-300 dark:hover:bg-red-900/20"
                        onClick={() => window.open(route('ecommerce.exports.products.pdf'), '_blank')}
                    />
                    <EcommerceActionButton icon={Upload} label="Importer" variant="outline" onClick={handleOpenImport} />
                    <Button asChild variant="outline" size="sm" className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0">
                        <Link href={route('ecommerce.catalog.index')} title="Voir le catalogue">
                            <ShoppingCart className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Voir le catalogue</span>
                        </Link>
                    </Button>
                    {canManageCommerceProducts && (
                        <EcommerceActionButton icon={Plus} label="Nouveau produit" onClick={handleCreate} />
                    )}
                </EcommercePageHeader>
            }
        >
            <Head title="Produits - E-commerce" />

            <div className="py-6 space-y-4">
                <form onSubmit={handleSearch} className="flex flex-wrap gap-3 items-center">
                    <Input
                        name="search"
                        defaultValue={filters.search}
                        placeholder="Rechercher par nom, SKU..."
                        className="max-w-xs"
                    />
                    <select
                        name="category_id"
                        defaultValue={filters.category_id || ''}
                        className="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                    >
                        <option value="">Toutes les catégories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                    <Button type="submit" size="sm" className="inline-flex items-center gap-2">
                        <Search className="h-4 w-4 shrink-0" />
                        <span>Filtrer</span>
                    </Button>
                </form>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            {products.length} produit(s)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {products.length === 0 ? (
                            <p className="text-gray-500 dark:text-gray-400 text-center py-8">
                                Aucun produit trouvé. Les produits sont gérés via le module Commerce (GlobalCommerce).
                            </p>
                        ) : (
                            <>
                                {/* Mobile: cartes */}
                                <div className="md:hidden space-y-3">
                                    {products.map((p) => (
                                        <div
                                            key={p.id}
                                            className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-600 shadow-sm overflow-hidden"
                                        >
                                            <div className="flex gap-3 p-4">
                                                <div className="relative shrink-0">
                                                    {p.image_url ? (
                                                        <img src={p.image_url} alt="" className="w-16 h-16 object-cover rounded-lg" />
                                                    ) : (
                                                        <div className="w-16 h-16 bg-gray-200 dark:bg-slate-600 rounded-lg flex items-center justify-center">
                                                            <Package className="h-8 w-8 text-gray-400" />
                                                        </div>
                                                    )}
                                                    {p.is_active && (
                                                        <span className="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-800" />
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-1">
                                                                <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                    {p.name}
                                                                </p>
                                                                {p.is_published_ecommerce && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="text-[10px] border-emerald-300 text-emerald-700 bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200 dark:bg-emerald-900/40"
                                                                    >
                                                                        Publié
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <p className="text-xs text-gray-500 dark:text-gray-400">{p.sku}</p>
                                                        </div>
                                                        {canManageCommerceProducts && (
                                                            <Dropdown>
                                                                <Dropdown.Trigger>
                                                                    <button
                                                                        type="button"
                                                                        className="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                                                        aria-label="Actions"
                                                                    >
                                                                        <MoreVertical className="h-5 w-5" />
                                                                    </button>
                                                                </Dropdown.Trigger>
                                                                <Dropdown.Content align="right" width="48" contentClasses="py-1 px-1 bg-white dark:bg-gray-800">
                                                                    <div className="flex items-center gap-0">
                                                                    <Link
                                                                        href={route('ecommerce.catalog.show', p.id)}
                                                                        className="flex items-center justify-center w-10 h-10 text-amber-600 dark:text-amber-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                                                        title="Voir"
                                                                    >
                                                                        <ExternalLink className="h-4 w-4" />
                                                                    </Link>
                                                                    <button
                                                                        type="button"
                                                                        className="flex items-center justify-center w-10 h-10 text-blue-600 dark:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                                                        onClick={() => handleEdit(p)}
                                                                        title="Modifier"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        className="flex items-center justify-center w-10 h-10 text-emerald-600 dark:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                                                        onClick={() => handleToggleStatus(p)}
                                                                        disabled={togglingStatus[p.id]}
                                                                        title={p.is_active ? 'Désactiver' : 'Activer'}
                                                                    >
                                                                        <Power className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        className="flex items-center justify-center w-10 h-10 text-amber-600 dark:text-amber-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded disabled:opacity-50"
                                                                        onClick={() => handleTogglePublish(p)}
                                                                        disabled={togglingPublish[p.id]}
                                                                        title={p.is_published_ecommerce ? 'Retirer de la boutique' : 'Publier sur la boutique'}
                                                                    >
                                                                        <Upload className="h-4 w-4" />
                                                                    </button>
                                                                    {canDelete && (
                                                                        <button
                                                                            type="button"
                                                                            className="flex items-center justify-center w-10 h-10 text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                                                            onClick={() => handleDelete(p)}
                                                                            title="Supprimer"
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </button>
                                                                    )}
                                                                    </div>
                                                                </Dropdown.Content>
                                                            </Dropdown>
                                                        )}
                                                    </div>
                                                    <Badge variant="secondary" className="text-[10px] mt-1">
                                                        {getCategoryName(p.category_id)}
                                                    </Badge>
                                                    <div className="mt-2 flex items-baseline justify-between">
                                                        <span className="font-semibold text-gray-900 dark:text-white">
                                                            {formatCurrency(p.sale_price, p.currency)}
                                                        </span>
                                                        <span className="text-xs text-gray-500 dark:text-gray-400">
                                                            {p.stock} en stock
                                                        </span>
                                                    </div>
                                                    <Link
                                                        href={route('ecommerce.catalog.show', p.id)}
                                                        className="text-amber-600 dark:text-amber-400 hover:opacity-80 mt-1 inline-flex items-center p-1 rounded"
                                                        title="Voir"
                                                    >
                                                        <ExternalLink className="h-4 w-4" />
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop: tableau */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-2">Image</th>
                                                <th className="text-left py-3 px-2">SKU / Nom</th>
                                                <th className="text-left py-3 px-2">Type</th>
                                                <th className="text-left py-3 px-2">Prix</th>
                                                <th className="text-left py-3 px-2">Stock</th>
                                                <th className="text-left py-3 px-2">Statut</th>
                                                <th className="text-left py-3 px-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {products.map((p) => (
                                                <tr key={p.id} className="border-b border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                                    <td className="py-3 px-2">
                                                        {p.image_url ? (
                                                            <img src={p.image_url} alt="" className="w-12 h-12 object-cover rounded" />
                                                        ) : (
                                                            <div className="w-12 h-12 bg-gray-200 dark:bg-slate-600 rounded flex items-center justify-center">
                                                                <Package className="h-6 w-6 text-gray-400" />
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-2">
                                                        <div className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                            <span>{p.name}</span>
                                                            {p.is_published_ecommerce && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="text-[10px] border-emerald-300 text-emerald-700 bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200 dark:bg-emerald-900/40"
                                                                >
                                                                    Publié sur la boutique
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-gray-500">{p.sku}</div>
                                                    </td>
                                                    <td className="py-3 px-2">
                                                        <Badge variant={p.product_type === 'digital' ? 'default' : 'secondary'} className="text-xs">
                                                            {p.product_type || 'physical'}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 px-2 font-medium">
                                                        {formatCurrency(p.sale_price, p.currency)}
                                                    </td>
                                                    <td className="py-3 px-2">{p.stock}</td>
                                                    <td className="py-3 px-2 space-y-1">
                                                        <div>
                                                            <Badge variant={p.is_active ? 'default' : 'destructive'}>
                                                                {p.is_active ? 'Actif' : 'Inactif'}
                                                            </Badge>
                                                        </div>
                                                        <div>
                                                            <Badge
                                                                variant={p.is_published_ecommerce ? 'default' : 'outline'}
                                                                className="text-[10px]"
                                                            >
                                                                {p.is_published_ecommerce ? 'Publié' : 'Non publié'}
                                                            </Badge>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-2">
                                                        <div className="flex items-center gap-1">
                                                            <Link
                                                                href={route('ecommerce.catalog.show', p.id)}
                                                                className="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded"
                                                                title="Voir"
                                                            >
                                                                <ExternalLink className="h-4 w-4" />
                                                            </Link>
                                                            {canManageCommerceProducts && (
                                                                <>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleEdit(p)}
                                                                        className="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded"
                                                                        title="Modifier"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleToggleStatus(p)}
                                                                        disabled={togglingStatus[p.id]}
                                                                        className="p-2 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded disabled:opacity-50"
                                                                        title={p.is_active ? 'Désactiver' : 'Activer'}
                                                                    >
                                                                        <Power className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleTogglePublish(p)}
                                                                        disabled={togglingPublish[p.id]}
                                                                        className="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded disabled:opacity-50"
                                                                        title={p.is_published_ecommerce ? 'Retirer de la boutique' : 'Publier sur la boutique'}
                                                                    >
                                                                        <Upload className="h-4 w-4" />
                                                                    </button>
                                                                    {canDelete && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => handleDelete(p)}
                                                                            className="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                                                                            title="Supprimer"
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </button>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); setImportPreview(null); }}
                title="Importer des produits"
                summaryItems={[
                    'Importez vos produits via un fichier Excel (.xlsx) ou CSV.',
                    'Colonnes obligatoires : nom, sku, categorie, prix_vente. Consultez le modèle pour le format complet.',
                    'Ne pas modifier les en-têtes du modèle.',
                ]}
                examples={[
                    { values: { nom: 'Produit A', sku: 'SKU001', categorie: 'Électronique', prix_vente: '10000' } },
                ]}
                templateUrl={route('ecommerce.products.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onGeneratePreview={handleGeneratePreview}
                previewLoading={previewLoading}
                preview={importPreview}
                onConfirmImport={handleConfirmImport}
                confirmingImport={confirmingImport}
            />

            {canManageCommerceProducts && (
                <>
                    <div className="md:hidden fixed bottom-20 right-4 z-30">
                        <Button
                            onClick={handleCreate}
                            className="h-14 w-14 rounded-full bg-amber-500 hover:bg-amber-600 text-white shadow-lg"
                            size="icon"
                            title="Nouveau produit"
                        >
                            <Plus className="h-6 w-6" />
                        </Button>
                    </div>
                    <EcommerceProductDrawer
                    isOpen={productDrawerOpen}
                    onClose={handleDrawerClose}
                    product={editingProduct}
                    categories={categories}
                    />
                </>
            )}
        </AppLayout>
    );
}
