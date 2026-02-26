import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { 
    ArrowLeft, 
    Package, 
    CheckCircle, 
    Truck, 
    Calendar,
    DollarSign,
    XCircle,
    FileText,
    Clock,
    Edit
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import PurchaseOrderDrawer from '@/Components/Pharmacy/PurchaseOrderDrawer';
import ReceivePurchaseOrderDrawer from '@/Components/Pharmacy/ReceivePurchaseOrderDrawer';
import { formatCurrency as formatCurrencyUtil } from '@/lib/currency';

export default function PurchasesShow({ 
    auth, 
    purchase_order, 
    lines = [],
    suppliers = [],
    products = [],
    routePrefix = 'pharmacy'
}) {
    const { shop } = usePage().props;
    const currency = purchase_order.currency || shop?.currency || 'CDF';
    
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [isReceiveDrawerOpen, setIsReceiveDrawerOpen] = useState(false);
    
    const canConfirm = purchase_order.status === 'DRAFT';
    const canReceive = purchase_order.status === 'CONFIRMED' || purchase_order.status === 'PARTIALLY_RECEIVED';
    const canCancel = !['RECEIVED', 'PARTIALLY_RECEIVED', 'CANCELLED'].includes(purchase_order.status);
    const canEdit = purchase_order.status === 'DRAFT';

    const handleConfirm = () => {
        axios.post(route(`${routePrefix}.purchases.confirm`, purchase_order.id))
            .then(() => { 
                toast.success('Bon de commande confirmé'); 
                router.reload(); 
            })
            .catch((err) => toast.error(err.response?.data?.message || 'Erreur'));
    };

    const handleReceive = () => {
        // Open receive drawer to enter batch info
        setIsReceiveDrawerOpen(true);
    };

    const handleReceiveSuccess = () => {
        setIsReceiveDrawerOpen(false);
        router.reload();
    };

    const handleCancel = () => {
        if (!confirm('Annuler ce bon de commande ?')) return;
        axios.post(route(`${routePrefix}.purchases.cancel`, purchase_order.id))
            .then(() => { 
                toast.success('Bon de commande annulé'); 
                router.reload(); 
            })
            .catch((err) => toast.error(err.response?.data?.message || 'Erreur'));
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
                        Partiellement reçu
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

    const formatCurrency = (amount) => formatCurrencyUtil(amount, currency);

    const handleDrawerSuccess = () => {
        router.reload();
    };

    // Prepare order data for drawer
    const orderForDrawer = {
        ...purchase_order,
        lines: lines.map(line => ({
            product_id: line.product_id,
            product_name: line.product_name,
            ordered_quantity: line.ordered_quantity,
            unit_cost: line.unit_cost
        }))
    };

    return (
        <AppLayout>
            <Head title={`Bon de commande ${purchase_order.id.slice(0, 8)}`} />
            
            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route(`${routePrefix}.purchases.index`)}>
                                <ArrowLeft className="h-4 w-4 mr-1" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <Truck className="h-6 w-6" />
                                Bon #{purchase_order.id.slice(0, 8)}
                            </h1>
                        </div>
                        {getStatusBadge(purchase_order.status)}
                    </div>
                    
                    <div className="flex gap-2 flex-wrap">
                        {canEdit && (
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={() => setIsDrawerOpen(true)}
                            >
                                <Edit className="h-4 w-4 mr-1" />
                                Modifier
                            </Button>
                        )}
                        {canConfirm && (
                            <Button size="sm" onClick={handleConfirm}>
                                <CheckCircle className="h-4 w-4 mr-1" />
                                Confirmer
                            </Button>
                        )}
                        {canReceive && (
                            <Button size="sm" variant="secondary" onClick={handleReceive}>
                                <Truck className="h-4 w-4 mr-1" />
                                Réceptionner
                            </Button>
                        )}
                        {canCancel && (
                            <Button size="sm" variant="destructive" onClick={handleCancel}>
                                <XCircle className="h-4 w-4 mr-1" />
                                Annuler
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Détails de la commande */}
                    <div className="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <Package className="h-5 w-5 text-gray-500" />
                                Informations de la commande
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                {/* Fournisseur */}
                                <div className="flex items-start gap-3">
                                    <div className="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <Truck className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Fournisseur</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">
                                            {purchase_order.supplier_name || '—'}
                                        </p>
                                    </div>
                                </div>

                                {/* Date commande */}
                                <div className="flex items-start gap-3">
                                    <div className="h-10 w-10 bg-gray-100 dark:bg-gray-700/50 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <Calendar className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Date commande</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">
                                            {purchase_order.ordered_at || purchase_order.created_at || '—'}
                                        </p>
                                    </div>
                                </div>

                                {/* Date prévue */}
                                <div className="flex items-start gap-3">
                                    <div className="h-10 w-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <Clock className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Livraison prévue</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">
                                            {purchase_order.expected_at || '—'}
                                        </p>
                                    </div>
                                </div>

                                {/* Date réception */}
                                {purchase_order.received_at && (
                                    <div className="flex items-start gap-3">
                                        <div className="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Date réception</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">
                                                {purchase_order.received_at}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Notes */}
                            {purchase_order.notes && (
                                <div className="mt-6 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Notes</p>
                                    <p className="text-gray-900 dark:text-gray-100">{purchase_order.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Résumé financier */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-gray-500" />
                                Résumé
                            </h2>
                        </div>
                        <div className="p-6 space-y-4">
                            {/* Nombre de lignes */}
                            <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                <span className="text-gray-600 dark:text-gray-300">Lignes</span>
                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                    {lines.length} produit(s)
                                </span>
                            </div>

                            {/* Quantité totale */}
                            <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                <span className="text-gray-600 dark:text-gray-300">Quantité totale</span>
                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                    {lines.reduce((sum, l) => sum + Number(l.ordered_quantity), 0)} unités
                                </span>
                            </div>

                            {/* Total */}
                            <div className="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span className="text-blue-700 dark:text-blue-300 font-medium">Total</span>
                                <span className="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    {formatCurrency(Number(purchase_order.total_amount))}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Lignes de commande */}
                    <div className="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <Package className="h-5 w-5 text-gray-500" />
                                Produits commandés ({lines.length})
                            </h2>
                        </div>

                        {lines.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-gray-500 dark:text-gray-400">Aucune ligne dans cette commande</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Produit
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Commandé
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Reçu
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Prix unitaire
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {lines.map((line) => (
                                            <tr key={line.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                            <Package className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                                        </div>
                                                        <span className="font-medium text-gray-900 dark:text-gray-100">
                                                            {line.product_name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                                    {line.ordered_quantity}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <span className={line.received_quantity >= line.ordered_quantity 
                                                        ? 'text-green-600 dark:text-green-400' 
                                                        : 'text-amber-600 dark:text-amber-400'
                                                    }>
                                                        {line.received_quantity || 0}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-right text-gray-600 dark:text-gray-300">
                                                    {formatCurrency(Number(line.unit_cost))}
                                                </td>
                                                <td className="px-6 py-4 text-right font-medium text-gray-900 dark:text-gray-100">
                                                    {formatCurrency(Number(line.line_total))}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <td colSpan="4" className="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-200">
                                                Total
                                            </td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-900 dark:text-gray-100">
                                                {formatCurrency(Number(purchase_order.total_amount))}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Edit Drawer */}
            <PurchaseOrderDrawer
                isOpen={isDrawerOpen}
                onClose={() => setIsDrawerOpen(false)}
                purchaseOrder={orderForDrawer}
                suppliers={suppliers}
                products={products}
                currency={currency}
                onSuccess={handleDrawerSuccess}
                routePrefix={routePrefix}
            />

            {/* Receive Drawer */}
            <ReceivePurchaseOrderDrawer
                isOpen={isReceiveDrawerOpen}
                onClose={() => setIsReceiveDrawerOpen(false)}
                purchaseOrder={purchase_order}
                lines={lines}
                currency={currency}
                onSuccess={handleReceiveSuccess}
                routePrefix={routePrefix}
            />
        </AppLayout>
    );
}
