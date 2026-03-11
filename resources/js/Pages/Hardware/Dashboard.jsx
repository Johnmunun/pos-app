import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
  Package,
  AlertTriangle,
  DollarSign,
  ShoppingCart,
  Plus,
  BarChart3,
  TrendingUp,
  Layers,
  FileText,
  Truck,
  Users,
  Tag,
  Zap,
  Calendar,
  Clock,
  Filter,
  Database,
  ArrowUp,
  ArrowDown,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';
import {
  LineChart,
  Line,
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

function hasPermission(permissions, permOrPerms) {
  if (!Array.isArray(permissions)) return false;
  if (permissions.includes('*')) return true;
  const perms = Array.isArray(permOrPerms) ? permOrPerms : [permOrPerms];
  return perms.some((perm) =>
    permissions.some(
      (p) =>
        p === perm ||
        p === perm.replace('.view', '.manage') ||
        p === perm.replace('.manage', '.view')
    )
  );
}

const QUICK_ACTIONS = [
  {
    label: 'Nouvelle vente',
    route: 'hardware.sales.create',
    icon: Plus,
    permission: 'hardware.sales.manage',
    color: 'emerald',
  },
  {
    label: 'Voir les ventes',
    route: 'hardware.sales.index',
    icon: ShoppingCart,
    permission: 'hardware.sales.view',
    color: 'blue',
  },
  {
    label: 'Ajouter un produit',
    route: 'hardware.products.create',
    icon: Package,
    permission: 'hardware.product.manage',
    color: 'amber',
  },
  {
    label: 'Voir les produits',
    route: 'hardware.products',
    icon: Package,
    permission: 'hardware.product.view',
    color: 'slate',
  },
  {
    label: 'Stock',
    route: 'hardware.stock.index',
    icon: Layers,
    permission: 'hardware.stock.view',
    color: 'violet',
  },
  {
    label: 'Mouvements',
    route: 'hardware.stock.movements.index',
    icon: BarChart3,
    permission: 'hardware.stock.movement.view',
    color: 'indigo',
  },
  {
    label: 'Rapports',
    route: 'hardware.reports.index',
    icon: FileText,
    permission: 'hardware.report.view',
    color: 'cyan',
  },
  {
    label: 'Bons de commande',
    route: 'hardware.purchases.index',
    icon: Truck,
    permission: 'hardware.purchases.view',
    color: 'teal',
  },
  {
    label: 'Clients',
    route: 'hardware.customers.index',
    icon: Users,
    permission: 'hardware.customer.view',
    color: 'purple',
  },
  {
    label: 'Fournisseurs',
    route: 'hardware.suppliers.index',
    icon: Truck,
    permission: 'hardware.supplier.view',
    color: 'orange',
  },
  {
    label: 'Catégories',
    route: 'hardware.categories.index',
    icon: Tag,
    permission: 'hardware.category.view',
    color: 'pink',
  },
];

const COLOR_MAP = {
  emerald: 'bg-emerald-500 hover:bg-emerald-600',
  blue: 'bg-blue-500 hover:bg-blue-600',
  amber: 'bg-amber-500 hover:bg-amber-600',
  slate: 'bg-slate-500 hover:bg-slate-600',
  violet: 'bg-violet-500 hover:bg-violet-600',
  indigo: 'bg-indigo-500 hover:bg-indigo-600',
  red: 'bg-red-500 hover:bg-red-600',
  cyan: 'bg-cyan-500 hover:bg-cyan-600',
  orange: 'bg-orange-500 hover:bg-orange-600',
  teal: 'bg-teal-500 hover:bg-teal-600',
  purple: 'bg-purple-500 hover:bg-purple-600',
  pink: 'bg-pink-500 hover:bg-pink-600',
};

const PERIOD_OPTIONS = [
  { value: 7, label: '7 derniers jours' },
  { value: 14, label: '14 derniers jours' },
  { value: 30, label: '30 derniers jours' },
];

function toYMD(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toISOString().slice(0, 10);
}

export default function Dashboard({
  stats,
  chartSalesLastDays = [],
  chartStockDistribution = [],
  filters = {},
}) {
  const { auth, shop } = usePage().props;
  const permissions = auth?.permissions ?? [];
  const currency = shop?.currency || 'CDF';
  const fmt = (amount) => formatCurrency(amount, currency);
  const currentPeriod = Number(filters?.period) || 14;
  const useDateRange = Boolean(filters?.from && filters?.to);
  const defaultFrom = toYMD(new Date(Date.now() - 14 * 24 * 60 * 60 * 1000));
  const defaultTo = toYMD(new Date());
  const [dateFrom, setDateFrom] = React.useState(filters?.from || defaultFrom);
  const [dateTo, setDateTo] = React.useState(filters?.to || defaultTo);
  const chartTitle = useDateRange
    ? `Ventes du ${filters.from} au ${filters.to}`
    : `Ventes des ${currentPeriod} derniers jours`;
  React.useEffect(() => {
    if (filters?.from) setDateFrom(filters.from);
    if (filters?.to) setDateTo(filters.to);
  }, [filters?.from, filters?.to]);

  const visibleActions = QUICK_ACTIONS.filter((a) =>
    hasPermission(permissions, a.permission)
  );

  const handlePeriodChange = (e) => {
    const value = Number(e.target.value) || 14;
    router.get(route('hardware.dashboard'), { period: value, from: '', to: '' }, { preserveState: true });
  };

  const handleDateRangeApply = () => {
    router.get(route('hardware.dashboard'), {
      period: currentPeriod,
      from: dateFrom || undefined,
      to: dateTo || undefined,
    }, { preserveState: true });
  };

  const salesChartData = (chartSalesLastDays || []).map((d) => ({
    ...d,
    dateShort: d.date ? new Date(d.date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '',
    total: Number(d.total),
    count: Number(d.count),
  }));

  const mediaStorage = stats?.media_storage || { images_count: 0, used_mb: null, limit_mb: 100 };
  const imagesCount = Number(mediaStorage.images_count ?? 0);

  return (
    <AppLayout
      header={
        <div>
          <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Tableau de bord — Quincaillerie
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Gérez vos performances et stocks en temps réel.
          </p>
        </div>
      }
    >
      <Head title="Tableau de bord Quincaillerie" />

      <div className="py-6 space-y-6 sm:space-y-8">
        {/* Filtres compacts avec icônes uniquement */}
        <div className="flex items-center gap-2 flex-wrap">
          {/* Période rapide - Icône seulement */}
          <div className="relative">
            <select
              value={currentPeriod}
              onChange={handlePeriodChange}
              className="appearance-none rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent cursor-pointer"
              title="Période rapide"
            >
              {PERIOD_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
            <Clock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 dark:text-gray-400 pointer-events-none" />
          </div>

          {/* Date début - Icône seulement */}
          <div className="relative">
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="appearance-none rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent w-40"
              title="Date début"
            />
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 dark:text-gray-400 pointer-events-none" />
          </div>

          {/* Date fin - Icône seulement */}
          <div className="relative">
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="appearance-none rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent w-40"
              title="Date fin"
            />
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 dark:text-gray-400 pointer-events-none" />
          </div>

          {/* Bouton appliquer - Icône seulement */}
          <Button
            type="button"
            onClick={handleDateRangeApply}
            className="bg-amber-600 hover:bg-amber-700 text-white p-2 rounded-lg"
            title="Appliquer les filtres"
          >
            <Filter className="h-4 w-4" />
          </Button>
        </div>

        {/* Stats Cards - Design Visily */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {/* VENTES DU JOUR */}
          <Card className="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-emerald-100 text-sm font-medium mb-2">VENTES DU JOUR</p>
                  <p className="text-white text-3xl font-bold mb-2">
                    {fmt(salesChartData.length > 0 ? (salesChartData[salesChartData.length - 1]?.total || 0) : 0)}
                  </p>
                  <div className="flex items-center gap-1 text-emerald-100 text-xs mb-2">
                    <ArrowUp className="h-3 w-3" />
                    <span>+12.5% vs hier</span>
                  </div>
                  <p className="text-emerald-100 text-xs">
                    {salesChartData.length > 0 ? (salesChartData[salesChartData.length - 1]?.count || 0) : 0} vente(s) validées
                  </p>
                </div>
                <div className="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <ShoppingCart className="h-7 w-7 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* VALEUR DU STOCK */}
          <Card className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-blue-100 text-sm font-medium mb-2">VALEUR DU STOCK</p>
                  <p className="text-white text-3xl font-bold mb-2">{fmt(stats?.inventory?.total_value ?? 0)}</p>
                  <div className="flex items-center gap-1 text-blue-100 text-xs mb-2">
                    <ArrowDown className="h-3 w-3" />
                    <span>-2.1% Valeur totale</span>
                  </div>
                  <p className="text-blue-100 text-xs">Stock actuel</p>
                </div>
                <div className="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Database className="h-7 w-7 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* PRODUITS EN STOCK */}
          <Card className="bg-gradient-to-br from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-amber-100 text-sm font-medium mb-2">PRODUITS EN STOCK</p>
                  <p className="text-white text-3xl font-bold mb-2">{stats?.products?.total ?? 0}</p>
                  <p className="text-amber-100 text-xs mt-2">{stats?.products?.active ?? 0} actifs</p>
                </div>
                <div className="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Package className="h-7 w-7 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* RUPTURES IMMINENTES */}
          <Card className="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-red-100 text-sm font-medium mb-2">RUPTURES IMMINENTES</p>
                  <p className="text-white text-3xl font-bold mb-2">{stats?.inventory?.out_of_stock_count ?? 0}</p>
                  <p className="text-red-100 text-xs mt-2">
                    {stats?.inventory?.low_stock_count ?? 0} critiques Articles à commander
                  </p>
                </div>
                <div className="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <AlertTriangle className="h-7 w-7 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* STOCKAGE MÉDIAS */}
          <Card className="bg-gradient-to-br from-slate-600 to-slate-700 dark:from-slate-700 dark:to-slate-800 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-slate-100 text-sm font-medium mb-2">STOCKAGE MÉDIAS</p>
                  <p className="text-white text-3xl font-bold mb-1">
                    {imagesCount}
                  </p>
                  <p className="text-slate-100 text-xs">
                    Images produits quincaillerie
                  </p>
                </div>
                <div className="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Database className="h-7 w-7 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Graphiques */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                    {chartTitle}
                  </CardTitle>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Évolution du chiffre d'affaires quotidien
                  </p>
                </div>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="text-xs"
                  onClick={() => {
                    // Fonction pour formater les nombres avec séparateurs
                    const formatNumber = (num) => {
                      return typeof num === 'number' 
                        ? num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        : String(num);
                    };
                    
                    // Fonction pour formater les dates
                    const formatDate = (dateStr) => {
                      try {
                        const date = new Date(dateStr);
                        return date.toLocaleDateString('fr-FR', { 
                          year: 'numeric', 
                          month: 'long', 
                          day: 'numeric' 
                        });
                      } catch {
                        return dateStr;
                      }
                    };
                    
                    const now = new Date();
                    const exportDate = now.toLocaleDateString('fr-FR', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    });
                    
                    // Calculer les totaux
                    const totalRevenue = salesChartData.reduce((sum, d) => sum + (d.total || 0), 0);
                    const totalSales = salesChartData.reduce((sum, d) => sum + (d.count || 0), 0);
                    const avgRevenue = salesChartData.length > 0 ? totalRevenue / salesChartData.length : 0;
                    
                    // Générer le CSV formaté
                    const csvLines = [
                      // En-tête principal
                      ['RAPPORT DE VENTES - MODULE HARDWARE'],
                      [''],
                      ['Date d\'export', exportDate],
                      ['Période', chartTitle],
                      ['Devise', currency],
                      [''],
                      // Séparateur visuel
                      ['═══════════════════════════════════════════════════════════════'],
                      [''],
                      // En-tête du tableau
                      ['Date', 'Chiffre d\'affaires (' + currency + ')', 'Nombre de ventes'],
                      ['───────────────────────────────────────────────────────────────'],
                      // Données
                      ...salesChartData.map(d => [
                        formatDate(d.date),
                        formatNumber(d.total || 0),
                        String(d.count || 0)
                      ]),
                      ['───────────────────────────────────────────────────────────────'],
                      // Totaux
                      ['TOTAL', formatNumber(totalRevenue), String(totalSales)],
                      ['MOYENNE PAR JOUR', formatNumber(avgRevenue), ''],
                      [''],
                      ['═══════════════════════════════════════════════════════════════'],
                      [''],
                      ['Résumé'],
                      ['Total des ventes', String(totalSales) + ' ventes'],
                      ['Chiffre d\'affaires total', formatNumber(totalRevenue) + ' ' + currency],
                      ['Chiffre d\'affaires moyen/jour', formatNumber(avgRevenue) + ' ' + currency],
                      ['Période analysée', salesChartData.length + ' jour(s)'],
                    ];
                    
                    // Convertir en CSV (échapper les virgules dans les chaînes)
                    const escapeCSV = (value) => {
                      const str = String(value);
                      if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                        return '"' + str.replace(/"/g, '""') + '"';
                      }
                      return str;
                    };
                    
                    const csv = csvLines.map(row => row.map(escapeCSV).join(',')).join('\n');
                    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' }); // BOM pour Excel
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const fileName = `rapport-ventes-hardware-${now.toISOString().slice(0, 10)}.csv`;
                    a.download = fileName;
                    a.click();
                    window.URL.revokeObjectURL(url);
                  }}
                >
                  <FileText className="h-3 w-3 mr-1" />
                  Exporter (.csv)
                </Button>
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
                      yAxisId="left"
                      tickFormatter={(v) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : v)}
                      tick={{ fontSize: 11 }}
                    />
                    <Tooltip
                      formatter={(value) => {
                        const v = Array.isArray(value) ? value[0] : value;
                        return [fmt(Number(v)), 'Chiffre d\'affaires'];
                      }}
                      labelFormatter={(_, payload) => payload[0]?.payload?.date && new Date(payload[0].payload.date).toLocaleDateString('fr-FR')}
                      contentStyle={{ borderRadius: 8 }}
                    />
                    <Line
                      yAxisId="left"
                      type="monotone"
                      dataKey="total"
                      stroke="#10b981"
                      strokeWidth={2}
                      dot={{ fill: '#10b981', r: 3 }}
                      name="CA"
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

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4">
            <CardHeader className="pb-2">
              <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                <BarChart3 className="h-5 w-5 text-violet-500" />
                Répartition du stock
              </CardTitle>
            </CardHeader>
            <CardContent className="h-72">
              {chartStockDistribution && chartStockDistribution.length > 0 ? (
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={chartStockDistribution}
                      dataKey="value"
                      nameKey="name"
                      cx="50%"
                      cy="50%"
                      outerRadius={80}
                      label={({ name, value }) => `${name}: ${value}`}
                    >
                      {chartStockDistribution.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.fill} />
                      ))}
                    </Pie>
                    <Tooltip
                      formatter={(value) => {
                        const v = Array.isArray(value) ? value[0] : value;
                        return [v, 'Produits'];
                      }}
                    />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                  Aucune donnée de stock
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Statistiques supplémentaires */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Catégories
              </CardTitle>
              <Tag className="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats?.categories?.total ?? 0}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {stats?.categories?.active ?? 0} actives
              </p>
            </CardContent>
          </Card>

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Fournisseurs
              </CardTitle>
              <Truck className="h-4 w-4 text-indigo-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats?.suppliers?.total ?? 0}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {stats?.suppliers?.active ?? 0} actifs
              </p>
            </CardContent>
          </Card>

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Clients
              </CardTitle>
              <Users className="h-4 w-4 text-purple-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats?.customers?.total ?? 0}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {stats?.customers?.active ?? 0} actifs
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Alerts */}
        {stats?.alerts?.length > 0 && (
          <div>
            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
              Alertes
            </h3>
            <div className="space-y-3">
              {stats.alerts.map((alert, index) => (
                <div
                  key={index}
                  className={`p-4 rounded-lg border ${
                    alert.type === 'danger'
                      ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                      : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
                  }`}
                >
                  <div className="flex items-center">
                    <AlertTriangle
                      className={`h-5 w-5 mr-3 ${
                        alert.type === 'danger'
                          ? 'text-red-600 dark:text-red-400'
                          : 'text-yellow-600 dark:text-yellow-400'
                      }`}
                    />
                    <div>
                      <p
                        className={`font-medium ${
                          alert.type === 'danger'
                            ? 'text-red-800 dark:text-red-300'
                            : 'text-yellow-800 dark:text-yellow-300'
                        }`}
                      >
                        {alert.message}
                      </p>
                      <p
                        className={`text-sm ${
                          alert.type === 'danger'
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-yellow-600 dark:text-yellow-400'
                        }`}
                      >
                        Priorité : {alert.priority}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Actions rapides - Design Visily */}
        {visibleActions.length > 0 && (
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="pb-3">
              <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                ACTIONS RAPIDES
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {visibleActions.slice(0, 4).map((action) => {
                  const Icon = action.icon;
                  return (
                    <Link
                      key={action.route}
                      href={route(action.route)}
                      className="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                    >
                      <div className={`w-12 h-12 rounded-lg ${COLOR_MAP[action.color] || 'bg-gray-500'} flex items-center justify-center mb-2`}>
                        <Icon className="h-6 w-6 text-white" />
                      </div>
                      <span className="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                        {action.label}
                      </span>
                    </Link>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </AppLayout>
  );
}
