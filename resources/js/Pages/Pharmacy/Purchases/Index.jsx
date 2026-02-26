import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    Package, 
    Plus, 
    Eye, 
    Filter, 
    Truck, 
    CheckCircle, 
    Clock, 
    Search,
    XCircle,
    FileText,
    Edit,
    TrendingUp
} from 'lucide-react';
import PurchaseOrderDrawer from '@/Components/Pharmacy/PurchaseOrderDrawer';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';
import { formatCurrency as formatCurrencyUtil } from '@/lib/currency';

export default function PurchasesIndex({ 
    purchase_orders = [], 
    filters = {}, 
    suppliers = [], 
    products = [],
    routePrefix = 'pharmacy'
}) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [status, setStatus] = useState(filters.status || '');
    
    // Drawer state
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [editingOrder, setEditingOrder] = useState(null);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route(`${routePrefix}.purchases.index`), { 
            from: from || undefined, 
            to: to || undefined, 
            status: status || undefined 
        }, { preserveState: true });
    };

    const getStatusBadge = (s) => {
        switch (s) {
            case 'RECEIVED':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Reçu
                    </Badge>
                );
            case 'PARTIALLY_RECEIVED':
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <Clock className="h-3 w-3 mr-1" />
                        Partiel
                    </Badge>
                );
            case 'CONFIRMED':
                return (
                    <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Confirmé
                    </Badge>
                );
            case 'CANCELLED':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <XCircle className="h-3 w-3 mr-1" />
                        Annulé
                    </Badge>
                );
            default:
                return (
                    <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-700/50 dark:text-gray-300">
                        <FileText className="h-3 w-3 mr-1" />
                        Brouillon
                    </Badge>
                );
        }
    };

    const openCreateDrawer = () => {
        setEditingOrder(null);
        setIsDrawerOpen(true);
    };

    const openEditDrawer = (order) => {
        setEditingOrder(order);
        setIsDrawerOpen(true);
    };

    const handleDrawerSuccess = () => {
        router.reload({ only: ['purchase_orders'] });
    };

    const receivedCount = purchase_orders.filter(po => po.status === 'RECEIVED').length;
    const pendingCount = purchase_orders.filter(po => ['DRAFT', 'CONFIRMED'].includes(po.status)).length;
    const totalAmount = purchase_orders
        .filter(po => po.status === 'RECEIVED')
        .reduce((sum, po) => sum + Number(po.total_amount), 0);

    const formatCurrency = (amount) => formatCurrencyUtil(amount, currency);

    return (
        <AppLayout>
            <Head title="Bons de Commande" />
            
            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <Truck className="h-6 w-6" />
                            Bons de Commande
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1">
                            Gestion des achats fournisseurs
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons
                            pdfUrl={route(`${routePrefix}.exports.purchases.pdf`, { from, to, status })}
                            excelUrl={route(`${routePrefix}.exports.purchases.excel`, { from, to, status })}
                            disabled={!purchase_orders.length}
                        />
                        <Button onClick={openCreateDrawer}>
                            <Plus className="h-4 w-4 mr-2" />
                            Nouveau bon
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {/* Total Commandes */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{purchase_orders.length}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">bons de commande</p>
                            </div>
                            <div className="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <Package className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    {/* Reçus */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Reçus</p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{receivedCount}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">commandes complètes</p>
                            </div>
                            <div className="h-12 w-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <CheckCircle className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    {/* En attente */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">En attente</p>
                                <p className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{pendingCount}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">à réceptionner</p>
                            </div>
                            <div className="h-12 w-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <Clock className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </div>

                    {/* Montant total */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total achats</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{formatCurrency(totalAmount)}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">commandes reçues</p>
                            </div>
                            <div className="h-12 w-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <TrendingUp className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtres */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <Filter className="h-5 w-5 text-gray-500" />
                            Filtres
                        </h2>
                    </div>
                    <div className="p-6">
                        <form onSubmit={handleFilter} className="flex flex-wrap gap-4 items-end">
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Du</label>
                                <Input 
                                    type="date" 
                                    value={from} 
                                    onChange={(e) => setFrom(e.target.value)} 
                                    className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Au</label>
                                <Input 
                                    type="date" 
                                    value={to} 
                                    onChange={(e) => setTo(e.target.value)} 
                                    className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Statut</label>
                                <select
                                    className="w-full h-10 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="DRAFT">Brouillon</option>
                                    <option value="CONFIRMED">Confirmé</option>
                                    <option value="PARTIALLY_RECEIVED">Partiellement reçu</option>
                                    <option value="RECEIVED">Reçu</option>
                                    <option value="CANCELLED">Annulé</option>
                                </select>
                            </div>
                            <Button type="submit" className="inline-flex items-center gap-2">
                                <Search className="h-4 w-4" />
                                Filtrer
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Liste des bons de commande */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <Truck className="h-5 w-5 text-gray-500" />
                            Liste des bons ({purchase_orders.length})
                        </h2>
                    </div>

                    {purchase_orders.length === 0 ? (
                        <div className="py-12 text-center">
                            <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                Aucun bon de commande
                            </p>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Créez votre premier bon pour commencer
                            </p>
                            <Button onClick={openCreateDrawer}>
                                <Plus className="h-4 w-4 mr-2" />
                                Nouveau bon
                            </Button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Fournisseur
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {purchase_orders.map((po) => (
                                        <tr key={po.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {po.created_at}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-8 w-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                        <Truck className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                    </div>
                                                    <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {po.supplier_name || '—'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(po.status)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                                {formatCurrency(Number(po.total_amount))}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {po.status === 'DRAFT' && (
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm"
                                                            onClick={() => openEditDrawer(po)}
                                                            className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        asChild
                                                        className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                    >
                                                        <Link href={route(`${routePrefix}.purchases.show`, po.id)}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Purchase Order Drawer */}
            <PurchaseOrderDrawer
                isOpen={isDrawerOpen}
                onClose={() => setIsDrawerOpen(false)}
                purchaseOrder={editingOrder}
                suppliers={suppliers}
                products={products}
                currency={currency}
                onSuccess={handleDrawerSuccess}
                routePrefix={routePrefix}
            />
        </AppLayout>
    );
}
