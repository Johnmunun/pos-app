import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { useState } from 'react';
import {
    AlertTriangle,
    Ban,
    CheckCircle2,
    Flag,
    RefreshCcw,
    ShieldAlert,
    Store,
} from 'lucide-react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';

const STATUS_OPTIONS = [
    { value: 'pending', label: 'En attente' },
    { value: 'reviewed', label: 'Examinés' },
    { value: 'dismissed', label: 'Classés sans suite' },
    { value: 'all', label: 'Tous' },
];

function severityClass(severity) {
    if (severity === 'critical') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300';
    if (severity === 'warning') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
}

export default function ShopReportsIndex({
    reports = [],
    shops_summary = [],
    filters = { shop_id: null, status: 'pending' },
    reason_labels = {},
    thresholds = { warning: 5, critical: 10 },
    stats = { pending_total: 0, suspended_shops: 0 },
}) {
    const [actionLoading, setActionLoading] = useState(null);
    const [suspendModal, setSuspendModal] = useState(null);
    const [suspendReason, setSuspendReason] = useState('');

    const applyFilters = (next) => {
        router.get(
            route('admin.ecommerce.shop-reports.index'),
            {
                status: next.status ?? filters.status,
                shop_id: next.shop_id ?? filters.shop_id ?? undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const dismissReport = async (reportId) => {
        setActionLoading(`dismiss-${reportId}`);
        try {
            await axios.post(route('api.admin.ecommerce.shop-reports.dismiss', reportId));
            toast.success('Signalement classé sans suite.');
            router.reload({ only: ['reports', 'shops_summary', 'stats'] });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Action impossible.');
        } finally {
            setActionLoading(null);
        }
    };

    const suspendShop = async () => {
        if (!suspendModal?.shop_id || !suspendReason.trim()) {
            toast.error('Indiquez un motif de suspension.');
            return;
        }
        setActionLoading(`suspend-${suspendModal.shop_id}`);
        try {
            await axios.post(route('api.admin.ecommerce.shops.suspend-reports', suspendModal.shop_id), {
                reason: suspendReason.trim(),
            });
            toast.success('Boutique suspendue. La vitrine publique est inaccessible.');
            setSuspendModal(null);
            setSuspendReason('');
            router.reload();
        } catch (err) {
            toast.error(err.response?.data?.message || 'Suspension impossible.');
        } finally {
            setActionLoading(null);
        }
    };

    const restoreShop = async (shopId) => {
        if (!window.confirm('Réactiver cette boutique sur la vitrine publique ?')) return;
        setActionLoading(`restore-${shopId}`);
        try {
            await axios.post(route('api.admin.ecommerce.shops.restore-reports', shopId));
            toast.success('Boutique réactivée.');
            router.reload();
        } catch (err) {
            toast.error(err.response?.data?.message || 'Réactivation impossible.');
        } finally {
            setActionLoading(null);
        }
    };

    return (
        <AppLayout fullWidth>
            <Head title="Signalements boutiques" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 bg-gray-50 dark:bg-slate-900 min-h-screen">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Flag className="h-7 w-7 text-rose-600" />
                            Signalements boutiques
                        </h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Modération des vitrines e-commerce : signalements clients, seuils d&apos;alerte (
                            {thresholds.warning} / {thresholds.critical}) et suspension.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.reload()}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-700 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700"
                    >
                        <RefreshCcw className="h-4 w-4" />
                        Actualiser
                    </button>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                        <p className="text-xs uppercase tracking-wide text-slate-500">En attente</p>
                        <p className="mt-1 text-2xl font-bold text-amber-600">{stats.pending_total ?? 0}</p>
                    </div>
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                        <p className="text-xs uppercase tracking-wide text-slate-500">Boutiques suspendues</p>
                        <p className="mt-1 text-2xl font-bold text-rose-600">{stats.suspended_shops ?? 0}</p>
                    </div>
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 sm:col-span-2">
                        <p className="text-xs uppercase tracking-wide text-slate-500">Seuils automatiques</p>
                        <p className="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            <span className="inline-flex items-center gap-1 text-amber-700 dark:text-amber-300">
                                <AlertTriangle className="h-4 w-4" /> Alerte à {thresholds.warning}+ signalements en attente
                            </span>
                            {' · '}
                            <span className="inline-flex items-center gap-1 text-rose-700 dark:text-rose-300">
                                <ShieldAlert className="h-4 w-4" /> Critique à {thresholds.critical}+
                            </span>
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                    <div className="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <Store className="h-5 w-5 text-indigo-600" />
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white">
                            Boutiques signalées ({shops_summary.length})
                        </h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[800px] text-sm">
                            <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 text-left">Boutique</th>
                                    <th className="px-4 py-3 text-left">Tenant</th>
                                    <th className="px-4 py-3 text-left">Sous-domaine</th>
                                    <th className="px-4 py-3 text-left">En attente</th>
                                    <th className="px-4 py-3 text-left">Total</th>
                                    <th className="px-4 py-3 text-left">État</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                                {shops_summary.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                                            Aucune boutique signalée pour le moment.
                                        </td>
                                    </tr>
                                ) : (
                                    shops_summary.map((row) => (
                                        <tr key={row.shop_id} className="hover:bg-slate-50/80 dark:hover:bg-slate-900/30">
                                            <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                                {row.shop_name}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{row.tenant_name}</td>
                                            <td className="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                {row.ecommerce_subdomain || '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${severityClass(row.severity)}`}
                                                >
                                                    {row.reports_pending}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">{row.reports_total}</td>
                                            <td className="px-4 py-3">
                                                {row.is_suspended ? (
                                                    <span className="inline-flex items-center gap-1 text-xs font-medium text-rose-700 dark:text-rose-300">
                                                        <Ban className="h-3.5 w-3.5" /> Suspendue
                                                    </span>
                                                ) : row.ecommerce_is_online ? (
                                                    <span className="text-xs text-emerald-700 dark:text-emerald-300">En ligne</span>
                                                ) : (
                                                    <span className="text-xs text-slate-500">Hors ligne</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right space-x-2">
                                                <button
                                                    type="button"
                                                    onClick={() => applyFilters({ shop_id: row.shop_id, status: 'pending' })}
                                                    className="text-xs text-indigo-600 hover:underline"
                                                >
                                                    Voir signalements
                                                </button>
                                                {row.is_suspended ? (
                                                    <button
                                                        type="button"
                                                        disabled={actionLoading === `restore-${row.shop_id}`}
                                                        onClick={() => restoreShop(row.shop_id)}
                                                        className="text-xs text-emerald-600 hover:underline disabled:opacity-50"
                                                    >
                                                        Réactiver
                                                    </button>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        disabled={!!actionLoading}
                                                        onClick={() =>
                                                            setSuspendModal({
                                                                shop_id: row.shop_id,
                                                                shop_name: row.shop_name,
                                                            })
                                                        }
                                                        className="text-xs text-rose-600 hover:underline disabled:opacity-50"
                                                    >
                                                        Suspendre
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                    <div className="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white">
                            Détail des signalements ({reports.length})
                        </h2>
                        <div className="flex flex-wrap items-center gap-2">
                            <select
                                value={filters.status || 'pending'}
                                onChange={(e) => applyFilters({ status: e.target.value, shop_id: filters.shop_id })}
                                className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm"
                            >
                                {STATUS_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                            {filters.shop_id ? (
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ shop_id: null, status: filters.status })}
                                    className="text-xs text-slate-600 hover:underline"
                                >
                                    Toutes les boutiques
                                </button>
                            ) : null}
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[960px] text-sm">
                            <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 text-left">Date</th>
                                    <th className="px-4 py-3 text-left">Boutique</th>
                                    <th className="px-4 py-3 text-left">Motif</th>
                                    <th className="px-4 py-3 text-left">Détails</th>
                                    <th className="px-4 py-3 text-left">Statut</th>
                                    <th className="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                                {reports.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-slate-500">
                                            Aucun signalement pour ces filtres.
                                        </td>
                                    </tr>
                                ) : (
                                    reports.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-3 text-slate-600 whitespace-nowrap">{r.created_at}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-900 dark:text-white">{r.shop_name}</div>
                                                <div className="text-xs text-slate-500">{r.tenant_name}</div>
                                            </td>
                                            <td className="px-4 py-3">{r.reason_label || reason_labels[r.reason] || r.reason}</td>
                                            <td className="px-4 py-3 max-w-xs truncate text-slate-600" title={r.details || ''}>
                                                {r.details || '—'}
                                                {r.reporter_email ? (
                                                    <div className="text-xs text-slate-400 mt-0.5">{r.reporter_email}</div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.status === 'pending' ? (
                                                    <span className="text-xs text-amber-700">En attente</span>
                                                ) : r.status === 'dismissed' ? (
                                                    <span className="text-xs text-slate-500">Sans suite</span>
                                                ) : (
                                                    <span className="text-xs text-emerald-700">Examiné</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {r.status === 'pending' ? (
                                                    <button
                                                        type="button"
                                                        disabled={actionLoading === `dismiss-${r.id}`}
                                                        onClick={() => dismissReport(r.id)}
                                                        className="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900 disabled:opacity-50"
                                                    >
                                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                                        Classer sans suite
                                                    </button>
                                                ) : (
                                                    <span className="text-xs text-slate-400">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <Modal show={!!suspendModal} onClose={() => !actionLoading && setSuspendModal(null)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-950/50">
                            <Ban className="h-5 w-5 text-rose-600" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Suspendre la boutique
                            </h3>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {suspendModal?.shop_name} — la vitrine publique sera inaccessible (comme une page
                                désactivée pour abus).
                            </p>
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Motif interne (obligatoire)
                        </label>
                        <textarea
                            rows={4}
                            value={suspendReason}
                            onChange={(e) => setSuspendReason(e.target.value)}
                            className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                            placeholder="Ex. : 12 signalements arnaque, contenu trompeur confirmé…"
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => setSuspendModal(null)} disabled={!!actionLoading}>
                            Annuler
                        </Button>
                        <Button type="button" variant="destructive" onClick={suspendShop} disabled={!!actionLoading}>
                            {actionLoading ? 'Suspension…' : 'Suspendre la boutique'}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
