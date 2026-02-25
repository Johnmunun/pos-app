import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    BarChart3,
    DollarSign,
    ArrowUpCircle,
    ArrowDownCircle,
    TrendingUp,
    AlertTriangle,
    FileDown,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function FinanceDashboardIndex({ dashboard, filters }) {
    const currency = dashboard?.currency || 'CDF';
    const [from, setFrom] = useState(filters?.from || '');
    const [to, setTo] = useState(filters?.to || '');

    useEffect(() => {
        setFrom(filters?.from || '');
        setTo(filters?.to || '');
    }, [filters?.from, filters?.to]);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(
            route('finance.dashboard'),
            {
                from: from || undefined,
                to: to || undefined,
            },
            { preserveState: true }
        );
    };

    const fmt = (amount) => formatCurrency(amount || 0, currency);

    const totalRevenue = dashboard?.total_revenue ?? 0;
    const totalExpenses = dashboard?.total_expenses ?? 0;
    const grossProfit = dashboard?.gross_profit ?? 0;
    const marginPercent = dashboard?.margin_percent ?? 0;

    const topProducts = dashboard?.top_10_profitable_products || [];
    const lowMarginProducts = dashboard?.low_margin_products || [];

    return (
        <AppLayout>
            <Head title="Dashboard Finance" />

            <div className="container mx-auto py-6 px-4">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <BarChart3 className="h-6 w-6 text-emerald-500" />
                            Dashboard Finance
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1">
                            Vue d&apos;ensemble des revenus, dépenses et bénéfices.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                window.open(
                                    route('finance.dashboard.export.pdf', {
                                        from: from || undefined,
                                        to: to || undefined,
                                    }),
                                    '_blank',
                                    'noopener,noreferrer'
                                )
                            }
                            className="border-gray-300 dark:border-slate-600"
                        >
                            <FileDown className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>
                </div>

                {/* Filtres période */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <Card className="md:col-span-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-emerald-500" />
                                Période d&apos;analyse
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilter} className="flex flex-col md:flex-row items-end gap-4">
                                <div className="flex-1">
                                    <label htmlFor="from" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Du
                                    </label>
                                    <Input
                                        id="from"
                                        type="date"
                                        value={from}
                                        onChange={(e) => setFrom(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="flex-1">
                                    <label htmlFor="to" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Au
                                    </label>
                                    <Input
                                        id="to"
                                        type="date"
                                        value={to}
                                        onChange={(e) => setTo(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" className="bg-emerald-600 hover:bg-emerald-700 text-white">
                                        Filtrer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                {/* KPIs principaux */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Total revenus
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">{fmt(totalRevenue)}</p>
                            </div>
                            <ArrowUpCircle className="h-8 w-8 text-emerald-500" />
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Total dépenses
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">{fmt(totalExpenses)}</p>
                            </div>
                            <ArrowDownCircle className="h-8 w-8 text-red-500" />
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Bénéfice brut
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">{fmt(grossProfit)}</p>
                            </div>
                            <DollarSign className="h-8 w-8 text-emerald-500" />
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Marge bénéficiaire
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {marginPercent.toFixed(2)}%
                                </p>
                            </div>
                            <TrendingUp className="h-8 w-8 text-indigo-500" />
                        </CardContent>
                    </Card>
                </div>

                {/* Détails produits & dettes */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <Card className="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <BarChart3 className="h-5 w-5 text-emerald-500" />
                                Top 10 produits les plus rentables
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topProducts.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Aucune donnée de vente pour la période sélectionnée.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-slate-700">
                                                <th className="py-2 pr-4">Produit</th>
                                                <th className="py-2 pr-4 text-right">Qté vendue</th>
                                                <th className="py-2 pr-4 text-right">Revenus</th>
                                                <th className="py-2 pr-4 text-right">Bénéfice</th>
                                                <th className="py-2 pr-4 text-right">Marge %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {topProducts.map((p) => (
                                                <tr key={p.product_id} className="border-b border-gray-100 dark:border-slate-800">
                                                    <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                        {p.product_name}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {p.quantity_sold}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {fmt(p.revenue)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {fmt(p.profit)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                        {p.margin_percent.toFixed(2)}%
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <AlertTriangle className="h-5 w-5 text-amber-500" />
                                Produits à faible marge
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {lowMarginProducts.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Aucun produit avec une marge &lt; 10% sur la période.
                                </p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {lowMarginProducts.map((p) => (
                                        <li
                                            key={p.product_id}
                                            className="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-1"
                                        >
                                            <span className="text-gray-900 dark:text-gray-100">{p.product_name}</span>
                                            <span className="text-xs font-medium text-amber-600 dark:text-amber-400">
                                                {p.margin_percent.toFixed(2)}% · {fmt(p.revenue)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

