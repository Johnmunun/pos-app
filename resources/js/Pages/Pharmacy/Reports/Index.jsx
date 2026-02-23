import React, { useState } from 'react';
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
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function ReportsIndex({ report, filters }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);

    const [from, setFrom] = useState(filters?.from || '');
    const [to, setTo] = useState(filters?.to || '');

    const handleApply = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.reports.index'), { from, to }, { preserveState: true });
    };

    const sales = report?.sales || {};
    const stock = report?.stock || {};

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Rapports Pharmacy
                </h2>
            }
        >
            <Head title="Rapports Pharmacy" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filtres */}
                    <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2">
                                <BarChart3 className="h-4 w-4" />
                                Période
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleApply} className="flex flex-wrap items-end gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="from">Du</Label>
                                    <Input
                                        id="from"
                                        type="date"
                                        value={from}
                                        onChange={(e) => setFrom(e.target.value)}
                                        className="max-w-[180px]"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="to">Au</Label>
                                    <Input
                                        id="to"
                                        type="date"
                                        value={to}
                                        onChange={(e) => setTo(e.target.value)}
                                        className="max-w-[180px]"
                                    />
                                </div>
                                <Button type="submit" size="sm">
                                    <RefreshCw className="h-4 w-4 mr-2" />
                                    Actualiser
                                </Button>
                            </form>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Données mises en cache 5 min pour les performances.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Stats Ventes */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Chiffre d&apos;affaires
                                </CardTitle>
                                <DollarSign className="h-4 w-4 text-green-500 dark:text-green-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {fmt(sales.total || 0)}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Période sélectionnée
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Nombre de ventes
                                </CardTitle>
                                <ShoppingCart className="h-4 w-4 text-blue-500 dark:text-blue-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {sales.count ?? 0}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Ventes complétées
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Valeur du stock
                                </CardTitle>
                                <Package className="h-4 w-4 text-amber-500 dark:text-amber-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {fmt(stock.total_value || 0)}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {stock.product_count ?? 0} produits actifs
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Stock bas
                                </CardTitle>
                                <AlertTriangle className="h-4 w-4 text-orange-500 dark:text-orange-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {stock.low_stock_count ?? 0}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Produits à réapprovisionner
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Ventes par jour */}
                    {sales.by_day && sales.by_day.length > 0 && (
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <TrendingUp className="h-5 w-5" />
                                    Ventes par jour
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-200">
                                                    Date
                                                </th>
                                                <th className="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-200">
                                                    Ventes
                                                </th>
                                                <th className="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-200">
                                                    CA
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sales.by_day.map((row) => (
                                                <tr
                                                    key={row.date}
                                                    className="border-b border-gray-100 dark:border-slate-800"
                                                >
                                                    <td className="py-2 px-3 text-gray-900 dark:text-white">
                                                        {row.date}
                                                    </td>
                                                    <td className="py-2 px-3 text-right text-gray-700 dark:text-gray-300">
                                                        {row.count}
                                                    </td>
                                                    <td className="py-2 px-3 text-right font-medium text-gray-900 dark:text-white">
                                                        {fmt(row.total)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
