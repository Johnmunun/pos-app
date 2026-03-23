import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function BillingPlans({ plans = [], subscriptions = [], overrides = [], tenants = [], compliance = [] }) {
    const [complianceSearch, setComplianceSearch] = useState('');
    const [complianceSort, setComplianceSort] = useState('risk');
    const [alertsOnly, setAlertsOnly] = useState(false);
    const assignForm = useForm({
        tenant_id: '',
        billing_plan_id: plans[0]?.id ?? '',
        status: 'active',
    });

    const plansById = useMemo(() => {
        const map = {};
        plans.forEach((plan) => { map[plan.id] = plan; });
        return map;
    }, [plans]);

    const overrideForm = useForm({
        tenant_id: '',
        feature_code: 'api.payments',
        is_enabled: true,
        limit_value: '',
    });

    const savePlan = (plan) => {
        router.put(route('admin.billing.plans.update', plan.id), {
            name: plan.name,
            description: plan.description || '',
            monthly_price: plan.monthly_price ?? 0,
            annual_price: plan.annual_price ?? null,
            is_active: !!plan.is_active,
            features: plan.features || {},
        });
    };

    const filteredCompliance = useMemo(() => {
        const withScore = compliance.map((item) => {
            const productLimit = item.features?.['products.max']?.limit;
            const usersLimit = item.features?.['users.max']?.limit;
            const productRatio = item.features?.['products.max']?.enabled && productLimit ? ((item.usage?.products || 0) / productLimit) : 0;
            const usersRatio = item.features?.['users.max']?.enabled && usersLimit ? ((item.usage?.users || 0) / usersLimit) : 0;
            const risk = Math.max(productRatio, usersRatio);
            return { ...item, _risk: risk };
        });

        const sorted = [...withScore].sort((a, b) => {
            if (complianceSort === 'name') {
                return (a.tenant_name || '').localeCompare(b.tenant_name || '');
            }
            if (b._risk !== a._risk) return b._risk - a._risk;
            return (a.tenant_name || '').localeCompare(b.tenant_name || '');
        });

        let result = sorted;
        if (alertsOnly) {
            result = result.filter((item) => item._risk >= 0.8);
        }

        const query = complianceSearch.trim().toLowerCase();
        if (!query) return result;
        return result.filter((item) => (item.tenant_name || '').toLowerCase().includes(query));
    }, [compliance, complianceSearch, complianceSort, alertsOnly]);

    const statusBadgeClass = (enabled) => (
        enabled
            ? 'inline-flex rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 px-2 py-0.5 text-xs font-medium'
            : 'inline-flex rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 px-2 py-0.5 text-xs font-medium'
    );

    const usageBadgeClass = (ratio) => {
        if (ratio >= 1) return 'inline-flex rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 px-2 py-0.5 text-xs font-medium';
        if (ratio >= 0.8) return 'inline-flex rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 text-xs font-medium';
        return 'inline-flex rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 px-2 py-0.5 text-xs font-medium';
    };

    const renderUsageLabel = (enabled, usage, limit) => {
        if (!enabled) return 'Desactive';
        if (limit === null || limit === undefined) return `${usage} utilise (illimite)`;
        if (Number(limit) === 0) return 'Aucune creation autorisee (limite 0)';
        return `${usage} utilise sur ${limit}`;
    };

    return (
        <AppLayout fullWidth>
            <Head title="Plans & Limitations" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 bg-gray-50 dark:bg-slate-900 min-h-screen">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Plans & Limitations</h1>
                    <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">
                        Gestion dynamique des packs (Starter, Pro, Enterprise) sans hardcode.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {plans.map((plan) => (
                        <div key={plan.id} className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <h2 className="text-lg font-semibold text-slate-900 dark:text-white">{plan.name}</h2>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{plan.code}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => savePlan(plan)}
                                    className="px-3 py-1.5 text-xs rounded-lg bg-amber-600 text-white hover:bg-amber-700"
                                >
                                    Enregistrer
                                </button>
                            </div>

                            <div className="space-y-2 text-sm">
                                <div className="grid grid-cols-2 gap-2">
                                    <input
                                        value={plan.monthly_price ?? ''}
                                        onChange={(e) => { plan.monthly_price = e.target.value; }}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="Mensuel"
                                    />
                                    <input
                                        value={plan.annual_price ?? ''}
                                        onChange={(e) => { plan.annual_price = e.target.value; }}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="Annuel"
                                    />
                                </div>
                                <textarea
                                    value={plan.description || ''}
                                    onChange={(e) => { plan.description = e.target.value; }}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    rows={2}
                                />
                            </div>

                            <div className="space-y-2">
                                <p className="text-xs font-medium text-slate-700 dark:text-slate-300">Features</p>
                                {Object.entries(plan.features || {}).map(([code, feature]) => (
                                    <div key={code} className="rounded-lg border border-slate-200 dark:border-slate-700 p-2">
                                        <p className="text-xs text-slate-700 dark:text-slate-200">{feature.label || code}</p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                            {feature.enabled ? 'Active' : 'Inactive'}
                                            {feature.limit !== null && feature.limit !== undefined ? ` - Limite: ${feature.limit}` : ' - Illimite'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white mb-3">Assigner un plan a un tenant</h3>
                        <div className="space-y-3">
                            <select
                                value={assignForm.data.tenant_id}
                                onChange={(e) => assignForm.setData('tenant_id', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                            >
                                <option value="">Selectionner un tenant</option>
                                {tenants.map((tenant) => (
                                    <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                                ))}
                            </select>
                            <select
                                value={assignForm.data.billing_plan_id}
                                onChange={(e) => assignForm.setData('billing_plan_id', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                            >
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>{plan.name}</option>
                                ))}
                            </select>
                            <button
                                type="button"
                                onClick={() => assignForm.post(route('admin.billing.subscriptions.assign'))}
                                className="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                Assigner
                            </button>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 overflow-x-auto">
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white mb-3">Abonnements recents</h3>
                        <table className="w-full min-w-[520px] text-sm">
                            <thead>
                                <tr className="text-left text-slate-500 dark:text-slate-300">
                                    <th className="pb-2">Tenant</th>
                                    <th className="pb-2">Plan</th>
                                    <th className="pb-2">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {subscriptions.map((s) => (
                                    <tr key={s.id} className="border-t border-slate-200 dark:border-slate-700">
                                        <td className="py-2 text-slate-900 dark:text-slate-100">{s.tenant_name}</td>
                                        <td className="py-2 text-slate-900 dark:text-slate-100">{plansById[s.billing_plan_id]?.name || s.plan_name}</td>
                                        <td className="py-2 text-slate-700 dark:text-slate-300">{s.status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white mb-3">Override tenant par feature</h3>
                        <div className="space-y-3">
                            <select
                                value={overrideForm.data.tenant_id}
                                onChange={(e) => overrideForm.setData('tenant_id', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                            >
                                <option value="">Selectionner un tenant</option>
                                {tenants.map((tenant) => (
                                    <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                                ))}
                            </select>
                            <select
                                value={overrideForm.data.feature_code}
                                onChange={(e) => overrideForm.setData('feature_code', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                            >
                                <option value="api.payments">api.payments</option>
                                <option value="products.max">products.max</option>
                                <option value="users.max">users.max</option>
                            </select>
                            <div className="flex items-center gap-3">
                                <label className="text-sm text-slate-700 dark:text-slate-300">Active</label>
                                <input
                                    type="checkbox"
                                    checked={!!overrideForm.data.is_enabled}
                                    onChange={(e) => overrideForm.setData('is_enabled', e.target.checked)}
                                />
                            </div>
                            <input
                                type="number"
                                min="0"
                                value={overrideForm.data.limit_value}
                                onChange={(e) => overrideForm.setData('limit_value', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                placeholder="Limite (optionnelle)"
                            />
                            <button
                                type="button"
                                onClick={() => overrideForm.post(route('admin.billing.overrides.upsert'))}
                                className="w-full sm:w-auto px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                Enregistrer override
                            </button>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 overflow-x-auto">
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white mb-3">Overrides recents</h3>
                        <table className="w-full min-w-[520px] text-sm">
                            <thead>
                                <tr className="text-left text-slate-500 dark:text-slate-300">
                                    <th className="pb-2">Tenant</th>
                                    <th className="pb-2">Feature</th>
                                    <th className="pb-2">Active</th>
                                    <th className="pb-2">Limite</th>
                                </tr>
                            </thead>
                            <tbody>
                                {overrides.map((o) => (
                                    <tr key={o.id} className="border-t border-slate-200 dark:border-slate-700">
                                        <td className="py-2 text-slate-900 dark:text-slate-100">{o.tenant_name}</td>
                                        <td className="py-2 text-slate-900 dark:text-slate-100">{o.feature_code}</td>
                                        <td className="py-2 text-slate-700 dark:text-slate-300">{o.is_enabled === null ? '-' : (o.is_enabled ? 'Oui' : 'Non')}</td>
                                        <td className="py-2 text-slate-700 dark:text-slate-300">{o.limit_value ?? '-'}</td>
                                        <td className="py-2 text-right">
                                            <button
                                                type="button"
                                                onClick={() => router.delete(route('admin.billing.overrides.delete', o.id))}
                                                className="px-2 py-1 text-xs rounded bg-rose-600 text-white hover:bg-rose-700"
                                            >
                                                Reset
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 overflow-x-auto">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white">Conformite plan (effectif)</h3>
                        <div className="flex gap-2">
                            <input
                                value={complianceSearch}
                                onChange={(e) => setComplianceSearch(e.target.value)}
                                placeholder="Rechercher tenant..."
                                className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm"
                            />
                            <select
                                value={complianceSort}
                                onChange={(e) => setComplianceSort(e.target.value)}
                                className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm"
                            >
                                <option value="risk">Risque d'abord</option>
                                <option value="name">Nom (A-Z)</option>
                            </select>
                            <button
                                type="button"
                                onClick={() => setAlertsOnly((v) => !v)}
                                className={`rounded-lg px-3 py-2 text-sm border ${
                                    alertsOnly
                                        ? 'bg-amber-600 border-amber-600 text-white'
                                        : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200'
                                }`}
                            >
                                Alertes seulement
                            </button>
                            <a
                                href={route('admin.billing.compliance.export-csv')}
                                className="inline-flex items-center rounded-lg bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 px-3 py-2 text-sm"
                            >
                                Export CSV
                            </a>
                        </div>
                    </div>
                    <p className="mb-3 text-xs text-slate-600 dark:text-slate-300">
                        Exemple: <span className="font-medium">0 utilise sur 100</span> = aucune creation pour le moment;
                        <span className="font-medium"> limite 0</span> = creation bloquee.
                    </p>
                    <table className="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr className="text-left text-slate-500 dark:text-slate-300">
                                <th className="pb-2">Tenant</th>
                                <th className="pb-2">products.max</th>
                                <th className="pb-2">users.max</th>
                                <th className="pb-2">api.payments</th>
                                <th className="pb-2">analytics.advanced</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredCompliance.map((c) => (
                                <tr key={c.tenant_id} className="border-t border-slate-200 dark:border-slate-700">
                                    <td className="py-2 text-slate-900 dark:text-slate-100">{c.tenant_name}</td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        {c.features?.['products.max']?.enabled ? (
                                            c.features?.['products.max']?.limit !== null && c.features?.['products.max']?.limit !== undefined
                                                ? (
                                                    <div className="flex flex-col gap-1">
                                                        <span className={usageBadgeClass((c.usage?.products || 0) / c.features?.['products.max']?.limit)}>
                                                            {renderUsageLabel(true, (c.usage?.products || 0), c.features?.['products.max']?.limit)}
                                                        </span>
                                                    </div>
                                                )
                                                : renderUsageLabel(true, (c.usage?.products || 0), null)
                                        ) : renderUsageLabel(false, 0, null)}
                                    </td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        {c.features?.['users.max']?.enabled ? (
                                            c.features?.['users.max']?.limit !== null && c.features?.['users.max']?.limit !== undefined
                                                ? (
                                                    <div className="flex flex-col gap-1">
                                                        <span className={usageBadgeClass((c.usage?.users || 0) / c.features?.['users.max']?.limit)}>
                                                            {renderUsageLabel(true, (c.usage?.users || 0), c.features?.['users.max']?.limit)}
                                                        </span>
                                                    </div>
                                                )
                                                : renderUsageLabel(true, (c.usage?.users || 0), null)
                                        ) : renderUsageLabel(false, 0, null)}
                                    </td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        <span className={statusBadgeClass(!!c.features?.['api.payments']?.enabled)}>
                                            {c.features?.['api.payments']?.enabled ? 'Active' : 'Desactive'}
                                        </span>
                                    </td>
                                    <td className="py-2 text-slate-700 dark:text-slate-300">
                                        <span className={statusBadgeClass(!!c.features?.['analytics.advanced']?.enabled)}>
                                            {c.features?.['analytics.advanced']?.enabled ? 'Active' : 'Desactive'}
                                        </span>
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
