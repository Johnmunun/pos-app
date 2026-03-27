import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { useCallback, useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { CheckCircle2, Clock3, ExternalLink, ShieldCheck } from 'lucide-react';

function isPaidStatus(status) {
    const s = String(status || '').toLowerCase();
    return s === 'paid' || s === 'success' || s === 'completed';
}

export default function PaymentStatus({ transaction }) {
    const [tx, setTx] = useState(transaction);
    const [checking, setChecking] = useState(false);
    const [message, setMessage] = useState('');

    const statusColorClass = useMemo(() => {
        const status = String(tx?.status || '').toLowerCase();

        if (isPaidStatus(status)) {
            return 'text-green-600 dark:text-green-400';
        }

        if (status === 'failed' || status === 'cancelled' || status === 'rejected') {
            return 'text-red-600 dark:text-red-400';
        }

        return 'text-amber-600 dark:text-amber-400';
    }, [tx?.status]);

    const isPaid = useMemo(() => isPaidStatus(tx?.status), [tx?.status]);

    const hasEcommerceOrder = Boolean(tx?.has_ecommerce_order);

    const redirectAfterPaid = useCallback((data) => {
        if (data.ecommerce_success_url) {
            setMessage('Paiement confirmé. Redirection vers la page de téléchargement...');
            window.setTimeout(() => {
                window.location.href = data.ecommerce_success_url;
            }, 900);
            return;
        }
        setMessage('Paiement confirmé. Redirection vers votre tableau de bord...');
        window.setTimeout(() => {
            window.location.href = route('dashboard', { billing_success: data.id });
        }, 900);
    }, []);

    const verifyNow = async () => {
        setChecking(true);
        setMessage('');
        try {
            const { data } = await axios.get(route('api.billing.payments.status', tx.id));
            setTx((prev) => ({ ...prev, ...data }));

            if (isPaidStatus(data.status)) {
                redirectAfterPaid(data);
                return;
            }

            setMessage(`Statut actuel: ${data.status}`);
        } catch (error) {
            setMessage('Erreur pendant la verification du paiement.');
        } finally {
            setChecking(false);
        }
    };

    useEffect(() => {
        const currentStatus = String(tx?.status || '').toLowerCase();
        if (isPaidStatus(currentStatus)) {
            return undefined;
        }

        const intervalId = window.setInterval(async () => {
            try {
                const { data } = await axios.get(route('api.billing.payments.status', tx.id));
                setTx((prev) => ({ ...prev, ...data }));

                const nextStatus = String(data?.status || '').toLowerCase();
                if (isPaidStatus(nextStatus)) {
                    setMessage(
                        data.ecommerce_success_url
                            ? 'Paiement confirmé automatiquement. Redirection vers vos téléchargements...'
                            : 'Paiement confirmé automatiquement. Redirection...'
                    );
                    redirectAfterPaid(data);
                }
            } catch (error) {
                // Silent retry: polling should not spam errors.
            }
        }, 8000);

        return () => window.clearInterval(intervalId);
    }, [isPaid, tx?.id, redirectAfterPaid]);

    const afterSuccessCopy = hasEcommerceOrder
        ? 'Après succès, vous serez redirigé vers une page avec vos liens de téléchargement (produits numériques).'
        : 'Tu seras redirige vers le dashboard et tu recevras un email de confirmation.';

    const emailCopy = hasEcommerceOrder
        ? 'Après paiement confirmé, vous pouvez aussi recevoir un e-mail récapitulatif selon la configuration du magasin.'
        : "Apres paiement confirme, un email est envoye sur ta boite mail pour confirmer l'operation.";

    return (
        <AppLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Suivi paiement</h2>}
        >
            <Head title={hasEcommerceOrder ? 'Suivi paiement commande' : 'Suivi paiement abonnement'} />

            <div className="py-8">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl shadow p-6 sm:p-8">
                        <div className="absolute inset-0 bg-gradient-to-br from-amber-500/10 via-emerald-500/10 to-indigo-500/10" />
                        <div className="relative">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                        Finalisation du paiement
                                    </h1>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Si le paiement est validé sur votre téléphone, clique sur “Vérifier paiement”.
                                    </p>
                                </div>
                                <div className="shrink-0 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/30 px-3 py-2">
                                    <p className="text-[11px] text-gray-500 dark:text-gray-400">Transaction</p>
                                    <p className="text-sm font-semibold text-gray-900 dark:text-white">#{tx.id}</p>
                                </div>
                            </div>

                            <div className="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/30 p-4">
                                    <div className="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                        <ShieldCheck className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-sm font-semibold">Statut</span>
                                    </div>
                                    <p className={`mt-2 text-sm font-bold ${statusColorClass}`}>{tx.status}</p>
                                </div>
                                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/30 p-4">
                                    <div className="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                        <Clock3 className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                        <span className="text-sm font-semibold">Conseil</span>
                                    </div>
                                    <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                        Attends 5–20 secondes puis vérifie.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/30 p-4">
                                    <div className="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                        <CheckCircle2 className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-sm font-semibold">Après succès</span>
                                    </div>
                                    <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">{afterSuccessCopy}</p>
                                </div>
                            </div>

                            <div className="mt-4 rounded-2xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                                <p className="text-sm text-blue-800 dark:text-blue-200">{emailCopy}</p>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                {tx.checkout_url && (
                                    <a
                                        href={tx.checkout_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 h-11 px-4 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold shadow-sm"
                                    >
                                        <ExternalLink className="h-4 w-4" />
                                        Ouvrir FusionPay
                                    </a>
                                )}

                                <button
                                    type="button"
                                    onClick={verifyNow}
                                    disabled={checking}
                                    className="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm disabled:opacity-60"
                                >
                                    {checking ? 'Vérification...' : 'Vérifier paiement'}
                                </button>

                                <Link
                                    href={route('pending')}
                                    className="h-11 px-4 rounded-2xl border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 inline-flex items-center"
                                >
                                    Retour
                                </Link>
                            </div>

                            {message && (
                                <div className="mt-4 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                                    <p className="text-sm text-amber-800 dark:text-amber-200">{message}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
