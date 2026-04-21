import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { CheckCircle2, Clock3, RefreshCcw, ShieldAlert, WalletCards, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

const STATUSES = ['pending', 'approved', 'paid', 'rejected', 'failed', 'all'];

function statusBadge(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'paid') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    if (value === 'approved') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    if (value === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
}

function formatMoney(amount, currency) {
    const value = Number(amount || 0);
    const code = (currency || 'USD').toUpperCase();
    return `${value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${code}`;
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('fr-FR');
}

export default function BillingWithdrawals({ initialStatus = 'pending', initialData = { items: [], stats: {} } }) {
    const [status, setStatus] = useState(initialStatus);
    const [data, setData] = useState(initialData);
    const [loading, setLoading] = useState(false);
    const [actionLoading, setActionLoading] = useState(false);
    const [selectedId, setSelectedId] = useState(initialData?.items?.[0]?.id ?? null);
    const [reason, setReason] = useState('');
    const [provider, setProvider] = useState('manual');
    const [transferReference, setTransferReference] = useState('');

    const selectedItem = useMemo(
        () => (data?.items || []).find((item) => Number(item.id) === Number(selectedId)) || null,
        [data?.items, selectedId]
    );

    const fetchData = async (nextStatus = status) => {
        setLoading(true);
        try {
            const response = await axios.get(route('api.admin.merchant.withdrawals.index'), { params: { status: nextStatus } });
            setData(response.data || { items: [], stats: {} });
            setStatus(nextStatus);
            if ((response.data?.items || []).length > 0) {
                setSelectedId(response.data.items[0].id);
            } else {
                setSelectedId(null);
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || 'Impossible de charger les retraits.');
        } finally {
            setLoading(false);
        }
    };

    const performAction = async (action, payload = {}) => {
        if (!selectedItem) {
            toast.error('Selectionnez une demande.');
            return;
        }

        setActionLoading(true);
        try {
            await axios.post(route(`api.admin.merchant.withdrawals.${action}`, selectedItem.id), payload);
            toast.success('Action effectuée avec succès.');
            await fetchData(status);
            setReason('');
            if (action === 'mark-paid' || action === 'mark-failed') {
                setTransferReference('');
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || "L'action a échoué.");
        } finally {
            setActionLoading(false);
        }
    };

    const stats = data?.stats || {};

    return (
        <AppLayout fullWidth>
            <Head title="Retraits marchands" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 bg-gray-50 dark:bg-slate-900 min-h-screen">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Retraits marchands</h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Validation manuelle des demandes de retrait (Mobile Money, banque, PayPal).
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() => fetchData(status)}
                            disabled={loading}
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-700 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <RefreshCcw className="h-4 w-4" />
                            Actualiser
                        </button>
                        <Link
                            href={route('admin.billing.transactions.index')}
                            className="inline-flex items-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-700 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            Voir abonnements
                        </Link>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {STATUSES.map((item) => (
                        <button
                            key={item}
                            type="button"
                            onClick={() => fetchData(item)}
                            className={`rounded-xl border px-4 py-3 text-left transition ${
                                status === item
                                    ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950/30'
                                    : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'
                            }`}
                        >
                            <p className="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{item}</p>
                            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{stats[item] ?? 0}</p>
                        </button>
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                            <h2 className="text-base font-semibold text-slate-900 dark:text-white">Demandes ({(data?.items || []).length})</h2>
                            {loading ? <span className="text-xs text-slate-500">Chargement...</span> : null}
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[940px] text-sm">
                                <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-300">
                                    <tr>
                                        <th className="px-4 py-3 text-left">ID</th>
                                        <th className="px-4 py-3 text-left">Tenant</th>
                                        <th className="px-4 py-3 text-left">Demandeur</th>
                                        <th className="px-4 py-3 text-left">Montant</th>
                                        <th className="px-4 py-3 text-left">Destination</th>
                                        <th className="px-4 py-3 text-left">Statut</th>
                                        <th className="px-4 py-3 text-left">Créée le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(data?.items || []).map((item) => (
                                        <tr
                                            key={item.id}
                                            onClick={() => setSelectedId(item.id)}
                                            className={`cursor-pointer border-t border-slate-200 dark:border-slate-700 ${
                                                Number(selectedId) === Number(item.id) ? 'bg-indigo-50/60 dark:bg-indigo-950/20' : ''
                                            }`}
                                        >
                                            <td className="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">#{item.id}</td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">{item.tenant_name}</td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">{item.requester_name}</td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                <div>{formatMoney(item.requested_amount, item.currency_code)}</div>
                                                <div className="text-xs text-slate-500">Net: {formatMoney(item.net_amount, item.currency_code)}</div>
                                            </td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                <div className="uppercase text-xs text-slate-500">{item.destination_type}</div>
                                                <div>{item.destination_reference || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${statusBadge(item.status)}`}>
                                                    {item.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">{formatDate(item.created_at)}</td>
                                        </tr>
                                    ))}
                                    {(data?.items || []).length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                                                Aucune demande trouvée pour ce filtre.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3">
                            <h3 className="text-base font-semibold text-slate-900 dark:text-white">Détail de la demande</h3>
                            {!selectedItem ? (
                                <p className="text-sm text-slate-500">Sélectionnez une demande pour afficher le détail.</p>
                            ) : (
                                <>
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p className="text-slate-500">ID</p>
                                            <p className="font-medium text-slate-900 dark:text-slate-100">#{selectedItem.id}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500">Statut</p>
                                            <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${statusBadge(selectedItem.status)}`}>
                                                {selectedItem.status}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="text-slate-500">Montant demandé</p>
                                            <p className="font-medium text-slate-900 dark:text-slate-100">
                                                {formatMoney(selectedItem.requested_amount, selectedItem.currency_code)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500">Net à payer</p>
                                            <p className="font-medium text-slate-900 dark:text-slate-100">
                                                {formatMoney(selectedItem.net_amount, selectedItem.currency_code)}
                                            </p>
                                        </div>
                                        <div className="col-span-2">
                                            <p className="text-slate-500">Destination</p>
                                            <p className="font-medium text-slate-900 dark:text-slate-100">
                                                {selectedItem.destination_type} - {selectedItem.destination_reference || '-'}
                                            </p>
                                        </div>
                                        <div className="col-span-2">
                                            <p className="text-slate-500">Motif rejet/échec</p>
                                            <p className="font-medium text-slate-900 dark:text-slate-100">
                                                {selectedItem.rejection_reason || '-'}
                                            </p>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>

                        <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3">
                            <h3 className="text-base font-semibold text-slate-900 dark:text-white">Actions manuelles admin</h3>
                            <div className="space-y-2">
                                <label className="block text-xs text-slate-500">Provider manuel</label>
                                <input
                                    value={provider}
                                    onChange={(e) => setProvider(e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                    placeholder="manual | mobile_money | bank | paypal"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="block text-xs text-slate-500">Référence transfert</label>
                                <input
                                    value={transferReference}
                                    onChange={(e) => setTransferReference(e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                    placeholder="TXN-..."
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="block text-xs text-slate-500">Motif (rejet/échec)</label>
                                <textarea
                                    rows={3}
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                    placeholder="Préciser la raison..."
                                />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <button
                                    type="button"
                                    disabled={actionLoading || !selectedItem}
                                    onClick={() => performAction('approve')}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                                >
                                    <Clock3 className="h-4 w-4" />
                                    Approuver
                                </button>
                                <button
                                    type="button"
                                    disabled={actionLoading || !selectedItem}
                                    onClick={() => performAction('mark-paid', { provider, transfer_reference: transferReference })}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                                >
                                    <CheckCircle2 className="h-4 w-4" />
                                    Marquer payé
                                </button>
                                <button
                                    type="button"
                                    disabled={actionLoading || !selectedItem}
                                    onClick={() => performAction('reject', { reason })}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
                                >
                                    <XCircle className="h-4 w-4" />
                                    Rejeter
                                </button>
                                <button
                                    type="button"
                                    disabled={actionLoading || !selectedItem}
                                    onClick={() => performAction('mark-failed', {
                                        reason,
                                        provider,
                                        transfer_reference: transferReference,
                                    })}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                                >
                                    <ShieldAlert className="h-4 w-4" />
                                    Marquer échec
                                </button>
                            </div>
                            {actionLoading ? <p className="text-xs text-slate-500">Traitement en cours...</p> : null}
                        </div>

                        <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                            <div className="flex items-center gap-2 text-slate-900 dark:text-white font-semibold">
                                <WalletCards className="h-4 w-4" />
                                Rappel opérationnel
                            </div>
                            <ul className="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                <li>1. Vérifier le compte destinataire avant validation.</li>
                                <li>2. Approuver puis effectuer le transfert hors plateforme.</li>
                                <li>3. Marquer payé avec une référence de transfert.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
