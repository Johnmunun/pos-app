import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function BillingExpiredSubscriptions({ rows = [], activeRows = [], plans = [] }) {
    const [durationByTenant, setDurationByTenant] = useState({});
    const [planByTenant, setPlanByTenant] = useState({});
    const [processingTenantId, setProcessingTenantId] = useState(null);
    const [tab, setTab] = useState('expired');

    const activePlans = useMemo(
        () => plans.filter((p) => p?.is_active !== false),
        [plans]
    );

    const submitReactivate = (row) => {
        const tenantId = row.tenant_id;
        const selectedPlanId = Number(planByTenant[tenantId] || row.billing_plan_id || activePlans[0]?.id || 0);
        const durationDays = Number(durationByTenant[tenantId] || 30);

        if (!selectedPlanId) return;

        setProcessingTenantId(tenantId);
        router.post(route('admin.billing.subscriptions.reactivate'), {
            tenant_id: tenantId,
            billing_plan_id: selectedPlanId,
            duration_days: durationDays,
        }, {
            preserveScroll: true,
            onFinish: () => setProcessingTenantId(null),
        });
    };

    return (
        <AppLayout fullWidth>
            <Head title="Abonnements expires" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 bg-gray-50 dark:bg-slate-900 min-h-screen">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                            Abonnements (Actifs / Expirés)
                        </h1>
                        <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">
                            Interface dediee pour reactivation manuelle par admin/root.
                        </p>
                    </div>
                    <a
                        href={route('admin.billing.plans.index')}
                        className="inline-flex items-center rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm text-slate-700 dark:text-slate-200"
                    >
                        Retour plans billing
                    </a>
                </div>

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => setTab('active')}
                        className={`px-3 py-2 rounded-lg text-sm font-semibold border ${
                            tab === 'active'
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'
                        }`}
                    >
                        Actifs ({activeRows.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('expired')}
                        className={`px-3 py-2 rounded-lg text-sm font-semibold border ${
                            tab === 'expired'
                                ? 'bg-amber-600 text-white border-amber-600'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'
                        }`}
                    >
                        Expirés ({rows.length})
                    </button>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 overflow-x-auto">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr className="text-left text-slate-500 dark:text-slate-300">
                                <th className="pb-2">Tenant</th>
                                <th className="pb-2">Utilisateur</th>
                                <th className="pb-2">Dernier plan</th>
                                <th className="pb-2">Expire le</th>
                                {tab === 'expired' ? (
                                    <>
                                        <th className="pb-2">Nouveau plan</th>
                                        <th className="pb-2">Duree (jours)</th>
                                        <th className="pb-2 text-right">Action</th>
                                    </>
                                ) : (
                                    <th className="pb-2 text-right">Statut</th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {tab === 'active' ? (
                                activeRows.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="py-4 text-center text-slate-500 dark:text-slate-300">
                                            Aucun abonnement actif.
                                        </td>
                                    </tr>
                                ) : activeRows.map((row) => (
                                    <tr key={`${row.subscription_id}-${row.tenant_id}`} className="border-t border-slate-200 dark:border-slate-700">
                                        <td className="py-2 text-slate-900 dark:text-slate-100">{row.tenant_name}</td>
                                        <td className="py-2">
                                            <p className="text-slate-900 dark:text-slate-100">{row.user_name}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">{row.user_email}</p>
                                        </td>
                                        <td className="py-2 text-slate-700 dark:text-slate-300">
                                            {row.plan_name || '-'} {row.subscription_status ? `(${row.subscription_status})` : ''}
                                        </td>
                                        <td className="py-2 text-slate-700 dark:text-slate-300">
                                            {row.expires_at ? new Date(row.expires_at).toLocaleDateString() : '-'}
                                        </td>
                                        <td className="py-2 text-right">
                                            <span className="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                Actif
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : rows.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="py-4 text-center text-slate-500 dark:text-slate-300">
                                        Aucun compte inactif lie a un abonnement expire.
                                    </td>
                                </tr>
                            ) : rows.map((row) => (
                                <tr key={`${row.user_id}-${row.tenant_id}`} className="border-t border-slate-200 dark:border-slate-700">
                                    <td className="py-2 text-slate-900 dark:text-slate-100">{row.tenant_name}</td>
                                    <td className="py-2">
                                        <p className="text-slate-900 dark:text-slate-100">{row.user_name}</p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">{row.user_email}</p>
                                    </td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        {row.plan_name || '-'} {row.subscription_status ? `(${row.subscription_status})` : ''}
                                    </td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        {row.expires_at ? new Date(row.expires_at).toLocaleDateString() : '-'}
                                    </td>
                                    <td className="py-2">
                                        <select
                                            value={planByTenant[row.tenant_id] ?? row.billing_plan_id ?? activePlans[0]?.id ?? ''}
                                            onChange={(e) => setPlanByTenant((prev) => ({ ...prev, [row.tenant_id]: e.target.value }))}
                                            className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1"
                                        >
                                            {activePlans.map((plan) => (
                                                <option key={plan.id} value={plan.id}>{plan.name}</option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="py-2">
                                        <input
                                            type="number"
                                            min={1}
                                            max={3650}
                                            value={durationByTenant[row.tenant_id] ?? 30}
                                            onChange={(e) => setDurationByTenant((prev) => ({ ...prev, [row.tenant_id]: e.target.value }))}
                                            className="w-24 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1"
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        <button
                                            type="button"
                                            onClick={() => submitReactivate(row)}
                                            disabled={processingTenantId === row.tenant_id}
                                            className="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                                        >
                                            {processingTenantId === row.tenant_id ? '...' : 'Reactiver'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
