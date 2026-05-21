import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { BarChart3, Download, FileSpreadsheet, ArrowRight } from 'lucide-react';
import { cardShell, pageY } from '@/lib/layoutClasses';

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(amount);
}

export default function EcommerceReportsIndex({
    chartData = [],
    revenue = 0,
    orderCount = 0,
    topProducts = [],
    currency = 'USD',
    filters = {},
}) {
    const maxRevenue = Math.max(...chartData.map((d) => d.revenue), 1);
    const from = filters.from || '';
    const to = filters.to || '';
    const exportParams = from && to ? `?from=${from}&to=${to}` : '';

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full min-w-0">
                    <div className="min-w-0">
                        <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight">
                            Rapports E-commerce
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5 max-w-2xl hidden sm:block">
                            Synthèse des ventes et exports sur la période filtrée.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 shrink-0">
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={route('ecommerce.reports.export-sales-excel') + exportParams}
                                className="inline-flex items-center gap-2"
                            >
                                <FileSpreadsheet className="h-4 w-4 shrink-0" />
                                Export Excel
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={route('ecommerce.reports.export-sales-pdf') + exportParams}
                                className="inline-flex items-center gap-2"
                            >
                                <Download className="h-4 w-4 shrink-0" />
                                Export PDF
                            </a>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={route('ecommerce.dashboard')}>
                                Tableau de bord
                                <ArrowRight className="h-4 w-4 ml-2" />
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Rapports - E-commerce" />

            <div className={pageY}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card className={cardShell}>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Revenus (commandes payées)</p>
                            <p className="text-3xl font-bold text-gray-900 dark:text-white">
                                {formatCurrency(revenue, currency)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className={cardShell}>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Nombre de commandes</p>
                            <p className="text-3xl font-bold text-gray-900 dark:text-white">{orderCount}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Graphique barres */}
                {chartData.length > 0 && (
                    <Card className={cardShell}>
                        <CardContent className="pt-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <BarChart3 className="h-5 w-5" />
                                Revenus par jour
                            </h3>
                            <div className="flex items-end gap-1 h-48">
                                {chartData.slice(-14).map((d, i) => (
                                    <div
                                        key={d.date}
                                        className="flex-1 flex flex-col items-center gap-1"
                                        title={`${d.date}: ${formatCurrency(d.revenue, currency)}`}
                                    >
                                        <div
                                            className="w-full rounded-t bg-blue-500 dark:bg-blue-600 min-h-[4px]"
                                            style={{ height: `${Math.max(4, (d.revenue / maxRevenue) * 100)}%` }}
                                        />
                                        <span className="text-xs text-gray-500 truncate max-w-full" title={d.date}>
                                            {d.date.slice(8, 10)}/{d.date.slice(5, 7)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Top produits */}
                {topProducts.length > 0 && (
                    <Card className={cardShell}>
                        <CardContent className="pt-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Produits les plus vendus
                            </h3>
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-slate-700">
                                        <th className="text-left py-2 font-medium text-gray-700 dark:text-gray-300">Produit</th>
                                        <th className="text-right py-2 font-medium text-gray-700 dark:text-gray-300">Quantité</th>
                                        <th className="text-right py-2 font-medium text-gray-700 dark:text-gray-300">Revenus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topProducts.map((p, i) => (
                                        <tr key={i} className="border-b border-gray-100 dark:border-slate-700/50">
                                            <td className="py-2">{p.product_name}</td>
                                            <td className="py-2 text-right">{p.quantity}</td>
                                            <td className="py-2 text-right font-medium">
                                                {formatCurrency(p.revenue, currency)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                )}

                {chartData.length === 0 && topProducts.length === 0 && (
                    <Card className={cardShell}>
                        <CardContent className="py-12 text-center text-gray-500">
                            Aucune donnée sur la période. Modifiez les filtres de date ou consultez le tableau de bord.
                            <div className="mt-4">
                                <Link href={route('ecommerce.dashboard')} className="text-blue-600 hover:underline">
                                    Tableau de bord
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
            </div>
        </AppLayout>
    );
}
