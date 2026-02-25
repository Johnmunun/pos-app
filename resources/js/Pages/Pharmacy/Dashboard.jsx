import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
  Package,
  AlertTriangle,
  Calendar,
  DollarSign,
  ShoppingCart,
  Pill,
  Plus,
  BarChart3,
  TrendingUp,
  Layers,
  FileText,
  Truck,
  Users,
  ClipboardList,
  Zap,
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
    route: 'pharmacy.sales.create',
    icon: Plus,
    permission: 'pharmacy.sales.view',
    color: 'emerald',
  },
  {
    label: 'Voir les ventes',
    route: 'pharmacy.sales.index',
    icon: ShoppingCart,
    permission: 'pharmacy.sales.view',
    color: 'blue',
  },
  {
    label: 'Ajouter un produit',
    route: 'pharmacy.products.create',
    icon: Pill,
    permission: ['pharmacy.product.manage', 'pharmacy.pharmacy.product.manage'],
    color: 'amber',
  },
  {
    label: 'Voir les produits',
    route: 'pharmacy.products',
    icon: Package,
    permission: ['pharmacy.product.manage', 'pharmacy.pharmacy.product.manage'],
    color: 'slate',
  },
  {
    label: 'Stock',
    route: 'pharmacy.stock.index',
    icon: Layers,
    permission: ['stock.view', 'pharmacy.pharmacy.stock.manage'],
    color: 'violet',
  },
  {
    label: 'Mouvements',
    route: 'pharmacy.stock.movements.index',
    icon: BarChart3,
    permission: ['stock.movement.view', 'pharmacy.pharmacy.stock.manage'],
    color: 'indigo',
  },
  {
    label: 'Expirations',
    route: 'pharmacy.expirations.index',
    icon: Calendar,
    permission: 'pharmacy.expiration.view',
    color: 'red',
  },
  {
    label: 'Rapports',
    route: 'pharmacy.reports.index',
    icon: FileText,
    permission: 'pharmacy.report.view',
    color: 'cyan',
  },
  {
    label: 'Inventaire',
    route: 'pharmacy.inventories.index',
    icon: ClipboardList,
    permission: 'inventory.view',
    color: 'orange',
  },
  {
    label: 'Achats',
    route: 'pharmacy.purchases.index',
    icon: Truck,
    permission: 'pharmacy.purchases.view',
    color: 'teal',
  },
  {
    label: 'Clients',
    route: 'pharmacy.customers.index',
    icon: Users,
    permission: 'pharmacy.customer.view',
    color: 'purple',
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
    router.get(route('pharmacy.dashboard'), { period: value, from: '', to: '' }, { preserveState: true });
  };

  const handleDateRangeApply = () => {
    router.get(route('pharmacy.dashboard'), {
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

  return (
    <AppLayout
      header={
        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
          Tableau de bord
        </h2>
      }
    >
      <Head title="Tableau de bord Pharmacie" />

      <div className="py-6">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
          {/* Filtres */}
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="pb-2">
              <CardTitle className="text-base text-gray-900 dark:text-white">
                Filtres du graphique ventes
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap items-center gap-4">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Période rapide :
                </span>
                <select
                  value={currentPeriod}
                  onChange={handlePeriodChange}
                  className="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                >
                  {PERIOD_OPTIONS.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 ml-2">
                  Date début
                </span>
                <input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  className="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                />
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Date fin
                </span>
                <input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  className="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                />
                <Button
                  type="button"
                  onClick={handleDateRangeApply}
                  className="bg-amber-600 hover:bg-amber-700 text-white"
                >
                  Appliquer
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Actions rapides (selon permissions) */}
          {visibleActions.length > 0 && (
            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                  <Zap className="h-5 w-5 text-amber-500" />
                  Actions rapides
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex flex-wrap gap-2">
                  {visibleActions.map((action) => {
                    const Icon = action.icon;
                    const btnClass = COLOR_MAP[action.color] || 'bg-gray-500 hover:bg-gray-600';
                    return (
                      <Button
                        key={action.route}
                        className={`${btnClass} text-white text-sm`}
                        asChild
                      >
                        <Link href={route(action.route)} className="inline-flex items-center gap-2">
                          <Icon className="h-4 w-4" />
                          {action.label}
                        </Link>
                      </Button>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          )}

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                  Produits
                </CardTitle>
                <Package className="h-4 w-4 text-gray-400 dark:text-gray-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                  {stats?.products?.total ?? 0}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {stats?.products?.active ?? 0} actifs
                </p>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                  Stock bas
                </CardTitle>
                <AlertTriangle className="h-4 w-4 text-orange-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                  {stats?.inventory?.low_stock_count ?? 0}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  À réapprovisionner
                </p>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                  Valeur du stock
                </CardTitle>
                <DollarSign className="h-4 w-4 text-green-500 dark:text-green-400" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                  {fmt(stats?.inventory?.total_value ?? 0)}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  Valeur totale
                </p>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                  Expire bientôt
                </CardTitle>
                <Calendar className="h-4 w-4 text-red-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                  {stats?.expiry?.expiring_soon_count ?? 0}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  Sous 30 jours
                </p>
              </CardContent>
            </Card>
          </div>

          {/* Graphiques */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-4">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2 text-gray-900 dark:text-white">
                  <TrendingUp className="h-5 w-5 text-emerald-500" />
                  {chartTitle}
                </CardTitle>
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
        </div>
      </div>
    </AppLayout>
  );
}
