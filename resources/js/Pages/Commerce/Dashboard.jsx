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
  ArrowUp,
  ArrowDown,
  Database,
  Activity,
  Clock,
  Calendar,
  Filter,
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
    route: 'commerce.sales.create',
    icon: Plus,
    permission: 'commerce.sales.manage',
    color: 'emerald',
  },
  {
    label: 'Voir les ventes',
    route: 'commerce.sales.index',
    icon: ShoppingCart,
    permission: 'commerce.sales.view',
    color: 'blue',
  },
  {
    label: 'Ajouter un produit',
    route: 'commerce.products.create',
    icon: Package,
    permission: 'commerce.product.manage',
    color: 'amber',
  },
  {
    label: 'Voir les produits',
    route: 'commerce.products.index',
    icon: Package,
    permission: 'commerce.product.view',
    color: 'slate',
  },
  {
    label: 'Stock',
    route: 'commerce.stock.index',
    icon: Layers,
    permission: 'commerce.stock.view',
    color: 'violet',
  },
  {
    label: 'Rapports',
    route: 'commerce.reports.index',
    icon: FileText,
    permission: 'commerce.report.view',
    color: 'cyan',
  },
  {
    label: 'Bons de commande',
    route: 'commerce.purchases.index',
    icon: Truck,
    permission: 'commerce.purchases.view',
    color: 'teal',
  },
  {
    label: 'Clients',
    route: 'commerce.customers.index',
    icon: Users,
    permission: 'commerce.customer.view',
    color: 'purple',
  },
  {
    label: 'Fournisseurs',
    route: 'commerce.suppliers.index',
    icon: Truck,
    permission: 'commerce.supplier.view',
    color: 'orange',
  },
  {
    label: 'Catégories',
    route: 'commerce.categories.index',
    icon: Tag,
    permission: 'commerce.category.view',
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
    router.get(route('commerce.dashboard'), { period: value, from: '', to: '' }, { preserveState: true });
  };

  const handleDateRangeApply = () => {
    router.get(route('commerce.dashboard'), {
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

  // Calculs pour mobile (basés sur les données existantes)
  const salesTodayTotal = salesChartData.length > 0 
    ? salesChartData[salesChartData.length - 1]?.total || 0 
    : 0;
  const salesTodayCount = salesChartData.length > 0 
    ? salesChartData[salesChartData.length - 1]?.count || 0 
    : 0;
  const salesLast7Total = salesChartData.slice(-7).reduce((sum, d) => sum + (d.total || 0), 0);
  const salesLast7Count = salesChartData.slice(-7).reduce((sum, d) => sum + (d.count || 0), 0);
  
  // Calculs pour métriques supplémentaires
  const averageBasket = salesTodayCount > 0 ? salesTodayTotal / salesTodayCount : 0;
  const conversionRate = 24.8; // Mock data - devrait venir du backend

  // Données d'activité récente (mock - devrait venir du backend)
  const recentActivities = [
    {
      id: 1,
      type: 'sale',
      name: 'Samuel Bwanga',
      reference: '#7281',
      time: '14:20',
      amount: 81500,
      currency: currency,
      icon: TrendingUp,
      color: 'text-green-500',
      bgColor: 'bg-green-100 dark:bg-green-900/20',
    },
    {
      id: 2,
      type: 'purchase',
      name: 'Fournisseur Alim',
      reference: '#AC-99',
      time: '12:15',
      amount: -45000,
      currency: currency,
      icon: ShoppingCart,
      color: 'text-purple-500',
      bgColor: 'bg-purple-100 dark:bg-purple-900/20',
    },
    {
      id: 3,
      type: 'sale',
      name: 'Client Comptoir',
      reference: '#7280',
      time: '10:45',
      amount: 2500,
      currency: currency,
      icon: TrendingUp,
      color: 'text-green-500',
      bgColor: 'bg-green-100 dark:bg-green-900/20',
    },
  ];

  return (
    <AppLayout
      header={
        <>
          {/* Mobile Header */}
          <div className="md:hidden">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
              Dashboard Commerce
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Vue rapide des ventes et achats Global Commerce.
            </p>
          </div>
          {/* Desktop Header */}
          <div className="hidden md:block">
            <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
              Tableau de bord — Commerce
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Gérez vos performances et stocks en temps réel.
            </p>
          </div>
        </>
      }
    >
      <Head title="Tableau de bord Commerce" />

      <div className="py-4 md:py-6 space-y-4 md:space-y-6 lg:space-y-8">
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

        {/* Mobile KPI Cards - Layout vertical avec design Visily */}
        <div className="md:hidden space-y-4">
          {/* Ventes du jour */}
          <Card className="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 border-0 shadow-lg">
            <CardContent className="p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-purple-100 text-sm font-medium mb-1">VENTES DU JOUR</p>
                  <p className="text-white text-2xl font-bold mb-2">{fmt(salesTodayTotal)}</p>
                  <div className="flex items-center gap-1 text-purple-100 text-xs">
                    <ArrowUp className="h-3 w-3" />
                    <span>12% vs hier</span>
                  </div>
                  <p className="text-purple-100 text-xs mt-2">{salesTodayCount} vente(s) validées</p>
                </div>
                <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                  <TrendingUp className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Ventes 7 jours */}
          <Card className="bg-gradient-to-br from-pink-500 to-pink-600 dark:from-pink-600 dark:to-pink-700 border-0 shadow-lg">
            <CardContent className="p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-pink-100 text-sm font-medium mb-1">VENTES 7 JOURS</p>
                  <p className="text-white text-2xl font-bold mb-2">{fmt(salesLast7Total)}</p>
                  <p className="text-pink-100 text-xs mt-2">{salesLast7Count} transactions complétées</p>
                </div>
                <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                  <BarChart3 className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Achats du jour */}
          <Card className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-lg">
            <CardContent className="p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-blue-100 text-sm font-medium mb-1">ACHATS DU JOUR</p>
                  <p className="text-white text-2xl font-bold mb-2">{fmt(0)}</p>
                  <p className="text-blue-100 text-xs mt-2">3 bons de commande</p>
                </div>
                <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                  <ShoppingCart className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Valeur stock */}
          <Card className="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 border-0 shadow-lg">
            <CardContent className="p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-green-100 text-sm font-medium mb-1">VALEUR STOCK</p>
                  <p className="text-white text-2xl font-bold mb-2">{fmt(stats?.inventory?.total_value ?? 0)}</p>
                  <p className="text-green-100 text-xs mt-2">{stats?.products?.active ?? 0} produits actifs</p>
                </div>
                <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                  <Database className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Desktop Stats Cards - Design Visily */}
        <div className="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {/* VENTES DU JOUR */}
          <Card className="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-lg">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-emerald-100 text-sm font-medium mb-2">VENTES DU JOUR</p>
                  <p className="text-white text-3xl font-bold mb-2">{fmt(salesTodayTotal)}</p>
                  <div className="flex items-center gap-1 text-emerald-100 text-xs mb-2">
                    <ArrowUp className="h-3 w-3" />
                    <span>+12.5% vs hier</span>
                  </div>
                  <p className="text-emerald-100 text-xs">{salesTodayCount} vente(s) validées</p>
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
        </div>

        {/* Mobile Chart - Ventes/Achats combiné */}
        <div className="md:hidden">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                    Ventes / Achats - 7 derniers jours
                  </CardTitle>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Tendance hebdomadaire du chiffre d'affaires
                  </p>
                </div>
              </div>
            </CardHeader>
            <CardContent className="h-64">
              {salesChartData.length > 0 ? (
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={salesChartData.slice(-7)} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-600" />
                    <XAxis
                      dataKey="dateShort"
                      tick={{ fontSize: 10 }}
                      className="text-gray-600 dark:text-gray-400"
                    />
                    <YAxis
                      yAxisId="left"
                      tickFormatter={(v) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : v)}
                      tick={{ fontSize: 10 }}
                    />
                    <Tooltip
                      formatter={(value) => {
                        const v = Array.isArray(value) ? value[0] : value;
                        return [fmt(Number(v)), 'Ventes'];
                      }}
                      contentStyle={{ borderRadius: 8, fontSize: '12px' }}
                    />
                    <Line
                      yAxisId="left"
                      type="monotone"
                      dataKey="total"
                      stroke="#3b82f6"
                      strokeWidth={2}
                      dot={{ fill: '#3b82f6', r: 3 }}
                      name="Sales (USD)"
                    />
                    <Legend 
                      wrapperStyle={{ fontSize: '12px', paddingTop: '10px' }}
                      iconType="line"
                    />
                  </LineChart>
                </ResponsiveContainer>
              ) : (
                <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm">
                  Aucune donnée disponible
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Desktop Graphiques */}
        <div className="hidden md:grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                      ['RAPPORT DE VENTES - MODULE COMMERCE'],
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
                    const fileName = `rapport-ventes-commerce-${now.toISOString().slice(0, 10)}.csv`;
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

        {/* Mobile - Activité récente */}
        <div className="md:hidden">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                  ACTIVITÉ RÉCENTE
                </CardTitle>
                <Link href={route('commerce.sales.index')} className="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                  Voir tout
                </Link>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {recentActivities.map((activity) => {
                const Icon = activity.icon;
                const isPositive = activity.amount > 0;
                return (
                  <div key={activity.id} className="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-slate-800/50">
                    <div className={`w-10 h-10 rounded-lg ${activity.bgColor} flex items-center justify-center flex-shrink-0`}>
                      <Icon className={`h-5 w-5 ${activity.color}`} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {activity.name}
                        </p>
                        <p className={`text-sm font-semibold ${isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                          {isPositive ? '+' : ''}{fmt(Math.abs(activity.amount))}
                        </p>
                      </div>
                      <div className="flex items-center gap-2 mt-1">
                        <span className="text-xs text-gray-500 dark:text-gray-400">
                          {activity.type === 'sale' ? 'VENTE' : 'ACHAT'} {activity.reference}
                        </span>
                        <span className="text-xs text-gray-400 dark:text-gray-500">•</span>
                        <span className="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                          <Clock className="h-3 w-3" />
                          {activity.time}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </div>

        {/* Mobile - Métriques supplémentaires */}
        <div className="md:hidden grid grid-cols-2 gap-4">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardContent className="p-4">
              <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">TAUX CONVERSION</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{conversionRate}%</p>
              <div className="flex items-center gap-1 mt-2">
                <ArrowUp className="h-3 w-3 text-green-500" />
                <span className="text-xs text-green-600 dark:text-green-400">+2.4% vs hier</span>
              </div>
            </CardContent>
          </Card>
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardContent className="p-4">
              <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">PANIER MOYEN</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{fmt(averageBasket)}</p>
              <div className="mt-2">
                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                  Stable
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Desktop Actions rapides - Design Visily */}
        {visibleActions.length > 0 && (
          <Card className="hidden md:block bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
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

        {/* Desktop Activités récentes */}
        <div className="hidden md:block">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base font-semibold text-gray-900 dark:text-white">
                  Activités Récentes
                </CardTitle>
                <Link href={route('commerce.sales.index')} className="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                  Voir tout l'historique
                </Link>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {recentActivities.map((activity) => {
                const Icon = activity.icon;
                const isPositive = activity.amount > 0;
                const timeAgo = activity.time === 'HIER' ? 'Hier' : activity.time;
                return (
                  <div key={activity.id} className="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-slate-800/50 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                    <div className={`w-10 h-10 rounded-lg ${activity.bgColor} flex items-center justify-center flex-shrink-0`}>
                      <Icon className={`h-5 w-5 ${activity.color}`} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                          {activity.type === 'sale' ? 'Completed sale' : 'Purchase'} {activity.reference}
                        </p>
                        <p className={`text-sm font-semibold ${isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                          {isPositive ? '+' : ''}{fmt(Math.abs(activity.amount))}
                        </p>
                      </div>
                      {activity.name && (
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                          {activity.name} • {timeAgo}
                        </p>
                      )}
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </div>

        {/* Mobile FAB */}
        {hasPermission(permissions, 'commerce.sales.manage') && (
          <Link
            href={route('commerce.sales.create')}
            className="md:hidden fixed bottom-20 right-4 z-50 w-14 h-14 bg-purple-600 hover:bg-purple-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all"
          >
            <Plus className="h-6 w-6" />
          </Link>
        )}
      </div>
    </AppLayout>
  );
}
