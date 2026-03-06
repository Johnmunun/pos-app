import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Store, ShoppingCart, Receipt, TrendingUp } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function CommerceDashboardIndex({ dashboard }) {
    const currency = dashboard?.currency || 'USD';
    const salesToday = dashboard?.sales_today || { total: 0, count: 0 };
    const salesLast7 = dashboard?.sales_last_7 || { total: 0, count: 0 };
    const purchasesToday = dashboard?.purchases_today || { total: 0, count: 0 };
    const purchasesLast7 = dashboard?.purchases_last_7 || { total: 0, count: 0 };
    const chartLast7 = dashboard?.chart_last_7 || [];

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-2">
                            <Store className="h-5 w-5 text-emerald-500" />
                            Dashboard Commerce
                        </h2>
                        <p className="text-gray-500 dark:text-gray-400 text-sm mt-1">
                            Vue rapide des ventes et achats Global Commerce.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="text-sm">
                            <Link href={route('commerce.sales.index')}>Voir les ventes</Link>
                        </Button>
                        <Button asChild variant="outline" className="text-sm">
                            <Link href={route('commerce.purchases.index')}>Voir les achats</Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                                    <ShoppingCart className="h-4 w-4 text-emerald-500" />
                                    Ventes du jour
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {salesToday.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {salesToday.count} vente(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                                    <ShoppingCart className="h-4 w-4 text-indigo-500" />
                                    Ventes 7 jours
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {salesLast7.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {salesLast7.count} vente(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                                    <Receipt className="h-4 w-4 text-amber-500" />
                                    Achats du jour
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {purchasesToday.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {purchasesToday.count} bon(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                                    <TrendingUp className="h-4 w-4 text-emerald-500" />
                                    Achats 7 jours
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {purchasesLast7.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {purchasesLast7.count} bon(s)
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                                <TrendingUp className="h-5 w-5 text-emerald-500" />
                                Ventes / Achats — 7 derniers jours
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="h-72">
                            {chartLast7.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={chartLast7} margin={{ top: 8, right: 12, left: 0, bottom: 4 }}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            className="stroke-gray-200 dark:stroke-gray-600"
                                        />
                                        <XAxis
                                            dataKey="date"
                                            tick={{ fontSize: 11 }}
                                            className="text-gray-600 dark:text-gray-400"
                                        />
                                        <YAxis tick={{ fontSize: 11 }} />
                                        <Tooltip
                                            formatter={(value, name) => {
                                                const label =
                                                    name === 'sales_total'
                                                        ? 'Ventes'
                                                        : name === 'purchases_total'
                                                        ? 'Achats'
                                                        : name;
                                                return [`${value} ${currency}`, label];
                                            }}
                                            labelFormatter={(label) =>
                                                new Date(label).toLocaleDateString('fr-FR')
                                            }
                                            contentStyle={{ borderRadius: 8 }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="sales_total"
                                            stroke="#10b981"
                                            strokeWidth={2}
                                            dot={{ r: 3 }}
                                            name="Ventes"
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="purchases_total"
                                            stroke="#6366f1"
                                            strokeWidth={2}
                                            dot={{ r: 3 }}
                                            name="Achats"
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                                    Pas encore de données sur les 7 derniers jours.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

