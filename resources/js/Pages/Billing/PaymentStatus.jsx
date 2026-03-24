import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

export default function PaymentStatus({ transaction }) {
    const [tx, setTx] = useState(transaction);
    const [checking, setChecking] = useState(false);
    const [message, setMessage] = useState('');

    const statusColorClass = useMemo(() => {
        const status = String(tx?.status || '').toLowerCase();

        if (status === 'paid' || status === 'success' || status === 'completed') {
            return 'text-green-600 dark:text-green-400';
        }

        if (status === 'failed' || status === 'cancelled' || status === 'rejected') {
            return 'text-red-600 dark:text-red-400';
        }

        return 'text-amber-600 dark:text-amber-400';
    }, [tx?.status]);

    const isPaid = useMemo(() => {
        const status = String(tx?.status || '').toLowerCase();
        return status === 'paid' || status === 'success' || status === 'completed';
    }, [tx?.status]);

    const verifyNow = async () => {
        setChecking(true);
        setMessage('');
        try {
            const { data } = await axios.get(route('api.billing.payments.status', tx.id));
            setTx((prev) => ({ ...prev, ...data }));

            if (data.status === 'paid') {
                setMessage('Paiement confirme. Votre abonnement est maintenant actif.');
                window.setTimeout(() => {
                    window.location.href = route('pending');
                }, 900);
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
        if (isPaid) {
            return undefined;
        }

        const intervalId = window.setInterval(async () => {
            try {
                const { data } = await axios.get(route('api.billing.payments.status', tx.id));
                setTx((prev) => ({ ...prev, ...data }));

                const nextStatus = String(data?.status || '').toLowerCase();
                if (nextStatus === 'paid' || nextStatus === 'success' || nextStatus === 'completed') {
                    setMessage('Paiement confirme automatiquement. Votre abonnement est actif.');
                    window.setTimeout(() => {
                        window.location.href = route('pending');
                    }, 900);
                }
            } catch (error) {
                // Silent retry: polling should not spam errors.
            }
        }, 8000);

        return () => window.clearInterval(intervalId);
    }, [isPaid, tx?.id]);

    return (
        <AppLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Suivi paiement</h2>}
        >
            <Head title="Suivi paiement abonnement" />

            <div className="py-8">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow p-6">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Paiement abonnement</h1>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Transaction #{tx.id} - Ref: {tx.provider_reference || 'N/A'}
                        </p>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-xs text-gray-500 dark:text-gray-400">Montant</p>
                                <p className="font-semibold text-gray-900 dark:text-white">
                                    {tx.currency_code || 'USD'} {tx.amount}
                                </p>
                            </div>
                            <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-xs text-gray-500 dark:text-gray-400">Statut</p>
                                <p className={`font-semibold ${statusColorClass}`}>{tx.status}</p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {tx.checkout_url && (
                                <a
                                    href={tx.checkout_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium"
                                >
                                    Ouvrir le checkout
                                </a>
                            )}

                            <button
                                type="button"
                                onClick={verifyNow}
                                disabled={checking}
                                className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 disabled:opacity-50"
                            >
                                {checking ? 'Verification...' : 'Verifier paiement'}
                            </button>

                            {!isPaid && (
                                <Link
                                    href={route('pending')}
                                    className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Retour pending
                                </Link>
                            )}

                            {isPaid && (
                                <Link
                                    href={route('pending')}
                                    className="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium"
                                >
                                    Continuer vers pending
                                </Link>
                            )}
                        </div>

                        {message && <p className="mt-4 text-sm text-amber-700 dark:text-amber-300">{message}</p>}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
