import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { 
    ArrowLeft, 
    FileText, 
    Calendar, 
    User, 
    CreditCard,
    Package,
    CheckCircle,
    XCircle,
    Receipt,
    DollarSign
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import { formatCurrency as formatCurrencyUtil } from '@/lib/currency';

export default function SalesShow({ auth, sale, lines = [], customer, routePrefix = 'pharmacy' }) {
    const { shop } = usePage().props;
    const currency = sale.currency || shop?.currency || 'CDF';
    const isDraft = sale.status === 'DRAFT';

    const handleCancel = () => {
        if (!confirm('Annuler cette vente (brouillon) ?')) return;
        axios.post(route(`${routePrefix}.sales.cancel`, sale.id))
            .then(() => {
                toast.success('Vente annulée');
                window.location.reload();
            })
            .catch((err) => toast.error(err.response?.data?.message || 'Erreur lors de l\'annulation'));
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'COMPLETED':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Terminée
                    </Badge>
                );
            case 'CANCELLED':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <XCircle className="h-3 w-3 mr-1" />
                        Annulée
                    </Badge>
                );
            default:
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <FileText className="h-3 w-3 mr-1" />
                        Brouillon
                    </Badge>
                );
        }
    };

    const formatCurrency = (amount) => formatCurrencyUtil(amount, currency);

    return (
        <AppLayout>
            <Head title={`Vente ${sale.id.slice(0, 8)}`} />
            
            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route(`${routePrefix}.sales.index`)}>
                                <ArrowLeft className="h-4 w-4 mr-1" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <Receipt className="h-6 w-6" />
                                Vente #{sale.id.slice(0, 8)}
                            </h1>
                        </div>
                        {getStatusBadge(sale.status)}
                    </div>
                    {isDraft && (
                        <Button variant="destructive" size="sm" onClick={handleCancel}>
                            <XCircle className="h-4 w-4 mr-1" />
                            Annuler la vente
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Détails de la vente */}
                    <div className="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <FileText className="h-5 w-5 text-gray-500" />
                                Détails de la vente
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                {/* Date création */}
                                <div className="flex items-start gap-3">
                                    <div className="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Date création</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">{sale.created_at}</p>
                                    </div>
                                </div>

                                {/* Vendeur */}
                                {(sale.seller_name) && (
                                    <div className="flex items-start gap-3">
                                        <div className="h-10 w-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <User className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Vendeur</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{sale.seller_name}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Date finalisation */}
                                {sale.completed_at && (
                                    <div className="flex items-start gap-3">
                                        <div className="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Date finalisation</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{sale.completed_at}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Client */}
                                {customer && (
                                    <div className="flex items-start gap-3">
                                        <div className="h-10 w-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <User className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Client</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">
                                                {customer.full_name || customer.name}
                                                {customer.phone && (
                                                    <span className="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                                        ({customer.phone})
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Résumé financier */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-gray-500" />
                                Résumé financier
                            </h2>
                        </div>
                        <div className="p-6 space-y-4">
                            {/* Total */}
                            <div className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                <span className="text-gray-600 dark:text-gray-300">Total</span>
                                <span className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                    {formatCurrency(Number(sale.total_amount))}
                                </span>
                            </div>

                            {/* Montant payé */}
                            <div className="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span className="text-green-700 dark:text-green-300">Montant payé</span>
                                <span className="text-lg font-semibold text-green-600 dark:text-green-400">
                                    {formatCurrency(Number(sale.paid_amount))}
                                </span>
                            </div>

                            {/* Solde */}
                            <div className={`flex justify-between items-center p-3 rounded-lg ${
                                Number(sale.balance_amount) > 0 
                                    ? 'bg-amber-50 dark:bg-amber-900/20' 
                                    : 'bg-gray-50 dark:bg-gray-700/30'
                            }`}>
                                <span className={Number(sale.balance_amount) > 0 
                                    ? 'text-amber-700 dark:text-amber-300' 
                                    : 'text-gray-600 dark:text-gray-300'
                                }>
                                    Solde restant
                                </span>
                                <span className={`text-lg font-semibold ${
                                    Number(sale.balance_amount) > 0 
                                        ? 'text-amber-600 dark:text-amber-400' 
                                        : 'text-gray-600 dark:text-gray-400'
                                }`}>
                                    {formatCurrency(Number(sale.balance_amount))}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Lignes de la vente */}
                    <div className="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <Package className="h-5 w-5 text-gray-500" />
                                Articles ({lines.length})
                            </h2>
                        </div>

                        {lines.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-gray-500 dark:text-gray-400">Aucun article dans cette vente</p>
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
                                                Quantité
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
                                                    {line.quantity}
                                                </td>
                                                <td className="px-6 py-4 text-right text-gray-600 dark:text-gray-300">
                                                    {formatCurrency(Number(line.unit_price))}
                                                </td>
                                                <td className="px-6 py-4 text-right font-medium text-gray-900 dark:text-gray-100">
                                                    {formatCurrency(Number(line.line_total))}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <td colSpan="3" className="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-200">
                                                Total
                                            </td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-900 dark:text-gray-100">
                                                {formatCurrency(Number(sale.total_amount))}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
