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
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-2xl rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                                {detailsPlan.name} - avantages et limites
                            </h3>
                            <button
                                type="button"
                                onClick={() => setDetailsPlan(null)}
                                className="px-2 py-1 rounded border border-slate-300 dark:border-slate-600 text-sm"
                            >
                                Fermer
                            </button>
                        </div>

                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            Prix: {(detailsPlan?.pricing?.currency_code || 'USD')} {detailsPlan?.pricing?.monthly_effective ?? detailsPlan?.pricing?.monthly ?? 0}/mois
                        </p>

                        <div className="max-h-72 overflow-auto rounded border border-slate-200 dark:border-slate-700">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th className="text-left p-2">Fonctionnalite</th>
                                        <th className="text-left p-2">Etat</th>
                                        <th className="text-left p-2">Limite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(detailsPlan.features || {}).map(([code, feature]) => (
                                        <tr key={code} className="border-t border-slate-200 dark:border-slate-700">
                                            <td className="p-2">{feature?.label || code}</td>
                                            <td className="p-2">
                                                {feature?.enabled ? (
                                                    <span className="text-emerald-600 dark:text-emerald-400">Disponible</span>
                                                ) : (
                                                    <span className="text-rose-600 dark:text-rose-400">Indisponible</span>
                                                )}
                                            </td>
                                            <td className="p-2">
                                                {feature?.limit === null || feature?.limit === undefined ? 'Illimite' : feature.limit}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
