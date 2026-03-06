import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import toast from 'react-hot-toast';
import {
    ArrowLeft,
    FileText,
    Calendar,
    User,
    Package,
    CheckCircle,
    Receipt,
    DollarSign,
    Printer,
    CheckCircle2,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function CommerceSalesShow({ sale }) {
    const { shop } = usePage().props;
    const currency = sale?.currency || shop?.currency || 'CDF';
    const lines = sale?.lines || [];
    const format = (amount) => formatCurrency(Number(amount), currency);
    const [finalizing, setFinalizing] = useState(false);

    if (!sale) return null;

    const statusUpper = (sale?.status ?? '').toString().toUpperCase();
    const isCompleted = statusUpper === 'COMPLETED';
    const isDraft = statusUpper === 'DRAFT';

    const hasReceiptRoute =
        typeof window !== 'undefined' &&
        window.Ziggy?.routes &&
        Object.prototype.hasOwnProperty.call(window.Ziggy.routes, 'commerce.sales.receipt');

    const hasFinalizeRoute =
        typeof window !== 'undefined' &&
        window.Ziggy?.routes &&
        Object.prototype.hasOwnProperty.call(window.Ziggy.routes, 'commerce.sales.finalize');

    const handleFinalize = async () => {
        if (!hasFinalizeRoute || !sale?.id) return;
        setFinalizing(true);
        try {
            await axios.post(route('commerce.sales.finalize', sale.id), { paid_amount: sale.total_amount });
            toast.success('Vente finalisée avec succès.');
            router.visit(route('commerce.sales.show', sale.id));
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur lors de la finalisation.');
        } finally {
            setFinalizing(false);
        }
    };

    return (
        <AppLayout>
            <Head title={`Vente ${sale.id?.slice(0, 8)} - Commerce`} />

            <div className="min-h-screen bg-gray-50 dark:bg-slate-950">
                <div className="container mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div className="flex flex-wrap items-center gap-3">
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={route('commerce.sales.index')}
                                    className="inline-flex items-center"
                                >
                                    <ArrowLeft className="h-4 w-4 mr-1.5" />
                                    Retour
                                </Link>
                            </Button>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h1 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <Receipt className="h-5 w-5 sm:h-6 sm:w-6 text-amber-500" />
                                    Vente #{sale.id?.slice(0, 8)}
                                </h1>
                                <Badge
                                    variant={isCompleted ? 'default' : 'secondary'}
                                    className={
                                        isCompleted
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'
                                    }
                                >
                                    {isCompleted ? (
                                        <>
                                            <CheckCircle className="h-3 w-3 mr-1" />
                                            Terminée
                                        </>
                                    ) : isDraft ? (
                                        <>
                                            <FileText className="h-3 w-3 mr-1" />
                                            Brouillon
                                        </>
                                    ) : (
                                        sale.status
                                    )}
                                </Badge>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {isDraft && hasFinalizeRoute && (
                                <Button
                                    variant="default"
                                    size="sm"
                                    onClick={handleFinalize}
                                    disabled={finalizing}
                                    className="inline-flex items-center bg-green-600 hover:bg-green-700"
                                >
                                    <CheckCircle2 className="h-4 w-4 mr-1.5" />
                                    {finalizing ? 'Finalisation...' : 'Finaliser la vente'}
                                </Button>
                            )}
                            {hasReceiptRoute && (
                                <Button variant="outline" size="sm" asChild>
                                    <a
                                        href={route('commerce.sales.receipt', sale.id)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center"
                                    >
                                        <Printer className="h-4 w-4 mr-1.5" />
                                        Imprimer
                                    </a>
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        {/* Détails de la vente */}
                        <div className="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50">
                                <h2 className="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <FileText className="h-5 w-5 text-amber-500" />
                                    Détails de la vente
                                </h2>
                            </div>
                            <div className="p-4 sm:p-6">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    {/* Date */}
                                    <div className="flex items-start gap-3">
                                        <div className="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                Date
                                            </p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100 truncate">
                                                {sale.created_at}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Vendeur */}
                                    {sale.seller_name && (
                                        <div className="flex items-start gap-3">
                                            <div className="h-10 w-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <User className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                    Vendeur
                                                </p>
                                                <p className="font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    {sale.seller_name}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Client */}
                                    {sale.customer_name && (
                                        <div className="flex items-start gap-3 sm:col-span-2">
                                            <div className="h-10 w-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <User className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                    Client
                                                </p>
                                                <p className="font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    {sale.customer_name}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Notes */}
                                    {sale.notes && (
                                        <div className="sm:col-span-2 mt-2 p-3 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                Notes
                                            </p>
                                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                                {sale.notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Résumé financier */}
                        <div className="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50">
                                <h2 className="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <DollarSign className="h-5 w-5 text-amber-500" />
                                    Total
                                </h2>
                            </div>
                            <div className="p-4 sm:p-6">
                                <div className="flex justify-between items-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                    <span className="text-amber-800 dark:text-amber-200 font-medium">
                                        Montant total
                                    </span>
                                    <span className="text-xl sm:text-2xl font-bold text-amber-700 dark:text-amber-300">
                                        {format(sale.total_amount)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Articles */}
                        <div className="lg:col-span-3 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50">
                                <h2 className="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <Package className="h-5 w-5 text-amber-500" />
                                    Articles ({lines.length})
                                </h2>
                            </div>

                            {lines.length === 0 ? (
                                <div className="py-12 sm:py-16 text-center">
                                    <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                    <p className="text-gray-500 dark:text-gray-400">
                                        Aucun article dans cette vente
                                    </p>
                                </div>
                            ) : (
                                <>
                                    {/* Table - visible sur desktop */}
                                    <div className="hidden sm:block overflow-x-auto">
                                        <table className="w-full">
                                            <thead className="bg-gray-50 dark:bg-slate-800/50 border-b border-gray-200 dark:border-slate-700">
                                                <tr>
                                                    <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Produit
                                                    </th>
                                                    <th className="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Qté
                                                    </th>
                                                    <th className="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Prix unit.
                                                    </th>
                                                    <th className="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Sous-total
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                                {lines.map((line, idx) => (
                                                    <tr
                                                        key={idx}
                                                        className="hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors"
                                                    >
                                                        <td className="px-4 sm:px-6 py-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="h-9 w-9 bg-gray-100 dark:bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                    <Package className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                                </div>
                                                                <span className="font-medium text-gray-900 dark:text-gray-100">
                                                                    {line.product_name}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 sm:px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                                            {line.quantity}
                                                        </td>
                                                        <td className="px-4 sm:px-6 py-4 text-right text-gray-600 dark:text-gray-300">
                                                            {format(line.unit_price)}
                                                        </td>
                                                        <td className="px-4 sm:px-6 py-4 text-right font-medium text-gray-900 dark:text-gray-100">
                                                            {format(line.subtotal)}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                            <tfoot className="bg-gray-50 dark:bg-slate-800/50 border-t-2 border-gray-200 dark:border-slate-700">
                                                <tr>
                                                    <td
                                                        colSpan="3"
                                                        className="px-4 sm:px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-200"
                                                    >
                                                        Total
                                                    </td>
                                                    <td className="px-4 sm:px-6 py-4 text-right font-bold text-lg text-gray-900 dark:text-gray-100">
                                                        {format(sale.total_amount)}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    {/* Cards - visible sur mobile */}
                                    <div className="sm:hidden divide-y divide-gray-200 dark:divide-slate-700">
                                        {lines.map((line, idx) => (
                                            <div
                                                key={idx}
                                                className="p-4 flex flex-col gap-2"
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <div className="h-8 w-8 bg-gray-100 dark:bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                                            <Package className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                        </div>
                                                        <span className="font-medium text-gray-900 dark:text-gray-100 truncate">
                                                            {line.product_name}
                                                        </span>
                                                    </div>
                                                    <span className="font-semibold text-amber-600 dark:text-amber-400 flex-shrink-0">
                                                        {format(line.subtotal)}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                                    <span>
                                                        {line.quantity} × {format(line.unit_price)}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                        <div className="p-4 bg-amber-50 dark:bg-amber-900/20 flex justify-between items-center">
                                            <span className="font-semibold text-gray-700 dark:text-gray-200">
                                                Total
                                            </span>
                                            <span className="font-bold text-lg text-amber-700 dark:text-amber-300">
                                                {format(sale.total_amount)}
                                            </span>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
