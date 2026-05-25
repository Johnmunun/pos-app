import { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    Gift,
    ArrowLeft,
    Trophy,
    TrendingUp,
    TrendingDown,
    Users,
    ShoppingCart,
    Percent,
    Activity,
    SlidersHorizontal,
    BarChart3,
} from 'lucide-react';
import { cardShell, pageY } from '@/lib/layoutClasses';

const MODULE_LABELS = {
    commerce: 'Commerce',
    pharmacy: 'Pharmacie',
    hardware: 'Quincaillerie',
};

const TIER_LABELS = {
    bronze: 'Bronze',
    silver: 'Silver',
    gold: 'Gold',
    vip: 'VIP',
};

function getDefaultFromTo() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return {
        from: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`,
        to: d.toISOString().slice(0, 10),
    };
}

export default function LoyaltyReportsIndex({ report, filters }) {
    const defaultBounds = getDefaultFromTo();
    const period = report?.period || {};
    const summary = report?.summary || {};

    const [dateFrom, setDateFrom] = useState(period.from || filters?.from || defaultBounds.from);
    const [dateTo, setDateTo] = useState(period.to || filters?.to || defaultBounds.to);
    const [moduleFilter, setModuleFilter] = useState(filters?.module || 'all');

    const handleApply = (e) => {
        e.preventDefault();
        router.get(
            route('loyalty.reports.index'),
            { from: dateFrom, to: dateTo, module: moduleFilter },
            { preserveState: true },
        );
    };

    const periodLabel = useMemo(() => {
        if (!period?.from || !period?.to) return 'Période';
        if (period.from === period.to) return period.from;
        return `${period.from} → ${period.to}`;
    }, [period]);

    const fmtMoney = (n) =>
        Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <BarChart3 className="h-6 w-6 text-indigo-600" />
                            Rapports fidélité
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5">
                            Top clients, points distribués, utilisations et impact sur les ventes.
                        </p>
                    </div>
                    <Link
                        href={route('loyalty.settings.index')}
                        className="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >
                        <Gift className="h-4 w-4" />
                        Paramètres fidélité
                    </Link>
                </div>
            }
        >
            <Head title="Rapports fidélité" />

            <div className={pageY}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {!report?.loyalty_enabled && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                            Le programme fidélité est désactivé ou non inclus dans votre plan. Activez-le dans les
                            paramètres et vérifiez votre abonnement.
                        </div>
                    )}

                    <Card className={cardShell}>
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base flex items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4 text-indigo-500" />
                                Filtres — {periodLabel}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleApply} className="flex flex-col sm:flex-row flex-wrap gap-4 items-end">
                                <div className="space-y-2">
                                    <Label htmlFor="from">Date début</Label>
                                    <Input id="from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="to">Date fin</Label>
                                    <Input id="to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                                </div>
                                <div className="space-y-2 min-w-[160px]">
                                    <Label htmlFor="module">Module</Label>
                                    <select
                                        id="module"
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={moduleFilter}
                                        onChange={(e) => setModuleFilter(e.target.value)}
                                    >
                                        <option value="all">Tous</option>
                                        <option value="commerce">Commerce</option>
                                        <option value="pharmacy">Pharmacie</option>
                                        <option value="hardware">Quincaillerie</option>
                                    </select>
                                </div>
                                <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                    Appliquer
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <Card>
                            <CardContent className="pt-5 pb-5">
                                <div className="flex items-center gap-2 text-slate-500 text-xs mb-1">
                                    <Users className="h-4 w-4" />
                                    Cartes actives
                                </div>
                                <p className="text-2xl font-bold tabular-nums">{summary.accounts ?? 0}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-5 pb-5">
                                <div className="flex items-center gap-2 text-emerald-600 text-xs mb-1">
                                    <TrendingUp className="h-4 w-4" />
                                    Points gagnés (période)
                                </div>
                                <p className="text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                    +{summary.points_earned ?? 0}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-5 pb-5">
                                <div className="flex items-center gap-2 text-rose-600 text-xs mb-1">
                                    <TrendingDown className="h-4 w-4" />
                                    Points utilisés (période)
                                </div>
                                <p className="text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-400">
                                    {summary.points_redeemed ?? 0}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-5 pb-5">
                                <div className="flex items-center gap-2 text-indigo-600 text-xs mb-1">
                                    <Percent className="h-4 w-4" />
                                    Réductions fidélité
                                </div>
                                <p className="text-2xl font-bold tabular-nums">{fmtMoney(summary.discount_total)}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <Card>
                            <CardContent className="py-4 flex items-center gap-3">
                                <ShoppingCart className="h-8 w-8 text-slate-400" />
                                <div>
                                    <p className="text-xs text-slate-500">Ventes avec fidélité</p>
                                    <p className="text-lg font-semibold">{summary.sales_with_loyalty ?? 0}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="py-4">
                                <p className="text-xs text-slate-500">CA éligible fidélité (période)</p>
                                <p className="text-lg font-semibold">{fmtMoney(summary.eligible_sales_total)}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="py-4">
                                <p className="text-xs text-slate-500">Points expirés / annulés</p>
                                <p className="text-lg font-semibold">
                                    {summary.points_expired ?? 0} / {summary.points_reversed ?? 0}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {(report?.by_module || []).length > 0 && (
                        <Card className={cardShell}>
                            <CardHeader>
                                <CardTitle className="text-base">Par module</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid sm:grid-cols-3 gap-3">
                                    {report.by_module.map((row) => (
                                        <div
                                            key={row.module}
                                            className="rounded-lg border border-slate-200 dark:border-slate-700 p-3"
                                        >
                                            <p className="font-medium">{MODULE_LABELS[row.module] || row.module}</p>
                                            <p className="text-sm text-slate-500 mt-1">
                                                {row.accounts} cartes · +{row.points_earned} pts (période)
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="grid lg:grid-cols-2 gap-6">
                        <Card className={cardShell}>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Trophy className="h-4 w-4 text-amber-500" />
                                    Top clients (solde actuel)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {(report?.top_by_balance || []).length === 0 ? (
                                    <p className="text-sm text-slate-500">Aucune carte fidélité.</p>
                                ) : (
                                    <ul className="divide-y divide-slate-200 dark:divide-slate-700">
                                        {report.top_by_balance.map((row, i) => (
                                            <li key={row.id} className="py-2.5 flex justify-between gap-2 text-sm">
                                                <span className="min-w-0">
                                                    <span className="text-slate-400 mr-2">#{i + 1}</span>
                                                    <span className="font-medium truncate block">
                                                        {row.customer_name || row.loyalty_number}
                                                    </span>
                                                    <span className="text-xs text-slate-500">
                                                        {MODULE_LABELS[row.module]} · {TIER_LABELS[row.tier] || row.tier}
                                                    </span>
                                                </span>
                                                <span className="font-bold tabular-nums text-indigo-600 shrink-0">
                                                    {row.points_balance} pts
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card className={cardShell}>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <TrendingUp className="h-4 w-4 text-emerald-500" />
                                    Meilleurs gagnants (période)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {(report?.top_earners_period || []).length === 0 ? (
                                    <p className="text-sm text-slate-500">Aucun gain sur la période.</p>
                                ) : (
                                    <ul className="divide-y divide-slate-200 dark:divide-slate-700">
                                        {report.top_earners_period.map((row, i) => (
                                            <li key={row.id} className="py-2.5 flex justify-between gap-2 text-sm">
                                                <span className="min-w-0">
                                                    <span className="text-slate-400 mr-2">#{i + 1}</span>
                                                    {row.customer_name || row.loyalty_number}
                                                </span>
                                                <span className="font-bold tabular-nums text-emerald-600 shrink-0">
                                                    +{row.points_earned_period} pts
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card className={cardShell}>
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2">
                                <Activity className="h-4 w-4" />
                                Activité récente
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="overflow-x-auto">
                            {(report?.recent_activity || []).length === 0 ? (
                                <p className="text-sm text-slate-500">Aucune transaction.</p>
                            ) : (
                                <table className="w-full text-sm min-w-[520px]">
                                    <thead>
                                        <tr className="border-b text-left text-slate-500">
                                            <th className="py-2 pr-2">Date</th>
                                            <th className="py-2 pr-2">Client</th>
                                            <th className="py-2 pr-2">Type</th>
                                            <th className="py-2 pr-2">Module</th>
                                            <th className="py-2 text-right">Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.recent_activity.map((tx) => (
                                            <tr key={tx.id} className="border-b border-slate-100 dark:border-slate-800">
                                                <td className="py-2 pr-2 whitespace-nowrap">{tx.created_at}</td>
                                                <td className="py-2 pr-2">{tx.customer_name || tx.loyalty_number || '—'}</td>
                                                <td className="py-2 pr-2 capitalize">
                                                    <Badge variant="outline" className="text-xs">
                                                        {tx.type}
                                                    </Badge>
                                                </td>
                                                <td className="py-2 pr-2">{MODULE_LABELS[tx.module] || tx.module}</td>
                                                <td
                                                    className={`py-2 text-right font-medium tabular-nums ${
                                                        tx.points >= 0 ? 'text-emerald-600' : 'text-rose-600'
                                                    }`}
                                                >
                                                    {tx.points >= 0 ? '+' : ''}
                                                    {tx.points}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex gap-2">
                        <Link
                            href={route('settings.index')}
                            className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Retour paramètres
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
