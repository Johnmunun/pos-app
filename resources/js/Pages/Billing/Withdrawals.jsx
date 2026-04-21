import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { RefreshCcw, Send } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function statusBadge(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'paid') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    if (s === 'approved') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    if (s === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
}

function formatMoney(amount, currency) {
    const value = Number(amount || 0);
    const code = (currency || 'USD').toUpperCase();
    return `${value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${code}`;
}

function formatDate(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString('fr-FR');
}

export default function Withdrawals() {
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [balances, setBalances] = useState([]);
    const [withdrawals, setWithdrawals] = useState([]);
    const [form, setForm] = useState({
        currency_code: 'USD',
        requested_amount: '',
        destination_type: 'mobile_money',
        destination_reference: '',
    });

    const loadData = async () => {
        setLoading(true);
        try {
            const { data } = await axios.get(route('api.merchant.withdrawals.index'));
            const listBalances = Array.isArray(data?.balances) ? data.balances : [];
            setBalances(listBalances);
            setWithdrawals(Array.isArray(data?.withdrawals) ? data.withdrawals : []);
            if (listBalances.length > 0 && !listBalances.find((b) => b.currency_code === form.currency_code)) {
                setForm((prev) => ({ ...prev, currency_code: String(listBalances[0].currency_code || 'USD').toUpperCase() }));
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || 'Impossible de charger les retraits.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const selectedBalance = useMemo(
        () => balances.find((item) => String(item.currency_code).toUpperCase() === String(form.currency_code).toUpperCase()) || null,
        [balances, form.currency_code]
    );
    const requestedAmount = Number(form.requested_amount || 0);
    const availableAmount = Number(selectedBalance?.available_balance || 0);
    const isAmountInvalid = requestedAmount > 0 && selectedBalance !== null && requestedAmount > availableAmount;

    const submit = async (e) => {
        e.preventDefault();
        if (requestedAmount <= 0) {
            toast.error('Le montant doit etre superieur a 0.');
            return;
        }
        if (isAmountInvalid) {
            toast.error('Le montant depasse le solde disponible.');
            return;
        }
        if (!String(form.destination_reference || '').trim()) {
            toast.error('La reference de destination est obligatoire.');
            return;
        }

        setSubmitting(true);
        try {
            await axios.post(route('api.merchant.withdrawals.store'), {
                currency_code: String(form.currency_code || 'USD').toUpperCase(),
                requested_amount: Number(form.requested_amount || 0),
                destination_type: form.destination_type,
                destination_reference: form.destination_reference,
            });
            toast.success('Demande envoyée avec succès.');
            setForm((prev) => ({ ...prev, requested_amount: '', destination_reference: '' }));
            await loadData();
        } catch (error) {
            toast.error(error?.response?.data?.message || "Impossible d'envoyer la demande.");
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout fullWidth>
            <Head title="Mes retraits" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 bg-gray-50 dark:bg-slate-900 min-h-screen">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Mes retraits</h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Soumettez une demande de retrait. Le paiement est validé et exécuté manuellement par l&apos;administration.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={loadData}
                        disabled={loading}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-700 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700"
                    >
                        <RefreshCcw className="h-4 w-4" />
                        Actualiser
                    </button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
                    <div className="space-y-4">
                        <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                            <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-3">Solde portefeuille</h2>
                            <div className="space-y-2">
                                {balances.length === 0 ? (
                                    <p className="text-sm text-slate-500">Aucun solde disponible.</p>
                                ) : (
                                    balances.map((item) => (
                                        <div key={item.currency_code} className="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium text-slate-900 dark:text-slate-100">{item.currency_code}</span>
                                                <span className="text-sm text-slate-600 dark:text-slate-300">
                                                    Disponible: {formatMoney(item.available_balance, item.currency_code)}
                                                </span>
                                            </div>
                                            <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Bloqué: {formatMoney(item.locked_balance, item.currency_code)}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                        <form onSubmit={submit} className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3">
                            <h2 className="text-base font-semibold text-slate-900 dark:text-white">Nouvelle demande</h2>
                            <div>
                                <label className="mb-1 block text-xs text-slate-500">Devise</label>
                                <select
                                    value={form.currency_code}
                                    onChange={(e) => setForm((prev) => ({ ...prev, currency_code: e.target.value }))}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                >
                                    {(balances.length ? balances : [{ currency_code: 'USD' }]).map((b) => (
                                        <option key={b.currency_code} value={b.currency_code}>{b.currency_code}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs text-slate-500">Montant</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.requested_amount}
                                    onChange={(e) => setForm((prev) => ({ ...prev, requested_amount: e.target.value }))}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                    placeholder="0.00"
                                />
                                {selectedBalance ? (
                                    <p className="mt-1 text-xs text-slate-500">
                                        Disponible: {formatMoney(selectedBalance.available_balance, selectedBalance.currency_code)}
                                    </p>
                                ) : null}
                                {isAmountInvalid ? (
                                    <p className="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        Le montant saisi depasse votre solde disponible.
                                    </p>
                                ) : null}
                            </div>
                            <div>
                                <label className="mb-1 block text-xs text-slate-500">Méthode</label>
                                <select
                                    value={form.destination_type}
                                    onChange={(e) => setForm((prev) => ({ ...prev, destination_type: e.target.value }))}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                >
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank">Compte bancaire</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="wallet">Wallet</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs text-slate-500">Référence destination</label>
                                <input
                                    value={form.destination_reference}
                                    onChange={(e) => setForm((prev) => ({ ...prev, destination_reference: e.target.value }))}
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"
                                    placeholder="Nom + numéro Mobile Money / IBAN / PayPal"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={submitting || isAmountInvalid}
                                className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60"
                            >
                                <Send className="h-4 w-4" />
                                {submitting ? 'Envoi...' : 'Envoyer la demande'}
                            </button>
                        </form>
                    </div>

                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                        <div className="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                            <h2 className="text-base font-semibold text-slate-900 dark:text-white">Historique des demandes</h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[820px] text-sm">
                                <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-300">
                                    <tr>
                                        <th className="px-4 py-3 text-left">Date</th>
                                        <th className="px-4 py-3 text-left">Montant</th>
                                        <th className="px-4 py-3 text-left">Net</th>
                                        <th className="px-4 py-3 text-left">Destination</th>
                                        <th className="px-4 py-3 text-left">Statut</th>
                                        <th className="px-4 py-3 text-left">Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {withdrawals.map((item) => (
                                        <tr key={item.id} className="border-t border-slate-200 dark:border-slate-700">
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">{formatDate(item.created_at)}</td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                {formatMoney(item.requested_amount, item.currency_code)}
                                            </td>
                                            <td className="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                {formatMoney(item.net_amount, item.currency_code)}
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
                                            <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{item.rejection_reason || '-'}</td>
                                        </tr>
                                    ))}
                                    {withdrawals.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-8 text-center text-slate-500">
                                                Aucune demande pour le moment.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
