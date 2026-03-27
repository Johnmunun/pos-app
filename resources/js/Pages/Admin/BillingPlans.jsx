import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';

export default function BillingPlans({ plans = [], subscriptions = [], overrides = [], tenants = [], compliance = [], featureCatalog = {} }) {
    const normalizePlanFeatures = (plan) => {
        const catalogEntries = Object.entries(featureCatalog || {});
        const currentFeatures = { ...(plan?.features || {}) };
        catalogEntries.forEach(([code, meta]) => {
            if (!currentFeatures[code]) {
                currentFeatures[code] = {
                    label: meta?.label || code,
                    enabled: Boolean(meta?.default_enabled),
                    limit: meta?.default_limit ?? null,
                };
                return;
            }
            currentFeatures[code] = {
                label: currentFeatures[code]?.label || meta?.label || code,
                enabled: Boolean(currentFeatures[code]?.enabled),
                limit: currentFeatures[code]?.limit ?? null,
            };
        });
        return currentFeatures;
    };

    const normalizePlan = (plan) => ({
        ...plan,
        features: normalizePlanFeatures(plan),
    });

    const [complianceSearch, setComplianceSearch] = useState('');
    const [complianceSort, setComplianceSort] = useState('risk');
    const [alertsOnly, setAlertsOnly] = useState(false);
    const [editablePlans, setEditablePlans] = useState(() => plans.map((plan) => normalizePlan(plan)));
    const [savingPlanId, setSavingPlanId] = useState(null);
    const [templateByPlan, setTemplateByPlan] = useState({});
    const [previewState, setPreviewState] = useState({ open: false, loading: false, planId: null, templateCode: '', changes: [], changesCount: 0 });
    const [healthTenantId, setHealthTenantId] = useState(tenants[0]?.id || '');
    const [healthPlanId, setHealthPlanId] = useState(plans[0]?.id || '');
    const [healthLoading, setHealthLoading] = useState(false);
    const [healthError, setHealthError] = useState('');
    const [fusionHealth, setFusionHealth] = useState(null);
    const assignForm = useForm({
        tenant_id: '',
        billing_plan_id: plans[0]?.id ?? '',
        status: 'active',
    });

    const plansById = useMemo(() => {
        const map = {};
        plans.forEach((plan) => { map[plan.id] = plan; });
        return map;
    }, [plans, featureCatalog]);

    const overrideForm = useForm({
        tenant_id: '',
        feature_code: 'api.payments',
        is_enabled: true,
        limit_value: '',
    });
    const featureCodes = useMemo(() => {
        const keys = Object.keys(featureCatalog || {});
        return keys.length > 0 ? keys : ['api.payments', 'products.max', 'users.max'];
    }, [featureCatalog]);

    useEffect(() => {
        setEditablePlans(plans.map((plan) => normalizePlan(plan)));
        setTemplateByPlan(
            plans.reduce((acc, plan) => {
                acc[plan.id] = plan.code || 'starter';
                return acc;
            }, {})
        );
    }, [plans]);

    useEffect(() => {
        if (!healthTenantId || !healthPlanId) {
            setFusionHealth(null);
            return;
        }

        let cancelled = false;
        const loadHealth = async () => {
            setHealthLoading(true);
            setHealthError('');
            try {
                const { data } = await axios.get(route('admin.billing.fusionpay.health'), {
                    params: {
                        tenant_id: healthTenantId,
                        billing_plan_id: healthPlanId,
                    },
                });
                if (!cancelled) {
                    setFusionHealth(data);
                }
            } catch (error) {
                if (!cancelled) {
                    setFusionHealth(null);
                    setHealthError(error?.response?.data?.message || 'Impossible de charger le diagnostic FusionPay.');
                }
            } finally {
                if (!cancelled) {
                    setHealthLoading(false);
                }
            }
        };

        loadHealth();
        return () => {
            cancelled = true;
        };
    }, [healthTenantId, healthPlanId]);

    const updatePlanField = (planId, field, value) => {
        setEditablePlans((current) => current.map((plan) => (
            plan.id === planId ? { ...plan, [field]: value } : plan
        )));
    };

    const updateFeatureField = (planId, featureCode, field, value) => {
        setEditablePlans((current) => current.map((plan) => {
            if (plan.id !== planId) return plan;
            const features = { ...(plan.features || {}) };
            const feature = { ...(features[featureCode] || { label: featureCode, enabled: false, limit: null }) };
            feature[field] = value;
            features[featureCode] = feature;
            return { ...plan, features };
        }));
    };

    const savePlan = (plan) => {
        setSavingPlanId(plan.id);
        router.put(route('admin.billing.plans.update', plan.id), {
            name: plan.name,
            description: plan.description || '',
            monthly_price: Number(plan.monthly_price ?? 0),
            annual_price: plan.annual_price === '' || plan.annual_price === null ? null : Number(plan.annual_price),
            currency_code: (plan.currency_code || 'USD').toString().toUpperCase(),
            promo_type: plan.promo_type || null,
            promo_value: plan.promo_value === '' || plan.promo_value === null ? null : Number(plan.promo_value),
            promo_starts_at: plan.promo_starts_at || null,
            promo_ends_at: plan.promo_ends_at || null,
            promo_label: plan.promo_label || null,
            is_active: !!plan.is_active,
            features: plan.features || {},
        }, {
            preserveScroll: true,
            onFinish: () => setSavingPlanId(null),
        });
    };

    const openTemplatePreview = async (planId) => {
        const templateCode = templateByPlan[planId] || 'starter';
        setPreviewState({ open: true, loading: true, planId, templateCode, changes: [], changesCount: 0 });
        try {
            const { data } = await axios.get(route('admin.billing.plans.preview-template', planId), {
                params: { template_code: templateCode },
            });
            setPreviewState({
                open: true,
                loading: false,
                planId,
                templateCode,
                changes: data?.changes || [],
                changesCount: data?.changes_count || 0,
            });
        } catch {
            setPreviewState({ open: true, loading: false, planId, templateCode, changes: [], changesCount: 0 });
        }
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

    const renderFeatureState = (feature) => {
        if (!feature) return <span className="text-slate-400">-</span>;
        const enabled = !!feature.enabled;
        const limit = feature.limit;
        return (
            <div className="flex flex-wrap items-center gap-1">
                <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${
                    enabled
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                }`}>
                    {enabled ? 'Actif' : 'Inactif'}
                </span>
                <span className="inline-flex rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200 px-2 py-0.5 text-[11px] font-medium">
                    {limit === null || limit === undefined ? 'Illimite' : `Limite ${limit}`}
                </span>
            </div>
        );
    };

    const previewRowClass = (from, to) => {
        const fromEnabled = from ? !!from.enabled : false;
        const toEnabled = to ? !!to.enabled : false;
        const fromLimit = from?.limit ?? null;
        const toLimit = to?.limit ?? null;

        if (!fromEnabled && toEnabled) {
            return 'bg-emerald-50/70 dark:bg-emerald-900/10';
        }
        if (fromEnabled && !toEnabled) {
            return 'bg-rose-50/70 dark:bg-rose-900/10';
        }
        if (fromEnabled === toEnabled && fromLimit !== toLimit) {
            return 'bg-amber-50/70 dark:bg-amber-900/10';
        }
        return '';
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
                    <a
                        href={route('admin.billing.subscriptions.expired')}
                        className="inline-flex items-center mt-3 rounded-lg border border-amber-300 dark:border-amber-700 px-3 py-2 text-sm text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                    >
                        Ouvrir l'interface abonnements expires
                    </a>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                    <div className="flex flex-col gap-3">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900 dark:text-white">Sante FusionPay</h3>
                            <p className="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                Verifie la conversion devise + minimum requis avant paiement.
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2">
                            <select
                                value={healthTenantId}
                                onChange={(e) => setHealthTenantId(e.target.value)}
                                className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm"
                            >
                                <option value="">Selectionner un tenant</option>
                                {tenants.map((tenant) => (
                                    <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                                ))}
                            </select>
                            <select
                                value={healthPlanId}
                                onChange={(e) => setHealthPlanId(e.target.value)}
                                className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm"
                            >
                                <option value="">Selectionner un plan</option>
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>{plan.name}</option>
                                ))}
                            </select>
                        </div>

                        {healthLoading ? (
                            <p className="text-sm text-slate-600 dark:text-slate-300">Verification FusionPay...</p>
                        ) : null}
                        {healthError ? (
                            <p className="text-sm text-rose-600 dark:text-rose-400">{healthError}</p>
                        ) : null}

                        {fusionHealth ? (
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Devise paiement</p>
                                    <p className="font-semibold text-slate-900 dark:text-white">{fusionHealth.payin_currency}</p>
                                </div>
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Taux utilise</p>
                                    <p className="font-semibold text-slate-900 dark:text-white">
                                        {fusionHealth.rate?.value !== null && fusionHealth.rate?.value !== undefined
                                            ? `${fusionHealth.rate.value} (${fusionHealth.rate.direction})`
                                            : 'Non defini'}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Montant converti</p>
                                    <p className="font-semibold text-slate-900 dark:text-white">
                                        {fusionHealth.converted_amount} {fusionHealth.payin_currency}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Montant original</p>
                                    <p className="font-semibold text-slate-900 dark:text-white">
                                        {fusionHealth.original_amount} {fusionHealth.original_currency}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Minimum FusionPay</p>
                                    <p className="font-semibold text-slate-900 dark:text-white">
                                        {fusionHealth.minimum_amount} {fusionHealth.payin_currency}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Statut</p>
                                    <p className={`font-semibold ${fusionHealth.status === 'ok' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'}`}>
                                        {fusionHealth.status === 'ok' ? 'OK' : 'Attention'}
                                    </p>
                                </div>
                            </div>
                        ) : null}

                        {fusionHealth?.status !== 'ok' && (
                            <a
                                href={route('settings.currencies')}
                                className="inline-flex items-center rounded-lg border border-amber-300 dark:border-amber-700 px-3 py-2 text-sm text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                            >
                                Configurer les devises
                            </a>
                        )}

                        {fusionHealth?.issues?.length ? (
                            <ul className="text-xs text-amber-700 dark:text-amber-300 space-y-1">
                                {fusionHealth.issues.map((issue) => (
                                    <li key={issue}>- {issue}</li>
                                ))}
                            </ul>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {editablePlans.map((plan) => (
                        <div key={plan.id} className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <h2 className="text-lg font-semibold text-slate-900 dark:text-white">{plan.name}</h2>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{plan.code}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        {(plan.currency_code || 'USD').toUpperCase()} {plan.monthly_price ?? 0}/mois
                                    </p>
                                    {plan.is_promo_active ? (
                                        <p className="text-xs text-emerald-600 dark:text-emerald-400">
                                            Promo active: {plan.monthly_price_effective ?? plan.monthly_price}/mois
                                        </p>
                                    ) : null}
                                </div>
                                <button
                                    type="button"
                                    onClick={() => savePlan(plan)}
                                    disabled={savingPlanId === plan.id}
                                    className="px-3 py-1.5 text-xs rounded-lg bg-amber-600 text-white hover:bg-amber-700"
                                >
                                    {savingPlanId === plan.id ? 'Enregistrement...' : 'Enregistrer'}
                                </button>
                            </div>

                            <div className="space-y-2 text-sm">
                                <div className="grid grid-cols-[1fr_auto] gap-2 items-center">
                                    <select
                                        value={templateByPlan[plan.id] || 'starter'}
                                        onChange={(e) => setTemplateByPlan((current) => ({ ...current, [plan.id]: e.target.value }))}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    >
                                        <option value="starter">Template Starter</option>
                                        <option value="pro">Template Pro</option>
                                        <option value="enterprise">Template Enterprise</option>
                                    </select>
                                    <button
                                        type="button"
                                        onClick={() => openTemplatePreview(plan.id)}
                                        className="px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                    >
                                        Preview template
                                    </button>
                                </div>
                                <div>
                                    <label className="mb-1 block text-xs text-slate-600 dark:text-slate-300">Devise (ISO)</label>
                                    <input
                                        value={plan.currency_code ?? 'USD'}
                                        onChange={(e) => updatePlanField(plan.id, 'currency_code', e.target.value.toUpperCase().slice(0, 3))}
                                        className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="USD"
                                        maxLength={3}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <input
                                        value={plan.monthly_price ?? ''}
                                        onChange={(e) => updatePlanField(plan.id, 'monthly_price', e.target.value)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="Mensuel"
                                    />
                                    <input
                                        value={plan.annual_price ?? ''}
                                        onChange={(e) => updatePlanField(plan.id, 'annual_price', e.target.value)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="Annuel"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <select
                                        value={plan.promo_type ?? ''}
                                        onChange={(e) => updatePlanField(plan.id, 'promo_type', e.target.value || null)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    >
                                        <option value="">Aucune promo</option>
                                        <option value="percentage">% Pourcentage</option>
                                        <option value="fixed">Montant fixe</option>
                                    </select>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={plan.promo_value ?? ''}
                                        onChange={(e) => updatePlanField(plan.id, 'promo_value', e.target.value)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                        placeholder="Valeur promo"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <input
                                        type="datetime-local"
                                        value={plan.promo_starts_at ? String(plan.promo_starts_at).slice(0, 16) : ''}
                                        onChange={(e) => updatePlanField(plan.id, 'promo_starts_at', e.target.value || null)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    />
                                    <input
                                        type="datetime-local"
                                        value={plan.promo_ends_at ? String(plan.promo_ends_at).slice(0, 16) : ''}
                                        onChange={(e) => updatePlanField(plan.id, 'promo_ends_at', e.target.value || null)}
                                        className="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    />
                                </div>
                                <input
                                    value={plan.promo_label ?? ''}
                                    onChange={(e) => updatePlanField(plan.id, 'promo_label', e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    placeholder="Libelle promo (ex: -20% this month)"
                                />
                                <textarea
                                    value={plan.description || ''}
                                    onChange={(e) => updatePlanField(plan.id, 'description', e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2"
                                    rows={2}
                                />
                            </div>

                            <div className="space-y-2">
                                <p className="text-xs font-medium text-slate-700 dark:text-slate-300">Features</p>
                                {Object.entries(plan.features || {})
                                    .sort((a, b) => {
                                        const ga = featureCatalog?.[a[0]]?.group || 'other';
                                        const gb = featureCatalog?.[b[0]]?.group || 'other';
                                        if (ga !== gb) return ga.localeCompare(gb);
                                        const la = a[1]?.label || a[0];
                                        const lb = b[1]?.label || b[0];
                                        return String(la).localeCompare(String(lb));
                                    })
                                    .map(([code, feature]) => {
                                        const meta = featureCatalog?.[code] || {};
                                        const isLimit = meta?.type === 'limit';
                                        return (
                                            <div key={code} className="rounded-lg border border-slate-200 dark:border-slate-700 p-2">
                                                <div className="flex items-start justify-between gap-2 mb-2">
                                                    <div>
                                                        <p className="text-xs text-slate-700 dark:text-slate-200 font-medium">{feature.label || code}</p>
                                                        <p className="text-[11px] text-slate-500 dark:text-slate-400">{meta?.group || 'general'}</p>
                                                    </div>
                                                    <label className="inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-300">
                                                        <input
                                                            type="checkbox"
                                                            checked={!!feature.enabled}
                                                            onChange={(e) => updateFeatureField(plan.id, code, 'enabled', e.target.checked)}
                                                        />
                                                        Actif
                                                    </label>
                                                </div>
                                                {isLimit ? (
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        value={feature.limit ?? ''}
                                                        onChange={(e) => updateFeatureField(plan.id, code, 'limit', e.target.value === '' ? null : Number(e.target.value))}
                                                        className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs"
                                                        placeholder="Limite (vide = illimite)"
                                                    />
                                                ) : (
                                                    <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                                        {feature.enabled ? 'Disponible' : 'Monter de niveau pour activer'}
                                                    </p>
                                                )}
                                            </div>
                                        );
                                    })}
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
                                disabled={!assignForm.data.tenant_id || assignForm.processing}
                                className="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                {assignForm.processing ? 'Assignation...' : 'Assigner'}
                            </button>
                            {assignForm.errors.tenant_id && (
                                <p className="text-xs text-rose-600">{assignForm.errors.tenant_id}</p>
                            )}
                            {assignForm.errors.billing_plan_id && (
                                <p className="text-xs text-rose-600">{assignForm.errors.billing_plan_id}</p>
                            )}
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
                                {featureCodes.map((featureCode) => (
                                    <option key={featureCode} value={featureCode}>
                                        {featureCatalog?.[featureCode]?.label || featureCode}
                                    </option>
                                ))}
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
                                disabled={!overrideForm.data.tenant_id || overrideForm.processing}
                                className="w-full sm:w-auto px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                {overrideForm.processing ? 'Enregistrement...' : 'Enregistrer override'}
                            </button>
                            {overrideForm.errors.tenant_id && (
                                <p className="text-xs text-rose-600">{overrideForm.errors.tenant_id}</p>
                            )}
                            {overrideForm.errors.feature_code && (
                                <p className="text-xs text-rose-600">{overrideForm.errors.feature_code}</p>
                            )}
                            {overrideForm.errors.limit_value && (
                                <p className="text-xs text-rose-600">{overrideForm.errors.limit_value}</p>
                            )}
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
            {previewState.open ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-3xl rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-semibold text-slate-900 dark:text-white">
                                Preview template "{previewState.templateCode}"
                            </h3>
                            <button
                                type="button"
                                className="text-sm px-2 py-1 rounded border border-slate-300 dark:border-slate-600"
                                onClick={() => setPreviewState({ open: false, loading: false, planId: null, templateCode: '', changes: [], changesCount: 0 })}
                            >
                                Fermer
                            </button>
                        </div>
                        {previewState.loading ? (
                            <p className="text-sm text-slate-600 dark:text-slate-300">Chargement...</p>
                        ) : (
                            <>
                                <p className="text-sm text-slate-600 dark:text-slate-300">
                                    {previewState.changesCount} changement(s) detecte(s)
                                </p>
                                <div className="max-h-80 overflow-auto rounded border border-slate-200 dark:border-slate-700">
                                    <table className="w-full text-xs">
                                        <thead className="bg-slate-50 dark:bg-slate-900/50">
                                            <tr>
                                                <th className="text-left p-2">Feature</th>
                                                <th className="text-left p-2">Actuel</th>
                                                <th className="text-left p-2">Template</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {previewState.changes.map((item) => (
                                                <tr
                                                    key={item.code}
                                                    className={`border-t border-slate-200 dark:border-slate-700 ${previewRowClass(item.from, item.to)}`}
                                                >
                                                    <td className="p-2 font-medium">{item.code}</td>
                                                    <td className="p-2">{renderFeatureState(item.from)}</td>
                                                    <td className="p-2">{renderFeatureState(item.to)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        className="px-3 py-2 text-xs rounded border border-slate-300 dark:border-slate-600"
                                        onClick={() => setPreviewState({ open: false, loading: false, planId: null, templateCode: '', changes: [], changesCount: 0 })}
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="button"
                                        className="px-3 py-2 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-700"
                                        onClick={() => {
                                            router.post(route('admin.billing.plans.apply-template', previewState.planId), {
                                                template_code: previewState.templateCode,
                                            });
                                            setPreviewState({ open: false, loading: false, planId: null, templateCode: '', changes: [], changesCount: 0 });
                                        }}
                                    >
                                        Confirmer l'application
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            ) : null}
        </AppLayout>
    );
}
