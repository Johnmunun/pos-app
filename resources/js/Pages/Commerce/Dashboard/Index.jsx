import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Store, ShoppingCart, Receipt, TrendingUp } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { cardShell, pageY } from '@/lib/layoutClasses';

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
                        <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <Store className="h-6 w-6 text-amber-500 shrink-0" />
                            Dashboard Commerce
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed max-w-2xl">
                            Vue rapide des ventes et achats Global Commerce — accès direct aux listes.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="text-sm rounded-xl border-gray-300 dark:border-slate-600">
                            <Link href={route('commerce.sales.index')}>Voir les ventes</Link>
                        </Button>
                        <Button asChild variant="outline" className="text-sm rounded-xl border-gray-300 dark:border-slate-600">
                            <Link href={route('commerce.purchases.index')}>Voir les achats</Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard Commerce" />
            <div className={pageY}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                        <Card className={cardShell}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-2">
                                    <ShoppingCart className="h-4 w-4 text-emerald-500" />
                                    Ventes du jour
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                                    {salesToday.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {salesToday.count} vente(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className={cardShell}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-2">
                                    <ShoppingCart className="h-4 w-4 text-indigo-500" />
                                    Ventes 7 jours
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                                    {salesLast7.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {salesLast7.count} vente(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className={cardShell}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-2">
                                    <Receipt className="h-4 w-4 text-amber-500" />
                                    Achats du jour
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                                    {purchasesToday.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {purchasesToday.count} bon(s)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className={cardShell}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-2">
                                    <TrendingUp className="h-4 w-4 text-emerald-500" />
                                    Achats 7 jours
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                                    {purchasesLast7.total} {currency}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {purchasesLast7.count} bon(s)
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className={cardShell}>
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
                                            contentStyle={{ borderRadius: 12 }}
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
                                <div className="h-full flex items-center justify-center rounded-xl border border-dashed border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-950/30 text-gray-500 dark:text-gray-400 text-sm px-4 text-center">
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
