import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { 
  Package,
  AlertTriangle,
  Calendar,
  Plus,
  Minus,
  Edit3,
  History,
  Filter,
  Search
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import Modal from '@/Components/Modal';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';

export default function StockManagement({ products, lowStock, expiringSoon, categories = [], filters = {}, pagination }) {
    const { data, setData, post, processing, errors } = useForm({
        product_id: '',
        type: 'adjust',
        quantity: '',
        batch_number: '',
        expiry_date: '',
        supplier_id: '',
        purchase_order_id: ''
    });

    const [showStockForm, setShowStockForm] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [stockStatus, setStockStatus] = useState(filters.stock_status || '');
    const [perPage, setPerPage] = useState(filters.per_page || (pagination?.per_page || 15));
    const [movementsModalOpen, setMovementsModalOpen] = useState(false);
    const [movementsProduct, setMovementsProduct] = useState(null);
    const [movements, setMovements] = useState([]);

    const [stockSubmitting, setStockSubmitting] = useState(false);

    const handleStockUpdate = async () => {
        if (!selectedProduct) return;
        setStockSubmitting(true);
        try {
            await axios.post(route('pharmacy.products.stock.update', selectedProduct.id), {
                type: data.type,
                quantity: data.quantity,
                batch_number: data.batch_number || null,
                expiry_date: data.expiry_date || null,
                supplier_id: data.supplier_id || null,
                purchase_order_id: data.purchase_order_id || null,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            toast.success("Stock mis à jour avec succès");
            setShowStockForm(false);
            setSelectedProduct(null);
            setData({ product_id: '', type: 'adjust', quantity: '', batch_number: '', expiry_date: '', supplier_id: '', purchase_order_id: '' });
            router.reload();
        } catch (err) {
            let message = "Échec de la mise à jour du stock";
            const resData = err.response?.data;
            if (resData) {
                if (typeof resData.message === 'string') message = resData.message;
                else if (resData.errors && typeof resData.errors === 'object') {
                    const first = Object.values(resData.errors).flat()[0];
                    if (first) message = first;
                }
            }
            toast.error(String(message));
        } finally {
            setStockSubmitting(false);
        }
    };

    const openStockForm = (product, type = 'adjust') => {
        setSelectedProduct(product);
        setData({
            ...data,
            product_id: product.id,
            type: type
        });
        setShowStockForm(true);
    };

    const getStockStatus = (product) => {
        if (product.current_stock <= 0) return { status: 'out', color: 'destructive', label: 'Rupture' };
        if (product.current_stock <= product.minimum_stock) return { status: 'low', color: 'warning', label: 'Stock faible' };
        return { status: 'good', color: 'success', label: 'OK' };
    };

    const handleFilterSubmit = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.stock.index'), {
            search: searchTerm || undefined,
            category_id: selectedCategory || undefined,
            stock_status: stockStatus || undefined,
            per_page: perPage || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handlePageChange = (page) => {
        router.get(route('pharmacy.stock.index'), {
            search: filters.search,
            category_id: filters.category_id,
            stock_status: filters.stock_status,
            per_page: pagination?.per_page,
            page,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const openMovementsModal = async (product) => {
        try {
            const res = await axios.get(route('pharmacy.stock.movements', product.id));
            setMovements(res.data.movements || []);
            setMovementsProduct(product);
            setMovementsModalOpen(true);
        } catch (e) {
            toast.error("Impossible de charger l'historique");
        }
    };

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Gestion du Stock
                </h2>
            }
        >
            <Head title="Gestion du Stock" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Total Produits</CardTitle>
                                <Package className="h-4 w-4 text-blue-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">{pagination?.total ?? products.length}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    produits en stock
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Stock Faible</CardTitle>
                                <AlertTriangle className="h-4 w-4 text-orange-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">{lowStock.length}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    produits à réapprovisionner
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Expirations Proches</CardTitle>
                                <Calendar className="h-4 w-4 text-red-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-600 dark:text-red-400">{expiringSoon.length}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                    lots expirent bientôt
                                </p>
                                <Link 
                                    href={route('pharmacy.expirations.index')}
                                    className="flex items-center justify-center gap-2 w-full px-3 py-2 text-sm font-medium rounded-md bg-red-500 hover:bg-red-600 text-white transition-colors"
                                >
                                    <Calendar className="h-4 w-4" />
                                    <span>Voir tout</span>
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Actions rapides</CardTitle>
                                <History className="h-4 w-4 text-purple-500" />
                            </CardHeader>
                            <CardContent>
                                <Link 
                                    href={route('pharmacy.stock.movements.index')}
                                    className="flex items-center justify-center gap-2 w-full px-3 py-2 text-sm font-medium rounded-md bg-purple-500 hover:bg-purple-600 text-white transition-colors"
                                >
                                    <History className="h-4 w-4" />
                                    <span>Historique mouvements</span>
                                </Link>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filtres */}
                    <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Filter className="h-5 w-5 mr-2 text-amber-500" />
                                Filtres
                            </CardTitle>
                            <ExportButtons
                                pdfUrl={route('pharmacy.exports.stock.pdf')}
                                excelUrl={route('pharmacy.exports.stock.excel')}
                                disabled={!products.length}
                            />
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilterSubmit} className="flex flex-col md:flex-row gap-4">
                                <div className="flex-1">
                                    <Label htmlFor="search" className="text-gray-700 dark:text-gray-300">Recherche</Label>
                                    <Input
                                        id="search"
                                        placeholder="Nom ou code produit"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="flex-1">
                                    <Label htmlFor="category" className="text-gray-700 dark:text-gray-300">Catégorie</Label>
                                    <select
                                        id="category"
                                        value={selectedCategory}
                                        onChange={(e) => setSelectedCategory(e.target.value)}
                                        className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        <option value="">Toutes</option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="flex-1">
                                    <Label htmlFor="stock_status" className="text-gray-700 dark:text-gray-300">Statut stock</Label>
                                    <select
                                        id="stock_status"
                                        value={stockStatus}
                                        onChange={(e) => setStockStatus(e.target.value)}
                                        className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        <option value="">Tous</option>
                                        <option value="low">Stock faible</option>
                                        <option value="out">Rupture</option>
                                    </select>
                                </div>
                                <div className="w-full md:w-32">
                                    <Label htmlFor="per_page" className="text-gray-700 dark:text-gray-300">Par page</Label>
                                    <select
                                        id="per_page"
                                        value={perPage}
                                        onChange={(e) => setPerPage(e.target.value)}
                                        className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        <option value={10}>10</option>
                                        <option value={15}>15</option>
                                        <option value={25}>25</option>
                                        <option value={50}>50</option>
                                    </select>
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" className="bg-amber-500 hover:bg-amber-600 text-white">
                                        <Search className="h-4 w-4 mr-2" />
                                        Filtrer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Alertes Stock Faible */}
                    {lowStock.length > 0 && (
                        <Card className="mb-6 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                            <CardHeader>
                                <CardTitle className="flex items-center text-orange-800 dark:text-orange-300">
                                    <AlertTriangle className="h-5 w-5 mr-2" />
                                    Alerte Stock Faible ({lowStock.length} produits)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {lowStock.slice(0, 5).map(product => (
                                        <div key={product.id} className="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-orange-100 dark:border-orange-900">
                                            <div>
                                                <h4 className="font-medium text-gray-900 dark:text-white">{product.name}</h4>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    Actuel: {product.current_stock} | Minimum: {product.minimum_stock}
                                                </p>
                                            </div>
                                            <Button size="sm" onClick={() => openStockForm(product, 'add')} className="bg-orange-500 hover:bg-orange-600 text-white">
                                                <Plus className="h-4 w-4 mr-1" />
                                                Ajouter
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Alertes Expiration */}
                    {expiringSoon.length > 0 && (
                        <Card className="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <CardHeader>
                                <CardTitle className="flex items-center text-red-800 dark:text-red-300">
                                    <Calendar className="h-5 w-5 mr-2" />
                                    Expirations Proches ({expiringSoon.length} lots)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {expiringSoon.slice(0, 5).map(batch => (
                                        <div key={batch.id} className="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-red-100 dark:border-red-900">
                                            <div>
                                                <h4 className="font-medium text-gray-900 dark:text-white">{batch.product_name}</h4>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    Lot: {batch.batch_number} | Expire: {batch.expiry_date}
                                                </p>
                                            </div>
                                            <Badge variant="destructive">
                                                {batch.days_until_expiry} jours
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Formulaire de mise à jour du stock */}
                    {showStockForm && selectedProduct && (
                        <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="text-gray-900 dark:text-white">Mettre à jour le stock de {selectedProduct.name}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={(e) => e.preventDefault()} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="type" className="text-gray-700 dark:text-gray-300">Type d'opération</Label>
                                            <select
                                                id="type"
                                                value={data.type}
                                                onChange={(e) => setData('type', e.target.value)}
                                                className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                            >
                                                <option value="add">Ajouter</option>
                                                <option value="remove">Retirer</option>
                                                <option value="adjust">Ajuster</option>
                                            </select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="quantity" className="text-gray-700 dark:text-gray-300">Quantité *</Label>
                                            <Input
                                                id="quantity"
                                                type="number"
                                                value={data.quantity}
                                                onChange={(e) => setData('quantity', e.target.value)}
                                                placeholder="Entrez la quantité"
                                                className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                            />
                                            {errors.quantity && <p className="text-sm text-red-600 dark:text-red-400">{errors.quantity}</p>}
                                        </div>
                                    </div>

                                    {(data.type === 'add' || data.type === 'adjust') && (
                                        <>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="batch_number" className="text-gray-700 dark:text-gray-300">Numéro de lot</Label>
                                                    <Input
                                                        id="batch_number"
                                                        value={data.batch_number}
                                                        onChange={(e) => setData('batch_number', e.target.value)}
                                                        placeholder="LOT-001"
                                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="expiry_date" className="text-gray-700 dark:text-gray-300">Date d'expiration</Label>
                                                    <Input
                                                        id="expiry_date"
                                                        type="date"
                                                        value={data.expiry_date}
                                                        onChange={(e) => setData('expiry_date', e.target.value)}
                                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                                    />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    <div className="flex justify-end space-x-3 pt-4">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                setShowStockForm(false);
                                                setSelectedProduct(null);
                                            }}
                                            disabled={stockSubmitting}
                                            className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                        >
                                            Annuler
                                        </Button>
                                        <Button type="button" disabled={stockSubmitting} onClick={handleStockUpdate} className="bg-amber-500 hover:bg-amber-600 text-white">
                                            {stockSubmitting ? 'Mise à jour...' : 'Mettre à jour'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* Liste des produits */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2 text-blue-500" />
                                Produits ({pagination?.total ?? products.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                    <thead className="bg-gray-50 dark:bg-slate-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Produit
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Stock actuel
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                        {products.map((product) => {
                                            const status = getStockStatus(product);
                                            return (
                                                <tr key={product.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                {product.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                {product.product_code}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900 dark:text-white">
                                                            <span className="font-medium">{product.current_stock}</span>
                                                            <span className="text-gray-500 dark:text-gray-400"> / {product.minimum_stock}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge variant={status.color}>
                                                            {status.label}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'add')}
                                                                disabled={stockSubmitting}
                                                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700"
                                                                title="Ajouter"
                                                            >
                                                                <Plus className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'remove')}
                                                                disabled={stockSubmitting}
                                                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700"
                                                                title="Retirer"
                                                            >
                                                                <Minus className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'adjust')}
                                                                disabled={stockSubmitting}
                                                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700"
                                                                title="Ajuster"
                                                            >
                                                                <Edit3 className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openMovementsModal(product)}
                                                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700"
                                                                title="Historique"
                                                            >
                                                                <History className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Pagination */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                            <div>
                                Affichage de <span className="font-medium text-gray-900 dark:text-white">{pagination.from}</span> à{' '}
                                <span className="font-medium text-gray-900 dark:text-white">{pagination.to}</span> sur{' '}
                                <span className="font-medium text-gray-900 dark:text-white">{pagination.total}</span> produit(s)
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={pagination.current_page <= 1}
                                    onClick={() => handlePageChange(pagination.current_page - 1)}
                                    className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                >
                                    Précédent
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={pagination.current_page >= pagination.last_page}
                                    onClick={() => handlePageChange(pagination.current_page + 1)}
                                    className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                >
                                    Suivant
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal historique mouvements */}
            {movementsProduct && (
                <Modal
                    show={movementsModalOpen}
                    onClose={() => {
                        setMovementsModalOpen(false);
                        setMovementsProduct(null);
                        setMovements([]);
                    }}
                    maxWidth="xl"
                >
                    <div className="p-6 bg-white dark:bg-slate-900">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Historique des mouvements – {movementsProduct.name}
                            </h3>
                            <button
                                type="button"
                                onClick={() => {
                                    setMovementsModalOpen(false);
                                    setMovementsProduct(null);
                                    setMovements([]);
                                }}
                                className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl"
                            >
                                ×
                            </button>
                        </div>
                        {movements.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Aucun mouvement enregistré.</p>
                        ) : (
                            <div className="overflow-x-auto max-h-80">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                    <thead className="bg-gray-50 dark:bg-slate-800">
                                        <tr>
                                            <th className="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                            <th className="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                            <th className="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantité</th>
                                            <th className="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                        {movements.map((mvt) => (
                                            <tr key={mvt.id}>
                                                <td className="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{mvt.created_at}</td>
                                                <td className="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{mvt.type}</td>
                                                <td className="px-4 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">{mvt.quantity}</td>
                                                <td className="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{mvt.reference || '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}
