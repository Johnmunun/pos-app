import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { FileText, Filter, Search, XCircle } from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

const STATUS = [
    { value: '', label: 'Tous les statuts' },
    { value: 'draft', label: 'Brouillon' },
    { value: 'validated', label: 'Validée' },
    { value: 'paid', label: 'Payée' },
];

const SOURCE_TYPES = [
    { value: '', label: 'Toutes les sources' },
    { value: 'sale', label: 'Ventes' },
    { value: 'other', label: 'Autres' },
];

export default function FinanceInvoicesIndex({ invoices = [], filters = {}, pagination }) {
    const [status, setStatus] = useState(filters.status || '');
    const [sourceType, setSourceType] = useState(filters.source_type || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

    useEffect(() => {
        setStatus(filters.status || '');
        setSourceType(filters.source_type || '');
        setFrom(filters.from || '');
        setTo(filters.to || '');
    }, [filters.status, filters.source_type, filters.from, filters.to]);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(
            route('finance.invoices.index'),
            {
                status: status || undefined,
                source_type: sourceType || undefined,
                from: from || undefined,
                to: to || undefined,
                page: 1,
            },
            { preserveState: true }
        );
    };

    const resetFilters = () => {
        setStatus('');
        setSourceType('');
        setFrom('');
        setTo('');
        router.get(route('finance.invoices.index'), {}, { preserveState: false });
    };

    const currentPage = pagination?.current_page ?? 1;
    const lastPage = pagination?.last_page ?? 1;

    const goToPage = (page) => {
        router.get(
            route('finance.invoices.index'),
            {
                ...filters,
                page,
            },
            { preserveState: true }
        );
    };

    const statusBadge = (s) => {
        if (s === 'paid') {
            return <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 text-xs">Payée</Badge>;
        }
        if (s === 'validated') {
            return <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 text-xs">Validée</Badge>;
        }
        if (s === 'draft') {
            return <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs">Brouillon</Badge>;
        }
        return <Badge variant="outline" className="text-xs">{s}</Badge>;
    };

    return (
        <AppLayout>
            <Head title="Factures" />

            <div className="container mx-auto py-6 px-4">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <FileText className="h-6 w-6 text-emerald-500" />
                            Factures Finance
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1">
                            Liste des factures générées par le système (ventes et autres opérations).
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <Filter className="h-5 w-5 text-emerald-500" />
                                Filtres
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilter} className="space-y-4">
                                <div>
                                    <label htmlFor="status" className="text-sm text-gray-700 dark:text-gray-300">
                                        Statut
                                    </label>
                                    <select
                                        id="status"
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="mt-1 w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    >
                                        {STATUS.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="source_type" className="text-sm text-gray-700 dark:text-gray-300">
                                        Source
                                    </label>
                                    <select
                                        id="source_type"
                                        value={sourceType}
                                        onChange={(e) => setSourceType(e.target.value)}
                                        className="mt-1 w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    >
                                        {SOURCE_TYPES.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="from" className="text-sm text-gray-700 dark:text-gray-300">
                                            Du
                                        </label>
                                        <Input
                                            id="from"
                                            type="date"
                                            value={from}
                                            onChange={(e) => setFrom(e.target.value)}
                                            className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="to" className="text-sm text-gray-700 dark:text-gray-300">
                                            Au
                                        </label>
                                        <Input
                                            id="to"
                                            type="date"
                                            value={to}
                                            onChange={(e) => setTo(e.target.value)}
                                            className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button type="submit" className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white">
                                        <Search className="h-4 w-4 mr-2" />
                                        Filtrer
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetFilters}
                                        className="border-gray-300 dark:border-slate-600"
                                    >
                                        <XCircle className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-gray-900 dark:text-gray-100">
                                <span>Factures ({pagination?.total ?? invoices.length})</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {invoices.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Aucune facture pour les filtres sélectionnés.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-slate-700">
                                                <th className="py-2 pr-4">Numéro</th>
                                                <th className="py-2 pr-4">Source</th>
                                                <th className="py-2 pr-4 text-right">Total</th>
                                                <th className="py-2 pr-4 text-right">Payé</th>
                                                <th className="py-2 pr-4 text-center">Statut</th>
                                                <th className="py-2 pr-4">Émise le</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {invoices.map((inv) => (
                                                <tr key={inv.id} className="border-b border-gray-100 dark:border-slate-800">
                                                    <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">{inv.number}</td>
                                                    <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                        {inv.source_type || '-'}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {formatCurrency(inv.total_amount || 0, inv.currency || 'CDF')}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {formatCurrency(inv.paid_amount || 0, inv.currency || 'CDF')}
                                                    </td>
                                                    <td className="py-2 pr-4 text-center">
                                                        {statusBadge(inv.status)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                        {inv.issued_at?.slice(0, 16) || ''}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {lastPage > 1 && (
                                <div className="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <div>
                                        Page {currentPage} sur {lastPage}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage <= 1}
                                            onClick={() => goToPage(currentPage - 1)}
                                        >
                                            Précédent
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage >= lastPage}
                                            onClick={() => goToPage(currentPage + 1)}
                                        >
                                            Suivant
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

