import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { CheckCircle2, Sparkles } from 'lucide-react';

function formatDateFr(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
}

export default function BillingPaymentSuccessModal() {
    const { auth } = usePage().props;
    const billingSummary = auth?.billingSummary ?? null;

    const txId = useMemo(() => {
        if (typeof window === 'undefined') return null;
        const params = new URLSearchParams(window.location.search);
        const raw = params.get('billing_success');
        return raw ? String(raw) : null;
    }, []);

    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!txId) return;
        const key = `billing_success_shown_${txId}`;
        if (window.sessionStorage.getItem(key) === '1') return;
        window.sessionStorage.setItem(key, '1');
        setOpen(true);

        const url = new URL(window.location.href);
        url.searchParams.delete('billing_success');
        window.history.replaceState({}, '', url.toString());
    }, [txId]);

    if (!txId) return null;

    return (
        <Modal show={open} onClose={() => setOpen(false)} maxWidth="lg">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-amber-500/10 to-indigo-500/10" />
                <div className="absolute -top-24 -right-24 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl animate-pulse" />
                <div className="absolute -bottom-24 -left-24 h-56 w-56 rounded-full bg-amber-400/20 blur-3xl animate-pulse" />

                <div className="relative p-6 sm:p-7">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <span className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/35 ring-1 ring-emerald-200/70 dark:ring-emerald-900/40">
                                <CheckCircle2 className="h-7 w-7 text-emerald-600 dark:text-emerald-400" />
                            </span>
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <h2 className="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                        Paiement réussi
                                    </h2>
                                    <Sparkles className="h-5 w-5 text-amber-500" />
                                </div>
                                <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">
                                    Merci. Ton abonnement est maintenant actif.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Fermer
                        </button>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div className="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/70 dark:bg-slate-950/20 p-4">
                            <div className="text-xs font-semibold text-slate-500 dark:text-slate-400">Plan</div>
                            <div className="mt-1 font-bold text-slate-900 dark:text-white">
                                {billingSummary?.plan_name ?? '—'}
                            </div>
                        </div>
                        <div className="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/70 dark:bg-slate-950/20 p-4">
                            <div className="text-xs font-semibold text-slate-500 dark:text-slate-400">Expiration</div>
                            <div className="mt-1 font-bold text-slate-900 dark:text-white">
                                {formatDateFr(billingSummary?.expires_at)}
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            Réf. transaction: #{txId}
                        </p>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm"
                        >
                            Continuer
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}

