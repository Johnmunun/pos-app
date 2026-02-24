import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    ShoppingCart,
    Plus,
    Eye,
    Filter,
    DollarSign,
    CheckCircle,
    XCircle,
    Search,
    FileText,
    TrendingUp,
    Printer,
    Wallet as CashRegister
} from 'lucide-react';
import { formatCurrency as formatCurrencyUtil } from '@/lib/currency';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';

export default function SalesIndex({ sales = [], filters = {}, canViewAllSales = true }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [status, setStatus] = useState(filters.status || '');

    // Resynchroniser les champs avec l’URL quand on charge la page avec ?from=&to= ou après navigation
    useEffect(() => {
        setFrom(filters.from ?? '');
        setTo(filters.to ?? '');
        setStatus(filters.status ?? '');
    }, [filters.from, filters.to, filters.status]);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.sales.index'), { from: from || undefined, to: to || undefined, status: status || undefined }, { preserveState: true });
    };

    const getStatusBadge = (s) => {
        if (s === 'COMPLETED') {
            return (
                <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Terminée
                </Badge>
            );
        }
        if (s === 'CANCELLED') {
            return (
                <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                    <XCircle className="h-3 w-3 mr-1" />
                    Annulée
                </Badge>
            );
        }
        return (
            <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                <FileText className="h-3 w-3 mr-1" />
                Brouillon
            </Badge>
        );
    };

    const completedSales = sales.filter(s => s.status === 'COMPLETED').length;
    const draftSales = sales.filter(s => s.status === 'DRAFT').length;
    const totalRevenue = sales.filter(s => s.status === 'COMPLETED').reduce((acc, s) => acc + Number(s.total_amount), 0);
    const lastCompletedSale = sales.find(s => s.status === 'COMPLETED') ?? null;

    const formatCurrency = (amount) => formatCurrencyUtil(amount, currency);

    const openReceipt = (saleId) => {
        window.open(route('pharmacy.sales.receipt', saleId), '_blank', 'noopener,noreferrer');
    };

    return (
        <AppLayout>
            <Head title="Ventes" />
            
            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <ShoppingCart className="h-6 w-6" />
                            Gestion des Ventes
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-3 flex-wrap">
                            <span>{sales.length} vente(s) au total</span>
                            {!canViewAllSales && (
                                <span className="text-amber-600 dark:text-amber-400 text-sm font-medium">Vous consultez uniquement vos ventes.</span>
                            )}
                            <span className="text-xs text-gray-400 dark:text-gray-500">Raccourci: <kbd className="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono">1</kbd> cette page · <kbd className="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono">2</kbd> nouvelle vente</span>
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            type="button"
                            disabled={!lastCompletedSale}
                            onClick={() => lastCompletedSale && openReceipt(lastCompletedSale.id)}
                            className="inline-flex items-center gap-2 shrink-0"
                            title={lastCompletedSale ? 'Ouvrir le reçu de la dernière vente terminée pour impression thermique' : 'Aucune vente terminée à imprimer'}
                        >
                            <Printer className="h-4 w-4 shrink-0" />
                            <span className="whitespace-nowrap">Print thermique</span>
                        </Button>
                        <ExportButtons
                            pdfUrl={route('pharmacy.exports.sales.pdf', { from, to, status })}
                            excelUrl={route('pharmacy.exports.sales.excel', { from, to, status })}
                            disabled={!sales.length}
                        />
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('pharmacy.cash-registers.index')} className="inline-flex items-center gap-2">
                                <CashRegister className="h-4 w-4" />
                                Caisses
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('pharmacy.sales.create')} className="inline-flex items-center gap-2">
                                <Plus className="h-4 w-4" />
                                Nouvelle vente
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {/* Total Ventes */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Ventes</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{sales.length}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">toutes périodes</p>
                            </div>
                            <div className="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <ShoppingCart className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    {/* Ventes Terminées */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Terminées</p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{completedSales}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">complétées</p>
                            </div>
                            <div className="h-12 w-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <CheckCircle className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    {/* Brouillons */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Brouillons</p>
                                <p className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{draftSales}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">en attente</p>
                            </div>
                            <div className="h-12 w-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <FileText className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </div>

                    {/* Chiffre d'affaires */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Chiffre d'affaires</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{formatCurrency(totalRevenue)}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">ventes terminées</p>
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
                                    <option value="COMPLETED">Terminée</option>
                                    <option value="CANCELLED">Annulée</option>
                                </select>
                            </div>
                            <Button type="submit" className="inline-flex items-center gap-2">
                                <Search className="h-4 w-4" />
                                Filtrer
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Liste des ventes */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <ShoppingCart className="h-5 w-5 text-gray-500" />
                            Liste des ventes ({sales.length})
                        </h2>
                    </div>

                    {sales.length === 0 ? (
                        <div className="py-12 text-center">
                            <ShoppingCart className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                Aucune vente trouvée
                            </p>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Créez une nouvelle vente pour commencer
                            </p>
                            <Button asChild>
                                <Link href={route('pharmacy.sales.create')} className="inline-flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    Nouvelle vente
                                </Link>
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
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Type
                                        </th>
                                        {canViewAllSales && (
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Vendeur
                                            </th>
                                        )}
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Payé
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Solde
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {sales.map((sale) => (
                                        <tr key={sale.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {sale.created_at}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(sale.status)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {sale.sale_type === 'wholesale' ? (
                                                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                                        Gros
                                                    </Badge>
                                                ) : (
                                                    <Badge className="bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                                        Détail
                                                    </Badge>
                                                )}
                                            </td>
                                            {canViewAllSales && (
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {sale.seller_name ?? '—'}
                                                </td>
                                            )}
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                                {formatCurrency(Number(sale.total_amount))}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-medium">
                                                {formatCurrency(Number(sale.paid_amount))}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                <span className={Number(sale.balance_amount) > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'}>
                                                    {formatCurrency(Number(sale.balance_amount))}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openReceipt(sale.id)}
                                                        className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                        title="Print thermique"
                                                    >
                                                        <Printer className="h-4 w-4" />
                                                    </Button>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        asChild
                                                        className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                    >
                                                        <Link href={route('pharmacy.sales.show', sale.id)} title="Voir">
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
        </AppLayout>
    );
}
