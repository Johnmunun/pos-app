import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import GrabScroll from '@/Components/GrabScroll';
import {
    BarChart3,
    DollarSign,
    ArrowUpCircle,
    ArrowDownCircle,
    TrendingUp,
    AlertTriangle,
    FileDown,
    Calendar,
    Search,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

const cardShell =
    'overflow-hidden rounded-2xl border border-gray-200/80 bg-white/95 shadow-landing-soft backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/80';

function KpiCard({ title, value, icon: Icon, iconClass, accentBorder }) {
    return (
        <Card className={`${cardShell} ${accentBorder}`}>
            <CardContent className="p-5 sm:p-6">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1">
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {title}
                        </p>
                        <p className="text-xl sm:text-2xl font-bold tabular-nums text-gray-900 dark:text-white tracking-tight">
                            {value}
                        </p>
                    </div>
                    <div
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 ${iconClass}`}
                        aria-hidden
                    >
                        <Icon className="h-5 w-5" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

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
        <AppLayout
            header={
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Tableau de bord Finance
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block">
                        Revenus, dépenses et rentabilité sur la période choisie.
                    </p>
                </div>
            }
        >
            <Head title="Dashboard Finance" />

            <div className="py-8 sm:py-10 space-y-6 sm:space-y-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <BarChart3 className="h-7 w-7 text-emerald-500" />
                                Vue d&apos;ensemble
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-2xl leading-relaxed">
                                Analysez la performance financière : marges, produits les plus rentables et alertes de
                                faible marge.
                            </p>
                        </div>
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
                            className="rounded-xl border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 shrink-0"
                        >
                            <FileDown className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>

                    <Card className={`${cardShell} mb-6 sm:mb-8`}>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                <Calendar className="h-5 w-5 mr-2 text-emerald-500" />
                                Période d&apos;analyse
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={handleFilter}
                                className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end"
                            >
                                <div className="md:col-span-4">
                                    <Label htmlFor="from" className="text-gray-700 dark:text-gray-300">
                                        Du
                                    </Label>
                                    <Input
                                        id="from"
                                        type="date"
                                        value={from}
                                        onChange={(e) => setFrom(e.target.value)}
                                        className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="md:col-span-4">
                                    <Label htmlFor="to" className="text-gray-700 dark:text-gray-300">
                                        Au
                                    </Label>
                                    <Input
                                        id="to"
                                        type="date"
                                        value={to}
                                        onChange={(e) => setTo(e.target.value)}
                                        className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="md:col-span-4 flex md:justify-end">
                                    <Button
                                        type="submit"
                                        className="w-full md:w-auto rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                                    >
                                        <Search className="h-4 w-4 mr-2" />
                                        Appliquer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6 sm:mb-8">
                        <KpiCard
                            title="Total revenus"
                            value={fmt(totalRevenue)}
                            icon={ArrowUpCircle}
                            iconClass="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-emerald-500/25"
                            accentBorder="ring-1 ring-emerald-500/10"
                        />
                        <KpiCard
                            title="Total dépenses"
                            value={fmt(totalExpenses)}
                            icon={ArrowDownCircle}
                            iconClass="bg-red-500/15 text-red-600 dark:text-red-400 ring-red-500/25"
                            accentBorder="ring-1 ring-red-500/10"
                        />
                        <KpiCard
                            title="Bénéfice brut"
                            value={fmt(grossProfit)}
                            icon={DollarSign}
                            iconClass="bg-teal-500/15 text-teal-600 dark:text-teal-400 ring-teal-500/25"
                            accentBorder="ring-1 ring-teal-500/10"
                        />
                        <KpiCard
                            title="Marge bénéficiaire"
                            value={`${marginPercent.toFixed(2)}%`}
                            icon={TrendingUp}
                            iconClass="bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 ring-indigo-500/25"
                            accentBorder="ring-1 ring-indigo-500/10"
                        />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <Card className={`lg:col-span-2 ${cardShell}`}>
                            <CardHeader>
                                <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                    <BarChart3 className="h-5 w-5 mr-2 text-emerald-500" />
                                    Top 10 produits les plus rentables
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {topProducts.length === 0 ? (
                                    <div className="text-center py-14 px-4 rounded-xl border border-dashed border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-950/30">
                                        <BarChart3 className="h-12 w-12 text-gray-300 dark:text-slate-600 mx-auto mb-3" />
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            Aucune donnée pour cette période
                                        </p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                                            Ajustez les dates ou attendez des ventes enregistrées sur l&apos;intervalle
                                            sélectionné.
                                        </p>
                                    </div>
                                ) : (
                                    <GrabScroll className="rounded-xl border border-gray-100/90 bg-gray-50/30 dark:border-slate-700/60 dark:bg-slate-950/30">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                            <thead className="bg-gray-50 dark:bg-slate-800">
                                                <tr>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Produit
                                                    </th>
                                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Qté
                                                    </th>
                                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Revenus
                                                    </th>
                                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Bénéfice
                                                    </th>
                                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Marge %
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                                {topProducts.map((p) => (
                                                    <tr
                                                        key={p.product_id}
                                                        className="hover:bg-gray-50 dark:hover:bg-slate-800/80"
                                                    >
                                                        <td className="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                                            {p.product_name}
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-gray-700 dark:text-gray-300 tabular-nums">
                                                            {p.quantity_sold}
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-gray-700 dark:text-gray-300 tabular-nums">
                                                            {fmt(p.revenue)}
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400 font-medium tabular-nums">
                                                            {fmt(p.profit)}
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-gray-700 dark:text-gray-300 tabular-nums">
                                                            {p.margin_percent.toFixed(2)}%
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </GrabScroll>
                                )}
                            </CardContent>
                        </Card>

                        <Card className={cardShell}>
                            <CardHeader>
                                <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                    <AlertTriangle className="h-5 w-5 mr-2 text-amber-500" />
                                    Faible marge
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {lowMarginProducts.length === 0 ? (
                                    <div className="text-center py-10 px-3 rounded-xl border border-dashed border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-950/30">
                                        <AlertTriangle className="h-10 w-10 text-amber-200 dark:text-amber-900/50 mx-auto mb-2" />
                                        <p className="text-sm text-gray-600 dark:text-gray-300">
                                            Aucun produit sous les 10% de marge sur cette période.
                                        </p>
                                    </div>
                                ) : (
                                    <ul className="space-y-2">
                                        {lowMarginProducts.map((p) => (
                                            <li
                                                key={p.product_id}
                                                className="flex items-center justify-between gap-3 rounded-xl border border-gray-100 dark:border-slate-700/80 bg-white/80 dark:bg-slate-900/50 px-3 py-2.5"
                                            >
                                                <span className="text-sm font-medium text-gray-900 dark:text-white truncate min-w-0">
                                                    {p.product_name}
                                                </span>
                                                <span className="text-xs font-semibold text-amber-600 dark:text-amber-400 shrink-0 tabular-nums">
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
            </div>
        </AppLayout>
    );
}
