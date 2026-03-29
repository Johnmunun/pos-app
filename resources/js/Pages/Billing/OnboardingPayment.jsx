import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';

export default function OnboardingPayment() {
    const page = usePage();
    const authUser = page?.props?.auth?.user || null;
    const [plans, setPlans] = useState([]);
    const [selectedPlanId, setSelectedPlanId] = useState(null);
    const [paymentMethod, setPaymentMethod] = useState('mobile_money');
    const [phone, setPhone] = useState('');
    const [billingCycle, setBillingCycle] = useState('monthly');
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const [detailsPlan, setDetailsPlan] = useState(null);

    const selectedPlan = useMemo(
        () => plans.find((p) => p.id === selectedPlanId) || null,
        [plans, selectedPlanId]
    );
    const sortedPlans = useMemo(
        () => [...plans].sort((a, b) => {
            const pa = Number(a?.pricing?.monthly_effective ?? a?.pricing?.monthly ?? 0);
            const pb = Number(b?.pricing?.monthly_effective ?? b?.pricing?.monthly ?? 0);
            return pa - pb;
        }),
        [plans]
    );
    const selectedPlanIndex = useMemo(
        () => sortedPlans.findIndex((p) => p.id === selectedPlanId),
        [sortedPlans, selectedPlanId]
    );
    const nextPlan = useMemo(
        () => (selectedPlanIndex >= 0 ? sortedPlans[selectedPlanIndex + 1] || null : null),
        [sortedPlans, selectedPlanIndex]
    );
    const selectedFeatureEntries = useMemo(
        () => Object.entries(selectedPlan?.features || {}),
        [selectedPlan]
    );
    const visibleSelectedFeatures = useMemo(() => {
        const ordered = selectedFeatureEntries.sort((a, b) => {
            const al = String(a?.[1]?.label || a?.[0] || '');
            const bl = String(b?.[1]?.label || b?.[0] || '');
            return al.localeCompare(bl);
        });
        return ordered.slice(0, 6);
    }, [selectedFeatureEntries]);
    const upgradeHighlights = useMemo(() => {
        if (!selectedPlan || !nextPlan) return [];
        const current = selectedPlan.features || {};
        const next = nextPlan.features || {};
        const improvements = [];
        Object.entries(next).forEach(([code, feature]) => {
            const cur = current[code] || {};
            const nextEnabled = Boolean(feature?.enabled);
            const curEnabled = Boolean(cur?.enabled);
            const nextLimit = feature?.limit;
            const curLimit = cur?.limit;
            const unlocked = nextEnabled && !curEnabled;
            const betterLimit = nextEnabled && curEnabled && (curLimit !== null && curLimit !== undefined) && (nextLimit === null || nextLimit === undefined || Number(nextLimit) > Number(curLimit));
            if (unlocked || betterLimit) {
                improvements.push({
                    code,
                    label: feature?.label || code,
                    limit: nextLimit,
                });
            }
        });
        return improvements.slice(0, 5);
    }, [selectedPlan, nextPlan]);

    useEffect(() => {
        const loadPlans = async () => {
            try {
                const plansRes = await axios.get(route('api.billing.plans.public'));
                const availablePlans = Array.isArray(plansRes?.data?.plans)
                    ? plansRes.data.plans
                    : Array.isArray(plansRes?.data?.data)
                        ? plansRes.data.data
                        : [];
                setPlans(availablePlans);
                if (availablePlans.length > 0) {
                    setSelectedPlanId(availablePlans[0].id);
                }
            } catch (error) {
                setMessage('Impossible de charger les plans pour le moment.');
            }
        };

        loadPlans();
    }, []);

    const initiatePayment = async () => {
        if (!selectedPlanId) {
            setMessage('Veuillez choisir un plan.');
            return;
        }

        setLoading(true);
        setMessage('');

        try {
            const { data } = await axios.post(route('api.billing.payments.initiate'), {
                billing_plan_id: selectedPlanId,
                payment_method: paymentMethod,
                phone: phone || undefined,
                billing_cycle: billingCycle,
                customer_name: authUser?.name || 'Client',
                customer_email: authUser?.email || undefined,
            });

            if (data?.checkout_url) {
                window.location.href = data.checkout_url;
                return;
            }

            if (data?.transaction_id) {
                window.location.href = route('billing.payments.show', data.transaction_id);
                return;
            }

            setMessage('Paiement initie, mais aucune URL de paiement n’a ete retournee.');
        } catch (error) {
            setMessage(error?.response?.data?.message || 'Erreur pendant l’initiation du paiement.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Paiement abonnement</h2>}
        >
            <Head title="Paiement avant activation" />

            <div className="py-8">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Finalisez votre paiement
                        </h1>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Le paiement est requis avant d'acceder a la page de validation de compte.
                        </p>

                        <div className="space-y-2 mb-4">
                            {plans.length > 0 ? (
                                plans.map((plan) => (
                                    <label
                                        key={plan.id}
                                        className={`flex items-center justify-between p-3 rounded-lg border cursor-pointer ${
                                            selectedPlanId === plan.id
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                : 'border-gray-200 dark:border-gray-600'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <div>
                                                <p className="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                    {plan.name}
                                                    {String(plan?.code || '').toLowerCase() === 'pro' ? (
                                                        <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 text-[10px] font-semibold">
                                                            Populaire
                                                        </span>
                                                    ) : null}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {(plan?.pricing?.currency_code || 'USD')} {billingCycle === 'annual'
                                                        ? (plan?.pricing?.annual_effective ?? plan?.pricing?.annual ?? 0)
                                                        : (plan?.pricing?.monthly_effective ?? plan?.pricing?.monthly ?? 0)
                                                    }
                                                    {billingCycle === 'annual' ? '/an' : '/mois'}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    setDetailsPlan(plan);
                                                }}
                                                className="w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Voir details du plan"
                                            >
                                                ?
                                            </button>
                                        </div>
                                        <input
                                            type="radio"
                                            name="selected_plan"
                                            checked={selectedPlanId === plan.id}
                                            onChange={() => setSelectedPlanId(plan.id)}
                                        />
                                    </label>
                                ))
                            ) : (
                                <p className="text-xs text-amber-700 dark:text-amber-300">
                                    Aucun plan actif disponible pour le moment.
                                </p>
                            )}
                        </div>

                        {selectedPlan && (
                            <div className="mb-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 p-4">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                                    Ce plan inclut
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {visibleSelectedFeatures.map(([code, feature]) => (
                                        <div
                                            key={code}
                                            className={`rounded-lg border px-3 py-2 relative ${
                                                feature?.enabled
                                                    ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
                                                    : 'border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 pointer-events-none'
                                            }`}
                                        >
                                            {!feature?.enabled ? (
                                                <span className="absolute top-2 right-2 inline-flex items-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 text-[10px] font-semibold">
                                                    Monter de niveau
                                                </span>
                                            ) : null}
                                            <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                {feature?.label || code}
                                            </p>
                                            <p className={`text-xs mt-0.5 ${feature?.enabled ? 'text-gray-600 dark:text-gray-400' : 'text-gray-500 dark:text-gray-500 blur-[1px]'}`}>
                                                {!feature?.enabled
                                                    ? 'Indisponible'
                                                    : (feature?.limit === null || feature?.limit === undefined ? 'Illimite' : `Limite: ${feature.limit}`)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                                {selectedFeatureEntries.length > visibleSelectedFeatures.length ? (
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        +{selectedFeatureEntries.length - visibleSelectedFeatures.length} autres fonctionnalites dans le detail du plan.
                                    </p>
                                ) : null}
                            </div>
                        )}

                        {nextPlan && upgradeHighlights.length > 0 && (
                            <div className="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                                <p className="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                    Monter vers {nextPlan.name} debloque aussi:
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {upgradeHighlights.map((it) => (
                                        <div key={it.code} className="text-xs text-amber-900 dark:text-amber-200 bg-white/70 dark:bg-gray-900/30 rounded-md px-2 py-1.5 border border-amber-200/70 dark:border-amber-800/50">
                                            {it.label}
                                            {it.limit === null || it.limit === undefined ? ' (illimite)' : ` (jusqu'a ${it.limit})`}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-2 mb-3">
                            <button
                                type="button"
                                onClick={() => setBillingCycle('monthly')}
                                className={`px-3 py-2 rounded-lg text-sm border ${
                                    billingCycle === 'monthly'
                                        ? 'bg-amber-500 text-white border-amber-500'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                Mensuel
                            </button>
                            <button
                                type="button"
                                onClick={() => setBillingCycle('annual')}
                                className={`px-3 py-2 rounded-lg text-sm border ${
                                    billingCycle === 'annual'
                                        ? 'bg-amber-500 text-white border-amber-500'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                Annuel
                            </button>
                        </div>

                        <div className="grid grid-cols-2 gap-2 mb-3">
                            <button
                                type="button"
                                onClick={() => setPaymentMethod('mobile_money')}
                                className={`px-3 py-2 rounded-lg text-sm border ${
                                    paymentMethod === 'mobile_money'
                                        ? 'bg-amber-500 text-white border-amber-500'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                Mobile Money
                            </button>
                            <button
                                type="button"
                                onClick={() => setPaymentMethod('card')}
                                className={`px-3 py-2 rounded-lg text-sm border ${
                                    paymentMethod === 'card'
                                        ? 'bg-amber-500 text-white border-amber-500'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                Carte
                            </button>
                        </div>

                        <input
                            type="text"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            placeholder="Numero mobile (si Mobile Money)"
                            className="w-full mb-3 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                        />

                        {selectedPlan && (
                            <p className="text-xs text-gray-600 dark:text-gray-400 mb-4">
                                Montant: {selectedPlan?.pricing?.currency_code || 'USD'} {billingCycle === 'annual'
                                    ? (selectedPlan?.pricing?.annual_effective ?? selectedPlan?.pricing?.annual ?? 0)
                                    : (selectedPlan?.pricing?.monthly_effective ?? selectedPlan?.pricing?.monthly ?? 0)
                                }
                            </p>
                        )}

                        <button
                            type="button"
                            onClick={initiatePayment}
                            disabled={loading}
                            className="px-4 py-2 rounded-lg text-sm bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-50"
                        >
                            {loading ? '...' : 'Payer maintenant'}
                        </button>

                        {message && <p className="mt-3 text-sm text-amber-700 dark:text-amber-300">{message}</p>}
                    </div>
                </div>
            </div>

            {detailsPlan && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="plan-details-title"
                >
                    <div className="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl overflow-hidden">
                        <div className="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4 flex items-start justify-between gap-3">
                            <div>
                                <h3
                                    id="plan-details-title"
                                    className="text-lg font-bold text-white tracking-tight"
                                >
                                    {detailsPlan.name}
                                </h3>
                                <p className="text-sm text-amber-50/95 mt-0.5">
                                    Avantages et limites du plan
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setDetailsPlan(null)}
                                className="shrink-0 rounded-lg bg-white/15 hover:bg-white/25 text-white text-sm font-medium px-3 py-1.5 transition-colors"
                            >
                                Fermer
                            </button>
                        </div>

                        <div className="px-5 pt-4 pb-2">
                            <span className="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-amber-200/80 dark:border-amber-700/50 px-3 py-1 text-sm font-medium">
                                Prix :{' '}
                                <span className="ml-1 font-semibold tabular-nums">
                                    {(detailsPlan?.pricing?.currency_code || 'USD')}{' '}
                                    {detailsPlan?.pricing?.monthly_effective ?? detailsPlan?.pricing?.monthly ?? 0}
                                    /mois
                                </span>
                            </span>
                        </div>

                        <div className="px-5 pb-5">
                            <div className="max-h-80 overflow-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-100/90 dark:bg-gray-900/80 sticky top-0 z-[1] border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th className="text-left p-3 font-semibold text-gray-700 dark:text-gray-200">
                                                Fonctionnalité
                                            </th>
                                            <th className="text-left p-3 font-semibold text-gray-700 dark:text-gray-200">
                                                État
                                            </th>
                                            <th className="text-left p-3 font-semibold text-gray-700 dark:text-gray-200">
                                                Limite
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800/90">
                                        {Object.entries(detailsPlan.features || {}).map(([code, feature]) => (
                                            <tr
                                                key={code}
                                                className={
                                                    feature?.enabled
                                                        ? 'hover:bg-amber-50/50 dark:hover:bg-amber-950/20'
                                                        : 'opacity-90 bg-gray-50/50 dark:bg-gray-900/30'
                                                }
                                            >
                                                <td
                                                    className={`p-3 text-gray-800 dark:text-gray-200 ${feature?.enabled ? '' : 'blur-[1px] select-none'}`}
                                                >
                                                    {feature?.label || code}
                                                </td>
                                                <td className="p-3">
                                                    {feature?.enabled ? (
                                                        <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 px-2.5 py-0.5 text-xs font-semibold">
                                                            Disponible
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 px-2.5 py-0.5 text-xs font-semibold">
                                                            Non inclus
                                                        </span>
                                                    )}
                                                </td>
                                                <td
                                                    className={`p-3 text-gray-600 dark:text-gray-400 ${feature?.enabled ? '' : 'blur-[1px] select-none'}`}
                                                >
                                                    {feature?.limit === null || feature?.limit === undefined
                                                        ? 'Illimité'
                                                        : feature.limit}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
