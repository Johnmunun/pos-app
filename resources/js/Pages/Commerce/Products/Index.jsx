import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Plus, Search, Package, Pencil, Trash2, AlertTriangle, Eye, Activity, Upload, CheckCircle, XCircle, History, Power } from 'lucide-react';
import { toast } from 'react-hot-toast';
import CommerceProductDrawer from '@/Components/Commerce/ProductDrawer';
import ViewProductsModal from '@/Components/Commerce/ViewProductsModal';
import ProductMovementsMacModal from '@/Components/Commerce/ProductMovementsMacModal';
import ProductDetailsModal from '@/Components/Commerce/ProductDetailsModal';
import ImportModal from '@/Components/ImportModal';
import axios from 'axios';
import { formatCurrency } from '@/lib/currency';

export default function CommerceProductsIndex({ products = [], categories = [], filters = {} }) {
    const { shop, auth } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);
    const permissions = auth?.permissions || [];
    const [search, setSearch] = useState(filters?.search || '');
    const [categoryId, setCategoryId] = useState(filters?.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [movementsOpen, setMovementsOpen] = useState(false);
    const [movementsProduct, setMovementsProduct] = useState(null);
    const [detailsProduct, setDetailsProduct] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [confirmingImport, setConfirmingImport] = useState(false);
    const [togglingStatus, setTogglingStatus] = useState({});
    
    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission) || permissions.includes('*');
    };
    
    const canViewMovements = hasPermission('commerce.stock.movement.view') || hasPermission('module.commerce');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('commerce.products.index'), { 
            search, 
            category_id: categoryId || undefined,
            status: selectedStatus || undefined
        }, { preserveState: true });
    };
    
    const handleViewMovements = (product = null) => {
        setMovementsProduct(product);
        setMovementsOpen(true);
    };
    
    const handleToggleStatus = async (product) => {
        setTogglingStatus(prev => ({ ...prev, [product.id]: true }));
        try {
            const response = await axios.post(route('commerce.products.toggle-status', product.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload({ only: ['products'] });
            } else {
                toast.error(response.data.message || 'Erreur lors du changement de statut.');
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors du changement de statut.');
        } finally {
            setTogglingStatus(prev => ({ ...prev, [product.id]: false }));
        }
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
            const res = await axios.post(route('commerce.products.import.preview'), formData, {
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
            const res = await axios.post(route('commerce.products.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (res.data.success > 0) {
                toast.success(`${res.data.success} produit(s) importé(s) avec succès.`);
                setImportOpen(false);
                setImportFile(null);
                setImportPreview(null);
                router.reload({ only: ['products'] });
            }
            if (res.data.failed > 0 && res.data.errors?.length) {
                toast.error(`${res.data.failed} ligne(s) en erreur.`);
            }
        } catch (err) {
            const message =
                err.response?.data?.message ||
                "Erreur lors de l'import des produits.";
            toast.error(message);
        } finally {
            setConfirmingImport(false);
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
                        {canViewMovements && (
                            <Button
                                type="button"
                                variant="outline"
                                className="inline-flex items-center gap-2"
                                onClick={() => handleViewMovements(null)}
                            >
                                <History className="h-4 w-4" />
                                <span>Historique</span>
                            </Button>
                        )}
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
                            disabled={confirmingImport}
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
                    {/* Recherche - Mobile optimisée */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                <Search className="h-4 w-4 sm:h-5 sm:w-5 mr-2" /> 
                                <span className="hidden sm:inline">Recherche</span>
                                <span className="sm:hidden">Rechercher produits, SKU...</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="space-y-3">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Rechercher produits, SKU..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <div className="flex-1">
                                        <select
                                            value={categoryId}
                                            onChange={(e) => setCategoryId(e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500 text-sm py-2"
                                        >
                                            <option value="">Toutes les catégories</option>
                                            {categories.map((c) => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="flex-1 sm:flex-initial sm:w-40">
                                        <select
                                            value={selectedStatus}
                                            onChange={(e) => setSelectedStatus(e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500 text-sm py-2"
                                        >
                                            <option value="">Tous les statuts</option>
                                            <option value="active">Actif</option>
                                            <option value="inactive">Inactif</option>
                                        </select>
                                    </div>
                                    <Button type="submit" className="w-full sm:w-auto">
                                        <Search className="h-4 w-4 mr-2" />
                                        <span className="hidden sm:inline">Filtrer</span>
                                        <span className="sm:hidden">Rechercher</span>
                                    </Button>
                                </div>
                                <div className="md:hidden flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span>Affichage de {products.length} produit{products.length > 1 ? 's' : ''}</span>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" />
                                <span>Liste des produits</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {products.length === 0 ? (
                                <div className="text-center py-12">
                                    <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun produit</h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">Créez votre premier produit.</p>
                                    <Button onClick={handleCreate} className="bg-rose-500 hover:bg-rose-600 text-white">
                                        <Plus className="h-4 w-4 mr-2" /> Ajouter un produit
                                    </Button>
                                </div>
                            ) : (
                                <>
                                    {/* Vue Mobile - Cartes */}
                                    <div className="md:hidden space-y-3">
                                        {products.map((product) => {
                                            const stock = product.stock ?? 0;
                                            const stockStatus = stock <= 0 ? 'out' : stock <= 10 ? 'low' : 'in';
                                            
                                            return (
                                                <div 
                                                    key={product.id} 
                                                    className="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-colors"
                                                >
                                                    <div className="flex items-start gap-3 p-4">
                                                        {/* Image circulaire */}
                                                        <div className="flex-shrink-0">
                                                            <div className="h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden border-2 border-gray-200 dark:border-gray-600">
                                                                {product.image_url ? (
                                                                    <img 
                                                                        src={product.image_url} 
                                                                        alt={product.name}
                                                                        className="h-full w-full object-cover"
                                                                        onError={(e) => {
                                                                            e.target.style.display = 'none';
                                                                        }}
                                                                    />
                                                                ) : (
                                                                    <Package className="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                                                )}
                                                            </div>
                                                        </div>
                                                        
                                                        {/* Contenu principal */}
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-start justify-between gap-2 mb-1">
                                                                <h3 className="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                                                    {product.name}
                                                                </h3>
                                                                <div className="flex items-center gap-1 flex-shrink-0">
                                                                    {canViewMovements && (
                                                                        <button
                                                                            onClick={() => handleViewMovements(product)}
                                                                            className="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                                                            title="Historique"
                                                                        >
                                                                            <History className="h-4 w-4" />
                                                                        </button>
                                                                    )}
                                                                    <button
                                                                        onClick={() => handleToggleStatus(product)}
                                                                        disabled={togglingStatus[product.id]}
                                                                        className={`p-1.5 transition-colors ${
                                                                            product.is_active
                                                                                ? 'text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300'
                                                                                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                                                                        } ${togglingStatus[product.id] ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                                        title={product.is_active ? 'Désactiver' : 'Activer'}
                                                                    >
                                                                        <Power className={`h-4 w-4 ${product.is_active ? 'fill-current' : ''}`} />
                                                                    </button>
                                                                    <button
                                                                        onClick={() => handleEdit(product)}
                                                                        className="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                                        title="Modifier"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        onClick={() => handleDelete(product)}
                                                                        className="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                                                        title="Supprimer"
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            
                                                            <p className="text-base font-bold text-gray-900 dark:text-white mb-1">
                                                                {fmt(Number(product.sale_price_amount || 0))}
                                                            </p>
                                                            
                                                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-2 font-mono">
                                                                SKU: {product.sku}
                                                                {product.unit && (
                                                                    <span className="ml-2 text-gray-400">• {product.unit}</span>
                                                                )}
                                                            </p>
                                                            
                                                            {/* Badges */}
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                {getCategoryName(product.category_id) && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-0">
                                                                        {getCategoryName(product.category_id)}
                                                                    </Badge>
                                                                )}
                                                                {product.is_active ? (
                                                                    stockStatus === 'in' && (
                                                                        <Badge className="text-[10px] px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-0 flex items-center gap-1">
                                                                            <CheckCircle className="h-3 w-3" />
                                                                            En stock
                                                                        </Badge>
                                                                    )
                                                                ) : (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 border-0">
                                                                        Inactif
                                                                    </Badge>
                                                                )}
                                                                {stockStatus === 'low' && product.is_active && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-0 flex items-center gap-1">
                                                                        <AlertTriangle className="h-3 w-3" />
                                                                        Stock bas
                                                                    </Badge>
                                                                )}
                                                                {stockStatus === 'out' && product.is_active && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-0 flex items-center gap-1">
                                                                        <XCircle className="h-3 w-3" />
                                                                        Rupture
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Vue Desktop - Tableau */}
                                    <div className="hidden md:block overflow-x-auto -mx-2 sm:mx-0">
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
                                                Prix achat
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
                                                    <div className="flex flex-col">
                                                        <span>{p.sku}</span>
                                                        {p.unit && (
                                                            <span className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                                {p.unit}
                                                            </span>
                                                        )}
                                                    </div>
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
                                                    {p.purchase_price ? `${fmt(Number(p.purchase_price))}` : '—'}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                    {fmt(Number(p.sale_price_amount || 0))}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                    <span className="font-medium">{p.stock ?? 0}</span>
                                                    {p.minimum_stock !== undefined && p.minimum_stock !== null && (
                                                        <span className="text-gray-500 dark:text-gray-400 text-xs ml-1">
                                                            / {p.minimum_stock}
                                                        </span>
                                                    )}
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
                                                            title="Voir les détails"
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        {canViewMovements && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleViewMovements(p)}
                                                                title="Historique des mouvements"
                                                                className="text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                                            >
                                                                <History className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleToggleStatus(p)}
                                                            disabled={togglingStatus[p.id]}
                                                            title={p.is_active ? 'Désactiver le produit' : 'Activer le produit'}
                                                            className={
                                                                p.is_active
                                                                    ? 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20'
                                                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/20'
                                                            }
                                                        >
                                                            <Power className={`h-4 w-4 ${p.is_active ? 'fill-current' : ''}`} />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEdit(p)}
                                                            title="Modifier"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                            onClick={() => handleDelete(p)}
                                                            title="Supprimer"
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
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* FAB Mobile - Ajouter produit */}
                    <div className="md:hidden fixed bottom-20 right-4 z-30">
                        <Button
                            onClick={handleCreate}
                            className="h-14 w-14 rounded-full bg-rose-500 hover:bg-rose-600 text-white shadow-lg flex items-center justify-center"
                            size="icon"
                        >
                            <Plus className="h-6 w-6" />
                        </Button>
                    </div>
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
                onClose={() => {
                    setMovementsOpen(false);
                    setMovementsProduct(null);
                }}
                productId={movementsProduct?.id || null}
            />
            <ProductDetailsModal
                isOpen={!!detailsProduct}
                onClose={() => setDetailsProduct(null)}
                product={detailsProduct}
            />
            <ImportModal
                show={importOpen}
                onClose={() => { 
                    setImportOpen(false); 
                    setImportFile(null); 
                    setImportPreview(null);
                }}
                title="Importer des produits"
                summaryItems={[
                    'Importez vos produits via un fichier Excel (.xlsx) ou CSV.',
                    'Colonnes obligatoires : sku, nom, categorie, prix_vente.',
                    'Colonnes optionnelles : prix_achat, stock, stock_minimum, devise, actif, unite.',
                    'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                    'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                ]}
                examples={[
                    { values: { sku: 'PROD001', nom: 'Produit A', categorie: 'Électronique', prix_vente: '1500', devise: 'USD', unite: 'PIECE' } },
                    { values: { sku: 'PROD002', nom: 'Produit B', categorie: 'Vêtements', prix_vente: '2500', devise: 'USD', unite: 'CARTON' } },
                ]}
                templateUrl={route('commerce.products.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onGeneratePreview={handleGeneratePreview}
                previewLoading={previewLoading}
                preview={importPreview}
                onConfirmImport={handleConfirmImport}
                confirmingImport={confirmingImport}
            />
        </AppLayout>
    );
}
