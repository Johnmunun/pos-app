/**
 * Page: RootDashboard
 *
 * Dashboard ROOT global avec vue d'ensemble, statistiques, graphiques, alertes, etc.
 * Accessible uniquement par le ROOT user ou utilisateurs avec permission admin.dashboard.view
 */
import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
  Building2,
  Users,
  DollarSign,
  TrendingUp,
  AlertTriangle,
  Clock,
  Download,
  RefreshCw,
  BarChart3,
  PieChart as PieChartIcon,
  Activity,
  FileText,
  Calendar,
  CheckCircle,
  XCircle,
  Package,
} from 'lucide-react';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';

const COLORS = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

function formatNumber(num) {
  return new Intl.NumberFormat('fr-FR').format(num);
}

export default function RootDashboard() {
  const { kpis, module_stats, trends, alerts, recent_activity, top_tenants, period, from, to, auth } = usePage().props;
  const permissions = auth?.permissions ?? [];
  const canViewDashboard = auth?.user?.type === 'ROOT' || permissions.includes('admin.dashboard.view');
  const canExport = auth?.user?.type === 'ROOT' || permissions.includes('admin.dashboard.export');

  const [selectedPeriod, setSelectedPeriod] = useState(period || '30');
  const [dateFrom, setDateFrom] = useState(from || '');
  const [dateTo, setDateTo] = useState(to || '');

  if (!canViewDashboard) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Accès refusé</h1>
          <p className="text-gray-600">Vous n'avez pas la permission d'accéder à cette page.</p>
        </div>
      </div>
    );
  }

  const handlePeriodChange = (newPeriod) => {
    setSelectedPeriod(newPeriod);
    setDateFrom('');
    setDateTo('');
    router.get(route('admin.dashboard'), { period: newPeriod }, { preserveState: false });
  };

  const handleDateRangeChange = () => {
    if (dateFrom && dateTo) {
      router.get(route('admin.dashboard'), { from: dateFrom, to: dateTo }, { preserveState: false });
    }
  };

  const handleRefresh = () => {
    router.reload({ only: ['kpis', 'module_stats', 'trends', 'alerts', 'recent_activity', 'top_tenants'] });
  };

  // Préparer les données pour les graphiques
  const revenueChartData = trends?.map((t) => ({
    date: new Date(t.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }),
    revenue: t.revenue || 0,
    tenants: t.tenants || 0,
    users: t.users || 0,
  })) || [];

  const moduleRevenueData = [
    { name: 'Pharmacy', value: module_stats?.pharmacy?.revenue || 0 },
    { name: 'Commerce', value: module_stats?.commerce?.revenue || 0 },
    { name: 'Hardware', value: module_stats?.hardware?.revenue || 0 },
    { name: 'E-commerce', value: module_stats?.ecommerce?.revenue || 0 },
  ].filter((m) => m.value > 0);

  const moduleUsersData = [
    { name: 'Pharmacy', value: module_stats?.pharmacy?.users || 0 },
    { name: 'Commerce', value: module_stats?.commerce?.users || 0 },
    { name: 'Hardware', value: module_stats?.hardware?.users || 0 },
    { name: 'E-commerce', value: module_stats?.ecommerce?.users || 0 },
  ].filter((m) => m.value > 0);

  return (
    <AppLayout>
      <Head title="Dashboard ROOT" />

      <div className="min-h-screen bg-gray-50 dark:bg-slate-900">
        {/* Header */}
        <div className="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Dashboard ROOT</h1>
                <p className="text-gray-600 dark:text-gray-400 mt-1">Vue d'ensemble globale de la plateforme</p>
              </div>
              <div className="flex items-center gap-3">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleRefresh}
                  className="flex items-center gap-2"
                >
                  <RefreshCw className="h-4 w-4" />
                  Actualiser
                </Button>
                {canExport && (
                  <>
                    <Button
                      variant="outline"
                      size="sm"
                      asChild
                    >
                      <a 
                        href={route('admin.dashboard.export.pdf', { period: selectedPeriod, from: dateFrom, to: dateTo })}
                        className="flex items-center gap-2"
                      >
                        <FileText className="h-4 w-4" />
                        PDF
                      </a>
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      asChild
                    >
                      <a 
                        href={route('admin.dashboard.export.excel', { period: selectedPeriod, from: dateFrom, to: dateTo })}
                        className="flex items-center gap-2"
                      >
                        <Download className="h-4 w-4" />
                        Excel
                      </a>
                    </Button>
                  </>
                )}
              </div>
            </div>

            {/* Filtres */}
            <div className="mt-6 flex flex-wrap items-center gap-4">
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-gray-500" />
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Période:</span>
              </div>
              <div className="flex gap-2">
                {['7', '30', '90', 'all'].map((p) => (
                  <Button
                    key={p}
                    variant={selectedPeriod === p ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => handlePeriodChange(p)}
                  >
                    {p === 'all' ? 'Tout' : `${p}j`}
                  </Button>
                ))}
              </div>
              <div className="flex items-center gap-2">
                <input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  className="rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-1"
                />
                <span className="text-gray-500">à</span>
                <input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  className="rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-1"
                />
                <Button size="sm" onClick={handleDateRangeChange} disabled={!dateFrom || !dateTo}>
                  Appliquer
                </Button>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* 1. KPI Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <Building2 className="h-4 w-4" />
                  Total Tenants
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-gray-900 dark:text-white">
                  {formatNumber(kpis?.total_tenants || 0)}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {kpis?.active_tenants || 0} actifs, {kpis?.inactive_tenants || 0} inactifs
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <Users className="h-4 w-4" />
                  Total Utilisateurs
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-gray-900 dark:text-white">
                  {formatNumber(kpis?.total_users || 0)}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {kpis?.active_users || 0} actifs, {kpis?.inactive_users || 0} inactifs
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <DollarSign className="h-4 w-4" />
                  Chiffre d'affaires
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                  {formatCurrency(kpis?.total_revenue || 0)}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Tous modules confondus
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <TrendingUp className="h-4 w-4" />
                  Modules actifs
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-gray-900 dark:text-white">
                  {Object.values(module_stats || {}).filter((m) => (m?.tenants || 0) > 0).length}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Modules avec tenants
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <Package className="h-4 w-4" />
                  Total Produits
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-gray-900 dark:text-white">
                  {formatNumber(kpis?.total_products || 0)}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Toutes les boutiques
                </div>
              </CardContent>
            </Card>
          </div>

          {/* 2. Graphiques */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {/* Graphique CA */}
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <TrendingUp className="h-5 w-5 text-emerald-500" />
                  Évolution du chiffre d'affaires
                </CardTitle>
              </CardHeader>
              <CardContent>
                {revenueChartData.length > 0 ? (
                  <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={revenueChartData}>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                      <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                      <YAxis tick={{ fontSize: 12 }} tickFormatter={(v) => formatCurrency(v)} />
                      <Tooltip
                        formatter={(value) => formatCurrency(value)}
                        labelStyle={{ color: '#374151' }}
                      />
                      <Line
                        type="monotone"
                        dataKey="revenue"
                        stroke="#10b981"
                        strokeWidth={2}
                        dot={{ fill: '#10b981', r: 3 }}
                        name="CA"
                      />
                    </LineChart>
                  </ResponsiveContainer>
                ) : (
                  <div className="h-[300px] flex items-center justify-center text-gray-500">
                    Aucune donnée disponible
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Graphique répartition CA par module */}
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <PieChartIcon className="h-5 w-5 text-violet-500" />
                  Répartition CA par module
                </CardTitle>
              </CardHeader>
              <CardContent>
                {moduleRevenueData.length > 0 ? (
                  <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                      <Pie
                        data={moduleRevenueData}
                        dataKey="value"
                        nameKey="name"
                        cx="50%"
                        cy="50%"
                        outerRadius={80}
                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                      >
                        {moduleRevenueData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(value) => formatCurrency(value)} />
                      <Legend />
                    </PieChart>
                  </ResponsiveContainer>
                ) : (
                  <div className="h-[300px] flex items-center justify-center text-gray-500">
                    Aucune donnée disponible
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* 3. Statistiques par module */}
          <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 mb-8">
            <CardHeader>
              <CardTitle className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <BarChart3 className="h-5 w-5 text-blue-500" />
                Répartition par module
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                {Object.entries(module_stats || {}).map(([module, stats]) => (
                  <div key={module} className="border border-gray-200 dark:border-slate-700 rounded-lg p-4">
                    <h3 className="font-bold text-gray-900 dark:text-white capitalize mb-3">{module}</h3>
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">Tenants:</span>
                        <span className="font-semibold text-gray-900 dark:text-white">{stats?.tenants || 0}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">Utilisateurs:</span>
                        <span className="font-semibold text-gray-900 dark:text-white">{stats?.users || 0}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">CA:</span>
                        <span className="font-semibold text-emerald-600 dark:text-emerald-400">
                          {formatCurrency(stats?.revenue || 0)}
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* 4. Alertes système */}
          {alerts && alerts.length > 0 && (
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 mb-8">
              <CardHeader>
                <CardTitle className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <AlertTriangle className="h-5 w-5 text-amber-500" />
                  Alertes système
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {alerts.map((alert, index) => (
                    <div
                      key={index}
                      className={`p-4 rounded-lg border ${
                        alert.type === 'warning'
                          ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800'
                          : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                      }`}
                    >
                      <div className="flex items-center gap-2">
                        {alert.type === 'warning' ? (
                          <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        ) : (
                          <Activity className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        )}
                        <div>
                          <h4 className="font-semibold text-gray-900 dark:text-white">{alert.title}</h4>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{alert.message}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}

          {/* 5. Activité récente */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <Clock className="h-4 w-4 text-blue-500" />
                  Nouveaux tenants (7j)
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {recent_activity?.tenants && recent_activity.tenants.length > 0 ? (
                    recent_activity.tenants.map((tenant) => (
                      <div key={tenant.id} className="text-sm border-b border-gray-100 dark:border-slate-700 pb-2">
                        <div className="font-medium text-gray-900 dark:text-white">{tenant.name}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                          {new Date(tenant.created_at).toLocaleDateString('fr-FR')}
                        </div>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-gray-500">Aucun nouveau tenant</p>
                  )}
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <Users className="h-4 w-4 text-green-500" />
                  Nouveaux utilisateurs (7j)
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {recent_activity?.users && recent_activity.users.length > 0 ? (
                    recent_activity.users.map((user) => (
                      <div key={user.id} className="text-sm border-b border-gray-100 dark:border-slate-700 pb-2">
                        <div className="font-medium text-gray-900 dark:text-white">{user.name || user.email}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                          {new Date(user.created_at).toLocaleDateString('fr-FR')}
                        </div>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-gray-500">Aucun nouvel utilisateur</p>
                  )}
                </div>
              </CardContent>
            </Card>

            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <Activity className="h-4 w-4 text-purple-500" />
                  Connexions récentes (7j)
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {recent_activity?.logins && recent_activity.logins.length > 0 ? (
                    recent_activity.logins.map((user) => (
                      <div key={user.id} className="text-sm border-b border-gray-100 dark:border-slate-700 pb-2">
                        <div className="font-medium text-gray-900 dark:text-white">{user.name || user.email}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                          {user.last_login_at
                            ? new Date(user.last_login_at).toLocaleString('fr-FR')
                            : 'Jamais connecté'}
                        </div>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-gray-500">Aucune connexion récente</p>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>

          {/* 6. Top tenants */}
          {top_tenants && top_tenants.length > 0 && (
            <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
              <CardHeader>
                <CardTitle className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <TrendingUp className="h-5 w-5 text-emerald-500" />
                  Top 10 Tenants (par CA)
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-gray-200 dark:border-slate-700">
                        <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Rang</th>
                        <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Tenant</th>
                        <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Secteur</th>
                        <th className="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Chiffre d'affaires</th>
                      </tr>
                    </thead>
                    <tbody>
                      {top_tenants.map((tenant, index) => (
                        <tr key={tenant.id} className="border-b border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                          <td className="py-3 px-4 text-sm text-gray-900 dark:text-white font-medium">#{index + 1}</td>
                          <td className="py-3 px-4 text-sm text-gray-900 dark:text-white">{tenant.name}</td>
                          <td className="py-3 px-4 text-sm text-gray-600 dark:text-gray-400 capitalize">{tenant.sector || '—'}</td>
                          <td className="py-3 px-4 text-sm text-right font-semibold text-emerald-600 dark:text-emerald-400">
                            {formatCurrency(tenant.revenue)}
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
