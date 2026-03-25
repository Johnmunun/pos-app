import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { CreditCard, Building2, ArrowRight, CheckCircle2, XCircle, Clock3 } from 'lucide-react';
import { ensureFcmTokenDetailed } from '@/lib/firebaseMessaging';
import { useEffect, useMemo, useState } from 'react';

function statusBadge(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'paid' || s === 'success' || s === 'completed') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
    }
    if (s === 'failed' || s === 'rejected' || s === 'cancelled') {
        return 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300';
    }
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
}

function formatDate(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString('fr-FR');
}

export default function BillingTransactions({ transactions = [], subscriptions = [] }) {
    const [fcmDebug, setFcmDebug] = useState(null);
    const [fcmRunning, setFcmRunning] = useState(false);

    const envSummary = useMemo(() => {
        try {
            const enabled = import.meta.env.VITE_FCM_ENABLED;
            const vapid = import.meta.env.VITE_FCM_VAPID_PUBLIC_KEY;
            const projectId = import.meta.env.VITE_FCM_WEB_PROJECT_ID;
            const senderId = import.meta.env.VITE_FCM_WEB_MESSAGING_SENDER_ID;
            return {
                enabled,
                vapid_len: vapid ? String(vapid).length : 0,
                vapid_prefix: vapid ? String(vapid).slice(0, 6) : '',
                projectId: projectId || '',
                senderId: senderId || '',
            };
        } catch {
            return { enabled: 'unknown', vapid_len: 0, vapid_prefix: '', projectId: '', senderId: '' };
        }
    }, []);

    const withTimeout = async (promise, ms, label) => {
        const timeout = new Promise((_, reject) => {
            const id = setTimeout(() => {
                clearTimeout(id);
                reject(new Error(`Timeout (${label || 'operation'} > ${ms}ms)`));
            }, ms);
        });
        return Promise.race([promise, timeout]);
    };

    const activateNotifications = async ({ silent = false } = {}) => {
        try {
            setFcmRunning(true);
            setFcmDebug({
                ok: null,
                running: true,
                at: new Date().toISOString(),
                notification_permission: typeof Notification !== 'undefined' ? Notification.permission : 'n/a',
            });

            const { token, reason } = await withTimeout(ensureFcmTokenDetailed(), 15000, 'ensureFcmTokenDetailed');
            if (!token) {
                setFcmDebug({
                    ok: false,
                    reason: reason || "Impossible d'obtenir le token FCM.",
                    running: false,
                    at: new Date().toISOString(),
                    notification_permission: typeof Notification !== 'undefined' ? Notification.permission : 'n/a',
                });
                if (!silent) toast.error(reason || "Impossible d'obtenir le token FCM.");
                return;
            }

            const res = await withTimeout(
                axios.post(route('api.notifications.tokens.store'), {
                    platform: 'web',
                    token,
                }),
                15000,
                'api.notifications.tokens.store'
            );
            setFcmDebug({
                ok: true,
                token_len: String(token).length,
                token_prefix: String(token).slice(0, 12),
                running: false,
                at: new Date().toISOString(),
                notification_permission: typeof Notification !== 'undefined' ? Notification.permission : 'n/a',
                api_response: res?.data || null,
            });
            if (!silent) toast.success('Notifications activées (token enregistré).');
        } catch (e) {
            const message =
                e?.response?.data?.message ||
                e?.message ||
                "Erreur d'activation des notifications. Vérifie les permissions du navigateur.";
            setFcmDebug({
                ok: false,
                reason: message,
                running: false,
                at: new Date().toISOString(),
                notification_permission: typeof Notification !== 'undefined' ? Notification.permission : 'n/a',
                api_status: e?.response?.status,
                api_data: e?.response?.data,
            });
            if (!silent) toast.error(message);
        } finally {
            setFcmRunning(false);
        }
    };

    useEffect(() => {
        // Auto-diagnostic on first load (silent)
        activateNotifications({ silent: true });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const sendTest = async () => {
        try {
            await axios.post(route('api.notifications.test'), {
                title: 'Test notification',
                body: 'Hello FCM (test depuis OmniPOS)',
            });
            toast.success('Notification envoyée. Vérifie ton navigateur.');
        } catch (e) {
            toast.error(e?.response?.data?.message || 'Erreur envoi notification.');
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Billing - Transactions & Abonnements
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Suivi des paiements et des dates d’expiration par boutique.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={activateNotifications}
                            disabled={fcmRunning}
                            className="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            {fcmRunning ? 'Activation...' : 'Activer notifications'}
                        </button>
                        <button
                            type="button"
                            onClick={sendTest}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Tester notification
                        </button>
                        <Link
                            href={route('admin.billing.plans.index')}
                            className="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <ArrowRight className="h-4 w-4" />
                            Plans
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Admin - Transactions Billing" />

            <div className="py-6 space-y-6">
                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4">
                        <div className="min-w-0">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">Debug FCM</h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Si ça bloque, copie ce bloc et envoie-le moi.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={activateNotifications}
                            className="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Activer notifications
                        </button>
                    </div>
                    <div className="p-6">
                        <pre className="text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-50 dark:bg-gray-900/30 border border-gray-200 dark:border-gray-700 p-4 text-gray-800 dark:text-gray-200">
                            {JSON.stringify(
                                {
                                    env: envSummary,
                                    notification_permission:
                                        typeof Notification !== 'undefined' ? Notification.permission : 'n/a',
                                    debug: fcmDebug,
                                },
                                null,
                                2
                            )}
                        </pre>
                    </div>
                </div>

                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <Building2 className="h-5 w-5 text-amber-600 dark:text-amber-300" />
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">Abonnements actifs</h3>
                    </div>
                    <div className="p-6 overflow-x-auto">
                        {subscriptions.length === 0 ? (
                            <p className="text-sm text-gray-600 dark:text-gray-400">Aucun abonnement actif.</p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead className="bg-gray-50 dark:bg-gray-900/30">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Boutique</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Début</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Expiration</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {subscriptions.map((s) => (
                                        <tr key={s.id}>
                                            <td className="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {s.tenant_name} (#{s.tenant_id})
                                            </td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {s.plan_name} <span className="text-xs text-gray-500">({s.plan_code})</span>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {formatDate(s.starts_at)}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {formatDate(s.ends_at || s.trial_ends_at)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <CreditCard className="h-5 w-5 text-amber-600 dark:text-amber-300" />
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">Dernières transactions</h3>
                    </div>
                    <div className="p-6 overflow-x-auto">
                        {transactions.length === 0 ? (
                            <p className="text-sm text-gray-600 dark:text-gray-400">Aucune transaction.</p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead className="bg-gray-50 dark:bg-gray-900/30">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Boutique</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Utilisateur</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Payé le</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Créé le</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {transactions.map((t) => (
                                        <tr key={t.id}>
                                            <td className="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                #{t.id}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {t.tenant_name || '-'}{t.tenant_id ? ` (#${t.tenant_id})` : ''}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {t.user_name || '-'}
                                                {t.user_email ? <div className="text-xs text-gray-500">{t.user_email}</div> : null}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {t.plan_name || '-'} {t.plan_code ? <span className="text-xs text-gray-500">({t.plan_code})</span> : null}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200 font-semibold">
                                                {t.currency_code || ''} {t.amount ?? ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${statusBadge(t.status)}`}>
                                                    {String(t.status || 'pending')}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {formatDate(t.paid_at)}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {formatDate(t.created_at)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}

                        <div className="mt-4 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-3">
                            <span className="inline-flex items-center gap-1"><CheckCircle2 className="h-4 w-4 text-emerald-500" /> paid</span>
                            <span className="inline-flex items-center gap-1"><XCircle className="h-4 w-4 text-rose-500" /> failed</span>
                            <span className="inline-flex items-center gap-1"><Clock3 className="h-4 w-4 text-amber-500" /> pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

