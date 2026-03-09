import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Package,
    Users,
    DollarSign,
    ShoppingCart,
    TrendingUp,
    AlertTriangle,
    Filter,
    FileDown,
    BarChart3,
} from 'lucide-react';
import {
    LineChart,
    Line,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend,
} from 'recharts';
import { formatCurrency } from '@/lib/currency';

export default function EcommerceDashboard({ stats = {}, chartOrders = [], chartOrderStatus = [], filters = {} }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);
    const orders = stats.orders || {};
    const customers = stats.customers || {};
    const revenue = stats.revenue || {};
    const alerts = stats.alerts || [];

    const [showFilters, setShowFilters] = useState(false);
    const defaultTo = new Date().toISOString().slice(0, 10);
    const defaultFrom = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
    const [dateFrom, setDateFrom] = useState(filters.from || defaultFrom);
    const [dateTo, setDateTo] = useState(filters.to || defaultTo);

    useEffect(() => {
        if (filters.from) setDateFrom(filters.from);
        if (filters.to) setDateTo(filters.to);
    }, [filters.from, filters.to]);

    const handleFilter = () => {
        router.get(route('ecommerce.dashboard'), {
            from: dateFrom || undefined,
            to: dateTo || undefined,
        }, { preserveScroll: true });
        setShowFilters(false);
    };

    const exportPdfUrl = route('ecommerce.reports.export-sales-pdf') +
        (dateFrom && dateTo ? `?from=${dateFrom}&to=${dateTo}` : '');

    const salesChartData = (chartOrders || []).map((d) => ({
        date: d.date,
        dateShort: new Date(d.date + 'T12:00:00').toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }),
        total: Number(d.revenue ?? 0),
        count: Number(d.count ?? 0),
    }));

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight break-words min-w-0">
                        Tableau de bord E-commerce
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="gap-2"
                            onClick={() => setShowFilters(!showFilters)}
                        >
                            <Filter className="h-4 w-4" />
                            Filtrer
                        </Button>
                        <a
                            href={exportPdfUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-slate-700"
                        >
                            <FileDown className="h-4 w-4 shrink-0" />
                            Export PDF
                        </a>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard E-commerce" />

            <div className="py-6 space-y-6">
                {/* Panneau filtre (dates) */}
                {showFilters && (
                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <CardContent className="pt-6">
                            <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Filtrer par période (pour le graphique et l&apos;export PDF)
                            </p>
                            <div className="flex flex-wrap items-end gap-3">
                                <div>
                                    <label className="block text-xs text-gray-500 dark:text-gray-400 mb-1">Du</label>
                                    <Input
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                        className="w-40"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 dark:text-gray-400 mb-1">Au</label>
                                    <Input
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                        className="w-40"
                                    />
                                </div>
                                <Button onClick={handleFilter} size="sm">
                                    Appliquer
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Alertes */}
                {alerts.length > 0 && (
                    <div className="space-y-2">
                        {alerts.map((alert, i) => (
                            <div
                                key={i}
                                className={`flex items-center gap-2 p-4 rounded-lg ${
                                    alert.type === 'warning'
                                        ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800'
                                        : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'
                                }`}
                            >
                                <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0" />
                                <span className="text-sm text-gray-800 dark:text-gray-200">
                                    {alert.message}
                                </span>
                            </div>
                        ))}
                    </div>
                )}

                {/* Cartes KPIs - Style Global Commerce */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <Card className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-lg">
                        <CardContent className="p-5 md:p-6">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <p className="text-blue-100 text-sm font-medium mb-2">TOTAL COMMANDES</p>
                                    <p className="text-white text-2xl md:text-3xl font-bold mb-2">{orders.total ?? 0}</p>
                                    <p className="text-blue-100 text-xs">{orders.today ?? 0} aujourd&apos;hui • {orders.pending ?? 0} en attente</p>
                                </div>
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <ShoppingCart className="h-6 w-6 md:h-7 md:w-7 text-white" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-gradient-to-br from-violet-500 to-violet-600 dark:from-violet-600 dark:to-violet-700 border-0 shadow-lg">
                        <CardContent className="p-5 md:p-6">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <p className="text-violet-100 text-sm font-medium mb-2">CLIENTS</p>
                                    <p className="text-white text-2xl md:text-3xl font-bold mb-2">{customers.total ?? 0}</p>
                                    <p className="text-violet-100 text-xs">{customers.active ?? 0} actifs</p>
                                </div>
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <Users className="h-6 w-6 md:h-7 md:w-7 text-white" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-lg">
                        <CardContent className="p-5 md:p-6">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <p className="text-emerald-100 text-sm font-medium mb-2">REVENUS AUJOURD&apos;HUI</p>
                                    <p className="text-white text-2xl md:text-3xl font-bold mb-2">{fmt(revenue.today ?? 0)}</p>
                                    <p className="text-emerald-100 text-xs">Commandes payées</p>
                                </div>
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <DollarSign className="h-6 w-6 md:h-7 md:w-7 text-white" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-gradient-to-br from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 border-0 shadow-lg">
                        <CardContent className="p-5 md:p-6">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <p className="text-amber-100 text-sm font-medium mb-2">REVENUS (7 JOURS)</p>
                                    <p className="text-white text-2xl md:text-3xl font-bold mb-2">{fmt(revenue.last_7_days ?? 0)}</p>
                                    <p className="text-amber-100 text-xs">Derniers 7 jours</p>
                                </div>
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <TrendingUp className="h-6 w-6 md:h-7 md:w-7 text-white" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Graphiques Recharts - Style Global Commerce */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Évolution des revenus */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4">
                        <CardHeader className="pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                                    Évolution des revenus
                                </CardTitle>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Chiffre d&apos;affaires quotidien sur la période filtrée
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="h-72">
                            {salesChartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={salesChartData} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-600" />
                                        <XAxis
                                            dataKey="dateShort"
                                            tick={{ fontSize: 11 }}
                                            className="text-gray-600 dark:text-gray-400"
                                        />
                                        <YAxis
                                            tickFormatter={(v) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : v)}
                                            tick={{ fontSize: 11 }}
                                        />
                                        <Tooltip
                                            formatter={(value) => [fmt(Number(value)), 'Revenus']}
                                            labelFormatter={(_, payload) => payload[0]?.payload?.date && new Date(payload[0].payload.date).toLocaleDateString('fr-FR')}
                                            contentStyle={{ borderRadius: 8 }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="total"
                                            stroke="#10b981"
                                            strokeWidth={2}
                                            dot={{ fill: '#10b981', r: 3 }}
                                            name="Revenus"
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                                    Aucune vente sur la période
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Commandes par jour */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4">
                        <CardHeader className="pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                                    Commandes par jour
                                </CardTitle>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Nombre de commandes payées par jour
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="h-72">
                            {salesChartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={salesChartData} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-600" />
                                        <XAxis
                                            dataKey="dateShort"
                                            tick={{ fontSize: 11 }}
                                            className="text-gray-600 dark:text-gray-400"
                                        />
                                        <YAxis tick={{ fontSize: 11 }} />
                                        <Tooltip
                                            formatter={(value) => [value, 'Commandes']}
                                            labelFormatter={(_, payload) => payload[0]?.payload?.date && new Date(payload[0].payload.date).toLocaleDateString('fr-FR')}
                                            contentStyle={{ borderRadius: 8 }}
                                        />
                                        <Bar dataKey="count" fill="#8b5cf6" name="Commandes" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                                    Aucune commande sur la période
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Répartition des statuts (sur 2 colonnes si lg) */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4 lg:col-span-2">
                        <CardHeader className="pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                                    Répartition des statuts
                                </CardTitle>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Distribution des commandes par statut (en attente, confirmé, livré, etc.)
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="h-72">
                            {chartOrderStatus && chartOrderStatus.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={chartOrderStatus}
                                            dataKey="value"
                                            nameKey="name"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={80}
                                            label={({ name, value }) => `${name}: ${value}`}
                                        >
                                            {chartOrderStatus.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.fill} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value) => {
                                                const v = Array.isArray(value) ? value[0] : value;
                                                return [v, 'Commandes'];
                                            }}
                                        />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                                    Aucune commande
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Actions rapides - Style Global Commerce */}
                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                            ACTIONS RAPIDES
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Link
                                href={route('ecommerce.orders.index')}
                                className="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <div className="w-12 h-12 rounded-lg bg-blue-500 hover:bg-blue-600 flex items-center justify-center mb-2">
                                    <ShoppingCart className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                                    Commandes
                                </span>
                            </Link>
                            <Link
                                href={route('ecommerce.reports.index')}
                                className="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <div className="w-12 h-12 rounded-lg bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center mb-2">
                                    <FileDown className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                                    Rapports PDF
                                </span>
                            </Link>
                            <Link
                                href={route('ecommerce.customers.index')}
                                className="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <div className="w-12 h-12 rounded-lg bg-violet-500 hover:bg-violet-600 flex items-center justify-center mb-2">
                                    <Users className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                                    Clients
                                </span>
                            </Link>
                            <Link
                                href={route('ecommerce.catalog.index')}
                                className="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <div className="w-12 h-12 rounded-lg bg-amber-500 hover:bg-amber-600 flex items-center justify-center mb-2">
                                    <Package className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                                    Catalogue
                                </span>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
