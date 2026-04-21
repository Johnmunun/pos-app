import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import ImportModal from '@/Components/ImportModal';
import ProductDrawer from '@/Components/Pharmacy/ProductDrawer';
import ProductMovementsModal from '@/Components/Pharmacy/ProductMovementsModal';
import { 
  Search, 
  Plus, 
  Edit, 
  Trash2, 
  Package,
  AlertTriangle,
  Eye,
  FileText,
  Download,
  Upload,
  X,
  CheckCircle,
  XCircle,
  History,
  Copy
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import { formatCurrency } from '@/lib/currency';

export default function ProductsIndex({ auth, products, categories, filters, canImport = false, routePrefix = 'pharmacy', pageTitle = 'Gestion des Produits' }) {
    const { auth: authPage, depots = [], currentDepot, shop } = usePage().props ?? {};
    const permissions = authPage?.permissions || [];
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);
    
    const hasPermission = (permission) => {
        if (authPage?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };
    
    const canViewMovements = hasPermission('stock.movement.view') || (routePrefix === 'hardware' && hasPermission('hardware.stock.movement.view'));
    
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters?.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailProduct, setDetailProduct] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [confirmingImport, setConfirmingImport] = useState(false);
    const [movementsModalOpen, setMovementsModalOpen] = useState(false);
    const [movementsProduct, setMovementsProduct] = useState(null);
    const [duplicateModalOpen, setDuplicateModalOpen] = useState(false);
    const [duplicateProduct, setDuplicateProduct] = useState(null);
    const [duplicateTargetDepotId, setDuplicateTargetDepotId] = useState('');
    const [duplicateSubmitting, setDuplicateSubmitting] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route(`${routePrefix}.products`), {
            search: searchTerm,
            category_id: selectedCategory,
            status: selectedStatus || undefined
        });
    };

    const handleImport = () => {
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
            const res = await axios.post(route(`${routePrefix}.products.import.preview`), formData, {
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
            const res = await axios.post(route(`${routePrefix}.products.import`), formData, {
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
            setImportPreview((prev) => ({
                ...(prev || {}),
                total: res.data.total,
                valid: res.data.success,
                invalid: res.data.failed,
                // On garde les erreurs détaillées de la preview si disponibles
                errors: (prev && prev.errors) || (res.data.errors || []).map((msg) => ({
                    line: null,
                    field: null,
                    message: msg,
                })),
            }));
        } catch (err) {
            const msg = err.response?.data?.message || "Erreur lors de l'import.";
            toast.error(msg);
        } finally {
            setConfirmingImport(false);
        }
    };

    const handleCreate = () => {
        setEditingProduct(null);
        setDrawerOpen(true);
    };

    const handleEdit = (product) => {
        setEditingProduct(product);
        setDrawerOpen(true);
    };

    const handleView = (product) => {
        setDetailProduct(product);
        setDetailOpen(true);
    };

    const handleViewMovements = (product = null) => {
        setMovementsProduct(product);
        setMovementsModalOpen(true);
    };

    const handleDuplicateToDepot = (product) => {
        setDuplicateProduct(product);
        setDuplicateTargetDepotId('');
        setDuplicateModalOpen(true);
    };

    const handleDuplicateSubmit = async (e) => {
        e.preventDefault();
        if (!duplicateProduct || !duplicateTargetDepotId) {
            toast.error('Veuillez sélectionner un dépôt cible.');
            return;
        }
        setDuplicateSubmitting(true);
        try {
            const res = await axios.post(route(`${routePrefix}.products.duplicate-to-depot`, duplicateProduct.id), {
                target_depot_id: duplicateTargetDepotId,
            });
            if (res.data?.success && res.data?.product_id) {
                toast.success(res.data.message || 'Produit dupliqué avec succès.');
                setDuplicateModalOpen(false);
                setDuplicateProduct(null);
                setDuplicateTargetDepotId('');
                router.visit(route(`${routePrefix}.products.edit`, res.data.product_id));
            }
        } catch (err) {
            const msg = err.response?.data?.message || 'Erreur lors de la duplication.';
            toast.error(msg);
        } finally {
            setDuplicateSubmitting(false);
        }
    };

    const handleDelete = (product) => {
        toast.custom((t) => (
            <div className={`${t.visible ? 'animate-enter' : 'animate-leave'} max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}>
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
                                Êtes-vous sûr de vouloir supprimer "{product.name}" ? Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            onClick={() => {
                                toast.dismiss(t.id);
                                router.delete(route(`${routePrefix}.products.destroy`, product.id), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        toast.success('Produit supprimé avec succès');
                                    },
                                    onError: (errors) => {
                                        toast.error(errors.message || 'Erreur lors de la suppression du produit');
                                    }
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
        ), {
            duration: Infinity,
        });
    };

    const getStatusBadge = (product) => {
        const stock = product.current_stock ?? product.stock ?? 0;
        const minStock = product.minimum_stock ?? 0;
        if (stock <= 0) {
            return <Badge variant="destructive">Out of Stock</Badge>;
        }
        if (stock <= minStock) {
            return <Badge variant="warning">Low Stock</Badge>;
        }
        return <Badge variant="success">In Stock</Badge>;
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        {pageTitle}
                    </h2>
                    <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                        {/* Boutons d'export colorés et bien alignés */}
                        <div className="hidden sm:flex items-center gap-2">
                            <a
                                href={products.length ? route(`${routePrefix}.products.export.pdf`) : '#'}
                                target="_blank"
                                rel="noreferrer"
                                aria-disabled={products.length === 0}
                                className={`inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 px-3 h-10 shadow-sm hover:shadow-md ${
                                    products.length === 0
                                        ? 'opacity-50 cursor-not-allowed pointer-events-none bg-rose-400 text-white/80'
                                        : 'bg-rose-500 text-white hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-700'
                                }`}
                            >
                                <FileText className="h-4 w-4 mr-2" />
                                PDF
                            </a>
                            <a
                                href={products.length ? route(`${routePrefix}.products.export.excel`) : '#'}
                                aria-disabled={products.length === 0}
                                className={`inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 px-3 h-10 shadow-sm hover:shadow-md ${
                                    products.length === 0
                                        ? 'opacity-50 cursor-not-allowed pointer-events-none bg-emerald-400 text-white/80'
                                        : 'bg-emerald-500 text-white hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-700'
                                }`}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Excel
                            </a>
                        </div>
                        {/* Version compacte pour mobile */}
                        <div className="flex sm:hidden items-center gap-2">
                            <a
                                href={products.length ? route(`${routePrefix}.products.export.pdf`) : '#'}
                                target="_blank"
                                rel="noreferrer"
                                aria-disabled={products.length === 0}
                                className={`inline-flex items-center justify-center rounded-md text-xs font-medium px-2 h-8 shadow-sm ${
                                    products.length === 0
                                        ? 'opacity-50 cursor-not-allowed pointer-events-none bg-rose-400 text-white/80'
                                        : 'bg-rose-500 text-white hover:bg-rose-600'
                                }`}
                            >
                                <FileText className="h-3 w-3 mr-1" />
                                PDF
                            </a>
                            <a
                                href={products.length ? route(`${routePrefix}.products.export.excel`) : '#'}
                                aria-disabled={products.length === 0}
                                className={`inline-flex items-center justify-center rounded-md text-xs font-medium px-2 h-8 shadow-sm ${
                                    products.length === 0
                                        ? 'opacity-50 cursor-not-allowed pointer-events-none bg-emerald-400 text-white/80'
                                        : 'bg-emerald-500 text-white hover:bg-emerald-600'
                                }`}
                            >
                                <Download className="h-3 w-3 mr-1" />
                                Excel
                            </a>
                        </div>
                        {canViewMovements && (
                            <button
                                type="button"
                                onClick={() => handleViewMovements(null)}
                                className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 px-3 h-10 shadow-sm hover:shadow-md bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-300 dark:hover:bg-amber-900/70 border border-amber-300 dark:border-amber-700"
                            >
                                <History className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Historique</span>
                            </button>
                        )}
                        {canImport && (
                            <button
                                type="button"
                                onClick={handleImport}
                                className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 px-3 h-10 shadow-sm hover:shadow-md bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700"
                            >
                                <Upload className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Import</span>
                            </button>
                        )}
                        <button
                            onClick={handleCreate}
                            className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            <span className="hidden sm:inline">Nouveau</span>
                            <span className="sm:hidden">Ajouter</span>
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={pageTitle} />
            <div className="py-6 space-y-6">
                    {/* Search and Filters - Mobile optimisée */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                <Search className="h-4 w-4 sm:h-5 sm:w-5 mr-2" /> 
                                <span className="hidden sm:inline">Search Products</span>
                                <span className="sm:hidden">Rechercher produits, SKU...</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="space-y-3">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Rechercher produits, SKU..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <div className="flex-1">
                                        <select
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                            value={selectedCategory}
                                            onChange={(e) => setSelectedCategory(e.target.value)}
                                        >
                                            <option value="">Toutes les catégories</option>
                                            {categories.map(category => (
                                                <option key={category.id} value={category.id}>
                                                    {category.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="flex-1">
                                        <select
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                            value={selectedStatus}
                                            onChange={(e) => setSelectedStatus(e.target.value)}
                                        >
                                            <option value="">Tous les statuts</option>
                                            <option value="active">Actif</option>
                                            <option value="inactive">Inactif</option>
                                        </select>
                                    </div>
                                    <Button type="submit" className="w-full sm:w-auto">
                                        <Search className="h-4 w-4 mr-2" />
                                        <span className="hidden sm:inline">Rechercher</span>
                                        <span className="sm:hidden">Filtrer</span>
                                    </Button>
                                </div>
                                <div className="md:hidden flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span>Affichage de {products.length} produit{products.length > 1 ? 's' : ''}</span>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Products Table */}
                    <Card className="bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" />
                                Products List ({products.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {products.length === 0 ? (
                                <div className="text-center py-12">
                                    <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">No products found</h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">Get started by creating your first product.</p>
                                    <button
                                        onClick={handleCreate}
                                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        Ajouter
                                    </button>
                                </div>
                            ) : (
                                <>
                                    {/* Vue Mobile - Cartes */}
                                    <div className="md:hidden space-y-3">
                                        {products.map((product) => {
                                            const stock = product.current_stock ?? product.stock ?? 0;
                                            const minStock = product.minimum_stock ?? 0;
                                            const stockStatus = stock <= 0 ? 'out' : stock <= minStock ? 'low' : 'in';
                                            
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
                                                                    <button
                                                                        onClick={() => handleEdit(product)}
                                                                        className="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                                        title="Modifier"
                                                                    >
                                                                        <Edit className="h-4 w-4" />
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
                                                                {fmt(Number(product.price_amount || 0))}
                                                            </p>
                                                            
                                                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-2 font-mono">
                                                                SKU: {product.product_code}
                                                            </p>
                                                            
                                                            {/* Badges */}
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                {product.category?.name && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-0">
                                                                        {product.category.name}
                                                                    </Badge>
                                                                )}
                                                                {stockStatus === 'in' && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-0 flex items-center gap-1">
                                                                        <CheckCircle className="h-3 w-3" />
                                                                        En stock
                                                                    </Badge>
                                                                )}
                                                                {stockStatus === 'low' && (
                                                                    <Badge className="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-0 flex items-center gap-1">
                                                                        <AlertTriangle className="h-3 w-3" />
                                                                        Stock bas
                                                                    </Badge>
                                                                )}
                                                                {stockStatus === 'out' && (
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
                                    <div className="hidden md:block overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Product
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Code
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Category
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Price
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Stock
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            {products.map((product) => (
                                                <tr key={product.id} className="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 relative h-10 w-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                                                {product.image_url && (
                                                                    <img
                                                                        src={product.image_url}
                                                                        alt={product.name}
                                                                        className="absolute inset-0 h-10 w-10 object-cover z-[1]"
                                                                        onError={(e) => {
                                                                            e.target.onerror = null;
                                                                            e.target.style.display = 'none';
                                                                        }}
                                                                    />
                                                                )}
                                                                <div className="absolute inset-0 flex items-center justify-center z-0">
                                                                    <Package className="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {product.name}
                                                                </div>
                                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                    {product.description?.substring(0, 50)}...
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.product_code}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.category?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.price_currency} {product.price_amount?.toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <span className="font-medium">{product.current_stock}</span>
                                                        <span className="text-gray-500 dark:text-gray-400"> / {product.minimum_stock}</span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        {getStatusBadge(product)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleView(product)}
                                                                title="Voir le détail"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                            {canViewMovements && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleViewMovements(product)}
                                                                    title="Historique des mouvements"
                                                                    className="text-amber-600 hover:text-amber-700 hover:border-amber-300"
                                                                >
                                                                    <History className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleEdit(product)}
                                                                title="Modifier"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                            {depots?.length >= 1 && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleDuplicateToDepot(product)}
                                                                    title="Dupliquer vers un autre dépôt"
                                                                    className="text-blue-600 hover:text-blue-700 hover:border-blue-300"
                                                                >
                                                                    <Copy className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                onClick={() => handleDelete(product)}
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
                        <button
                            onClick={handleCreate}
                            className="h-14 w-14 rounded-full bg-amber-500 hover:bg-amber-600 text-white shadow-lg flex items-center justify-center transition-colors"
                        >
                            <Plus className="h-6 w-6" />
                        </button>
                    </div>
            </div>

            {/* Modal détail produit */}
            <Modal
                show={detailOpen}
                onClose={() => {
                    setDetailOpen(false);
                    setDetailProduct(null);
                }}
                maxWidth="xl"
            >
                {detailProduct && (
                    <div className="p-6">
                        <div className="flex justify-between items-start mb-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Détail du produit
                            </h3>
                            <button
                                type="button"
                                onClick={() => setDetailOpen(false)}
                                className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <div className="flex gap-6">
                            <div className="flex-shrink-0">
                                <div className="relative h-32 w-32 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    {detailProduct.image_url ? (
                                        <img
                                            src={detailProduct.image_url}
                                            alt={detailProduct.name}
                                            className="absolute inset-0 h-full w-full object-cover"
                                            onError={(e) => {
                                                e.target.onerror = null;
                                                e.target.style.display = 'none';
                                            }}
                                        />
                                    ) : null}
                                    <div className="absolute inset-0 flex items-center justify-center">
                                        <Package className="h-16 w-16 text-gray-400 dark:text-gray-500" />
                                    </div>
                                </div>
                            </div>
                            <div className="flex-1 space-y-3 text-sm">
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Nom</span>
                                    <p className="text-gray-900 dark:text-white font-medium">{detailProduct.name}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Code</span>
                                    <p className="text-gray-900 dark:text-white">{detailProduct.product_code}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Catégorie</span>
                                    <p className="text-gray-900 dark:text-white">{detailProduct.category?.name || '—'}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Prix</span>
                                    <p className="text-gray-900 dark:text-white">
                                        {detailProduct.price_currency} {detailProduct.price_amount?.toFixed(2)}
                                        {detailProduct.cost != null && (
                                            <span className="text-gray-500 dark:text-gray-400 ml-2">
                                                (Revient: {detailProduct.price_currency} {detailProduct.cost?.toFixed(2)})
                                            </span>
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Stock</span>
                                    <p className="text-gray-900 dark:text-white">
                                        {detailProduct.current_stock ?? detailProduct.stock ?? 0} / {detailProduct.minimum_stock ?? 0} min
                                    </p>
                                </div>
                                {detailProduct.description && (
                                    <div>
                                        <span className="font-medium text-gray-500 dark:text-gray-400">Description</span>
                                        <p className="text-gray-900 dark:text-white">{detailProduct.description}</p>
                                    </div>
                                )}
                                <div className="pt-4">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setDetailOpen(false);
                                            handleEdit(detailProduct);
                                        }}
                                    >
                                        <Edit className="h-4 w-4 mr-2" />
                                        Modifier
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Modal Dupliquer vers un dépôt */}
            <Modal
                show={duplicateModalOpen}
                onClose={() => {
                    setDuplicateModalOpen(false);
                    setDuplicateProduct(null);
                    setDuplicateTargetDepotId('');
                }}
                maxWidth="sm"
            >
                {duplicateProduct && (
                    <form onSubmit={handleDuplicateSubmit} className="p-6">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Dupliquer vers un dépôt
                            </h3>
                            <button
                                type="button"
                                onClick={() => setDuplicateModalOpen(false)}
                                className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Créer une copie de « {duplicateProduct.name} » dans un autre dépôt (même infos, nouveau code, stock à 0).
                        </p>
                        <div className="mb-4">
                            <label htmlFor="duplicate-depot" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Dépôt cible
                            </label>
                            <select
                                id="duplicate-depot"
                                value={duplicateTargetDepotId}
                                onChange={(e) => setDuplicateTargetDepotId(e.target.value)}
                                className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                required
                            >
                                <option value="">— Choisir un dépôt —</option>
                                {(depots ?? []).map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name || `Dépôt ${d.id}`}
                                        {currentDepot?.id === d.id ? ' (actuel)' : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDuplicateModalOpen(false)}
                            >
                                Annuler
                            </Button>
                            <Button type="submit" disabled={duplicateSubmitting || !duplicateTargetDepotId}>
                                {duplicateSubmitting ? 'Duplication…' : 'Dupliquer'}
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>

            {/* Modal Import */}
            {canImport && (
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
                        'Colonnes obligatoires : nom, code, categorie_id, prix, unite.',
                        'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                        'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                    ]}
                    examples={[
                        { values: { nom: 'Paracétamol 500mg', code: 'PARA500', categorie_id: '1', prix: '1500', unite: 'boîte' } },
                        { values: { nom: 'Amoxicilline 250mg', code: 'AMOX250', categorie_id: '2', prix: '3200', unite: 'boîte' } },
                    ]}
                    templateUrl={route(`${routePrefix}.products.import.template`)}
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

            {/* Product Drawer */}
            <ProductDrawer
                isOpen={drawerOpen}
                onClose={() => {
                    setDrawerOpen(false);
                    setEditingProduct(null);
                }}
                product={editingProduct}
                categories={categories}
                routePrefix={routePrefix}
            />

            {/* Product Movements Modal */}
            {canViewMovements && (
                <ProductMovementsModal
                    isOpen={movementsModalOpen}
                    onClose={() => {
                        setMovementsModalOpen(false);
                        setMovementsProduct(null);
                    }}
                    product={movementsProduct}
                    routePrefix={routePrefix}
                />
            )}
        </AppLayout>
    );
}