import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
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
  History
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

export default function ProductsIndex({ auth, products, categories, filters, canImport = false }) {
    const { auth: authPage } = usePage().props;
    const permissions = authPage?.permissions || [];
    
    const hasPermission = (permission) => {
        if (authPage?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };
    
    const canViewMovements = hasPermission('stock.movement.view');
    
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters?.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailProduct, setDetailProduct] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [movementsModalOpen, setMovementsModalOpen] = useState(false);
    const [movementsProduct, setMovementsProduct] = useState(null);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.products'), {
            search: searchTerm,
            category_id: selectedCategory,
            status: selectedStatus || undefined
        });
    };

    const handleImport = () => {
        setImportOpen(true);
        setImportFile(null);
        setImportResult(null);
    };

    const handleImportSubmit = async (e) => {
        e.preventDefault();
        if (!importFile) {
            toast.error('Veuillez sélectionner un fichier.');
            return;
        }
        setImporting(true);
        setImportResult(null);
        try {
            const formData = new FormData();
            formData.append('file', importFile);
            const res = await axios.post(route('pharmacy.products.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setImportResult(res.data);
            if (res.data.success > 0) {
                toast.success(`${res.data.success} produit(s) importé(s) avec succès.`);
                router.reload();
            }
            if (res.data.failed > 0 && res.data.errors?.length) {
                toast.error(`${res.data.failed} ligne(s) en erreur.`);
            }
        } catch (err) {
            const msg = err.response?.data?.message || 'Erreur lors de l\'import.';
            toast.error(msg);
            setImportResult({ success: 0, failed: 0, total: 0, errors: [msg] });
        } finally {
            setImporting(false);
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
                                router.delete(route('pharmacy.products.destroy', product.id), {
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
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Gestion des Produits
                    </h2>
                    <div className="flex items-center gap-3">
                        {/* Boutons d'export colorés et bien alignés */}
                        <div className="hidden sm:flex items-center gap-2">
                            <a
                                href={products.length ? route('pharmacy.products.export.pdf') : '#'}
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
                                Exporter PDF
                            </a>
                            <a
                                href={products.length ? route('pharmacy.products.export.excel') : '#'}
                                aria-disabled={products.length === 0}
                                className={`inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 px-3 h-10 shadow-sm hover:shadow-md ${
                                    products.length === 0
                                        ? 'opacity-50 cursor-not-allowed pointer-events-none bg-emerald-400 text-white/80'
                                        : 'bg-emerald-500 text-white hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-700'
                                }`}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Exporter Excel
                            </a>
                        </div>
                        {/* Version compacte pour mobile */}
                        <div className="flex sm:hidden items-center gap-2">
                            <a
                                href={products.length ? route('pharmacy.products.export.pdf') : '#'}
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
                                href={products.length ? route('pharmacy.products.export.excel') : '#'}
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
                                <span className="hidden sm:inline">Importer</span>
                            </button>
                        )}
                        <button
                            onClick={handleCreate}
                            className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            <span className="hidden sm:inline">Ajouter un produit</span>
                            <span className="sm:hidden">Ajouter</span>
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Gestion des Produits" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search and Filters */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Search className="h-5 w-5 mr-2" />
                                Search Products
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="flex flex-col gap-4">
                                <div className="flex flex-col md:flex-row gap-4">
                                    <div className="flex-1">
                                        <Input
                                            placeholder="Rechercher par nom ou code..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <select
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={selectedStatus}
                                            onChange={(e) => setSelectedStatus(e.target.value)}
                                        >
                                            <option value="">Tous les statuts</option>
                                            <option value="active">Actif</option>
                                            <option value="inactive">Inactif</option>
                                        </select>
                                    </div>
                                    <Button type="submit">
                                        <Search className="h-4 w-4 mr-2" />
                                        Rechercher
                                    </Button>
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
                                        Ajouter un produit
                                    </button>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
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
                            )}
                        </CardContent>
                    </Card>
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

            {/* Modal Import */}
            <Modal
                show={importOpen}
                onClose={() => {
                    setImportOpen(false);
                    setImportFile(null);
                    setImportResult(null);
                }}
                maxWidth="lg"
            >
                <div className="p-6">
                    <div className="flex justify-between items-start mb-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Importer des produits
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
                        Fichiers acceptés : .xlsx ou .csv. Colonnes obligatoires : <strong>nom</strong>, <strong>code</strong>, <strong>categorie_id</strong> (ou nom de catégorie), <strong>prix</strong>, <strong>unite</strong>.
                    </p>
                    <form onSubmit={handleImportSubmit} className="space-y-4">
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
                        {importResult && (
                            <div className="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">
                                <div className="flex items-center gap-4">
                                    <span className="flex items-center text-green-600 dark:text-green-400">
                                        <CheckCircle className="h-5 w-5 mr-1" />
                                        {importResult.success} importé(s)
                                    </span>
                                    {importResult.failed > 0 && (
                                        <span className="flex items-center text-red-600 dark:text-red-400">
                                            <XCircle className="h-5 w-5 mr-1" />
                                            {importResult.failed} échoué(s)
                                        </span>
                                    )}
                                </div>
                                {importResult.errors?.length > 0 && (
                                    <div className="mt-2 max-h-32 overflow-y-auto text-xs text-red-600 dark:text-red-400">
                                        {importResult.errors.map((err, i) => (
                                            <div key={i}>{err}</div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setImportOpen(false)}
                            >
                                Fermer
                            </Button>
                            <Button type="submit" disabled={!importFile || importing}>
                                {importing ? 'Import en cours...' : 'Importer'}
                            </Button>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* Product Drawer */}
            <ProductDrawer
                isOpen={drawerOpen}
                onClose={() => {
                    setDrawerOpen(false);
                    setEditingProduct(null);
                }}
                product={editingProduct}
                categories={categories}
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
                />
            )}
        </AppLayout>
    );
}