import React, { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    BarChart3,
    DollarSign,
    Package,
    ShoppingCart,
    TrendingUp,
    AlertTriangle,
    RefreshCw,
    Calendar,
    Truck,
    ArrowDownCircle,
    ArrowUpCircle,
    SlidersHorizontal,
    FileText,
    FileDown,
    FileSpreadsheet,
    Trophy,
    ArrowUpDown,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

function getDefaultFromTo() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const from = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`;
    const to = d.toISOString().slice(0, 10);
    return { from, to };
}

export default function ReportsIndex({ report, filters, routePrefix = 'pharmacy' }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);

    const defaultBounds = getDefaultFromTo();
    const periodFromServer = report?.period || {};

    const [dateFrom, setDateFrom] = useState(periodFromServer.from || filters?.from || defaultBounds.from);
    const [dateTo, setDateTo] = useState(periodFromServer.to || filters?.to || defaultBounds.to);

    const handleApply = (e) => {
        e.preventDefault();
        router.get(route(`${routePrefix}.reports.index`), { from: dateFrom, to: dateTo }, { preserveState: true });
    };

    const sales = report?.sales || {};
    const purchases = report?.purchases || {};
    const movements = report?.movements || {};
    const stock = report?.stock || {};
    const productsAnalysis = report?.products_analysis || [];
    const period = report?.period || { from: filters?.from, to: filters?.to };

    const [productSort, setProductSort] = useState({ key: 'revenue', dir: 'desc' });
    const sortedProducts = useMemo(() => {
        const list = [...productsAnalysis];
        const k = productSort.key;
        const d = productSort.dir === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            const va = a[k];
            const bv = b[k];
            if (typeof va === 'number' && typeof bv === 'number') return d * (va - bv);
            if (va == null && bv == null) return 0;
            if (va == null) return d;
            if (bv == null) return -d;
            return d * String(va).localeCompare(String(bv));
        });
        return list;
    }, [productsAnalysis, productSort]);
    const toggleSort = (key) => {
        setProductSort(prev => (prev.key === key ? { key, dir: prev.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'desc' }));
    };

    const periodLabel = useMemo(() => {
        if (!period?.from || !period?.to) return 'Période';
        if (period.from === period.to) return period.from;
        return `${period.from} → ${period.to}`;
    }, [period]);

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Rapport d&apos;activité
                </h2>
            }
        >
            <Head title="Rapport d'activité - Pharmacy" />

            <div className="py-6 space-y-6">
                    {/* Filtres */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 shadow-sm">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <SlidersHorizontal className="h-4 w-4 text-amber-500" />
                                Période du rapport
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleApply} className="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-4">
                                <div className="space-y-2 w-full sm:w-auto sm:min-w-[160px]">
                                    <Label htmlFor="date_from" className="text-gray-700 dark:text-gray-300">Date début</Label>
                                    <Input
                                        id="date_from"
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                        className="h-10 w-full"
                                    />
                                </div>
                                <div className="space-y-2 w-full sm:w-auto sm:min-w-[160px]">
                                    <Label htmlFor="date_to" className="text-gray-700 dark:text-gray-300">Date fin</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                        className="h-10 w-full"
                                    />
                                </div>
                                <Button type="submit" size="sm" className="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white h-10 px-4">
                                    <RefreshCw className="h-4 w-4 mr-2" />
                                    Générer le rapport
                                </Button>
                            </form>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                Période : <strong className="text-gray-700 dark:text-gray-300">{periodLabel}</strong>
                            </p>
                        </CardContent>
                    </Card>

                    {/* En-tête du rapport */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                                <FileText className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-gray-900 dark:text-white">Rapport d&apos;activité</h1>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{periodLabel}</p>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span className="flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                Généré le {new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-9"
                                    asChild
                                >
                                    <a
                                        href={route(`${routePrefix}.exports.reports.pdf`, { from: dateFrom, to: dateTo })}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <FileDown className="h-4 w-4 mr-1.5" />
                                        Exporter PDF
                                    </a>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-9"
                                    asChild
                                >
                                    <a
                                        href={route(`${routePrefix}.exports.reports.excel`, { from: dateFrom, to: dateTo })}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <FileSpreadsheet className="h-4 w-4 mr-1.5" />
                                        Exporter Excel
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* KPIs */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Chiffre d&apos;affaires</CardTitle>
                                <DollarSign className="h-4 w-4 text-green-500 dark:text-green-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-gray-900 dark:text-white">{fmt(sales.total || 0)}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">{sales.count ?? 0} ventes</p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Ventes</CardTitle>
                                <ShoppingCart className="h-4 w-4 text-blue-500 dark:text-blue-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-gray-900 dark:text-white">{sales.count ?? 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">Transactions complétées</p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Achats reçus</CardTitle>
                                <Truck className="h-4 w-4 text-indigo-500 dark:text-indigo-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-gray-900 dark:text-white">{purchases.count ?? 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">{fmt(purchases.total || 0)}</p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Mouvements stock</CardTitle>
                                <BarChart3 className="h-4 w-4 text-slate-500 dark:text-slate-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-gray-900 dark:text-white">{movements.total_ops ?? 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">Entrées / Sorties / Réglages</p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Valeur stock</CardTitle>
                                <Package className="h-4 w-4 text-amber-500 dark:text-amber-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-gray-900 dark:text-white">{fmt(stock.total_value || 0)}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">{stock.product_count ?? 0} produits actifs</p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Stock bas</CardTitle>
                                <AlertTriangle className="h-4 w-4 text-orange-500 dark:text-orange-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold text-orange-600 dark:text-orange-400">{stock.low_stock_count ?? 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">À réapprovisionner</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Détail mouvements de stock */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <TrendingUp className="h-5 w-5 text-amber-500" />
                                Synthèse des mouvements de stock
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="flex items-center gap-3 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                    <ArrowDownCircle className="h-8 w-8 text-green-600 dark:text-green-400 shrink-0" />
                                    <div>
                                        <p className="text-sm font-medium text-green-800 dark:text-green-200">Entrées</p>
                                        <p className="text-2xl font-bold text-green-700 dark:text-green-300">+{movements.qty_in ?? 0}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                    <ArrowUpCircle className="h-8 w-8 text-red-600 dark:text-red-400 shrink-0" />
                                    <div>
                                        <p className="text-sm font-medium text-red-800 dark:text-red-200">Sorties</p>
                                        <p className="text-2xl font-bold text-red-700 dark:text-red-300">-{movements.qty_out ?? 0}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <SlidersHorizontal className="h-8 w-8 text-slate-600 dark:text-slate-400 shrink-0" />
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-300">Réglages</p>
                                        <p className="text-2xl font-bold text-slate-700 dark:text-slate-300">{movements.qty_adjustment ?? 0}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Analyse par produit */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <Trophy className="h-5 w-5 text-amber-500" />
                                Analyse par produit
                            </CardTitle>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Par période : qui rapporte le plus, produits les plus vendus, bénéfice et marge (si coût renseigné).
                            </p>
                        </CardHeader>
                        <CardContent>
                            {sortedProducts.length > 0 ? (
                                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Produit</th>
                                                <th className="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Code</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">
                                                    <button type="button" onClick={() => toggleSort('qty_sold')} className="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                                        Qté vendue <ArrowUpDown className="h-3.5 w-3.5" />
                                                    </button>
                                                </th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">
                                                    <button type="button" onClick={() => toggleSort('revenue')} className="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                                        Chiffre d&apos;affaires <ArrowUpDown className="h-3.5 w-3.5" />
                                                    </button>
                                                </th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Coût</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">
                                                    <button type="button" onClick={() => toggleSort('benefit')} className="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                                        Bénéfice <ArrowUpDown className="h-3.5 w-3.5" />
                                                    </button>
                                                </th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">
                                                    <button type="button" onClick={() => toggleSort('margin_percent')} className="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                                        Marge % <ArrowUpDown className="h-3.5 w-3.5" />
                                                    </button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sortedProducts.map((row) => (
                                                <tr key={row.product_id} className="border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                                    <td className="py-3 px-4 text-gray-900 dark:text-white font-medium">{row.product_name}</td>
                                                    <td className="py-3 px-4 text-gray-600 dark:text-gray-400">{row.product_code || '—'}</td>
                                                    <td className="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{row.qty_sold}</td>
                                                    <td className="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{fmt(row.revenue)}</td>
                                                    <td className="py-3 px-4 text-right text-gray-600 dark:text-gray-400">{row.cost != null ? fmt(row.cost) : '—'}</td>
                                                    <td className="py-3 px-4 text-right">
                                                        {row.benefit != null ? (
                                                            <span className={row.benefit >= 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-red-600 dark:text-red-400 font-medium'}>
                                                                {fmt(row.benefit)}
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400 dark:text-gray-500">—</span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 text-right">
                                                        {row.margin_percent != null ? (
                                                            <span className={row.margin_percent >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}>
                                                                {row.margin_percent} %
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400 dark:text-gray-500">—</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-500 dark:text-gray-400 py-6 text-center">Aucune vente par produit sur la période.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Ventes par jour */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <ShoppingCart className="h-5 w-5 text-blue-500" />
                                Ventes par jour
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {sales.by_day && sales.by_day.length > 0 ? (
                                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Date</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Nombre de ventes</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Chiffre d&apos;affaires</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sales.by_day.map((row) => (
                                                <tr key={row.date} className="border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                                    <td className="py-3 px-4 text-gray-900 dark:text-white font-medium">{row.date}</td>
                                                    <td className="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{row.count}</td>
                                                    <td className="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{fmt(row.total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-500 dark:text-gray-400 py-6 text-center">Aucune vente sur la période.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Achats par jour */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <Truck className="h-5 w-5 text-indigo-500" />
                                Achats reçus par jour
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {purchases.by_day && purchases.by_day.length > 0 ? (
                                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Date</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Nombre</th>
                                                <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-200">Montant total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {purchases.by_day.map((row) => (
                                                <tr key={row.date} className="border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                                    <td className="py-3 px-4 text-gray-900 dark:text-white font-medium">{row.date}</td>
                                                    <td className="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{row.count}</td>
                                                    <td className="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{fmt(row.total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-500 dark:text-gray-400 py-6 text-center">Aucun achat reçu sur la période.</p>
                            )}
                        </CardContent>
                    </Card>
            </div>
        </AppLayout>
    );
}
