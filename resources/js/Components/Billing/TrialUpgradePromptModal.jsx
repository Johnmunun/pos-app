import { useEffect, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { Sparkles, Rocket, CheckCircle2 } from 'lucide-react';

export default function TrialUpgradePromptModal() {
    const { auth, flash } = usePage().props;
    const user = auth?.user ?? null;
    const billingSummary = auth?.billingSummary ?? null;

    const [open, setOpen] = useState(false);

    const isTrialPlan = useMemo(() => {
        const name = String(billingSummary?.plan_name ?? '').toLowerCase();
        return name.includes('trial');
    }, [billingSummary?.plan_name]);

    useEffect(() => {
        const shouldShow = Boolean(flash?.trial_upgrade_prompt);
        if (!user || !isTrialPlan || !shouldShow) return;

        const timer = window.setTimeout(() => {
            setOpen(true);
        }, 650);

        return () => window.clearTimeout(timer);
    }, [user, isTrialPlan, flash?.trial_upgrade_prompt]);

    if (!user || !isTrialPlan) return null;

    const rows = [
        { label: 'Produits', used: billingSummary?.products_used, limit: billingSummary?.products_limit },
        { label: 'Catégories', used: billingSummary?.categories_used, limit: billingSummary?.categories_limit },
        { label: 'Ventes', used: billingSummary?.sales_used, limit: billingSummary?.sales_limit },
    ];

    return (
        <Modal show={open} onClose={() => setOpen(false)} maxWidth="lg">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-500 opacity-95" />
                <div className="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/15 blur-2xl" />
                <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-amber-300/25 blur-2xl" />

                <div className="relative p-6 sm:p-7 text-white">
                    <div className="flex items-center gap-2 text-violet-100">
                        <Sparkles className="h-5 w-5" />
                        <span className="text-sm font-semibold tracking-wide uppercase">Mode Trial actif</span>
                    </div>

                    <h3 className="mt-3 text-2xl sm:text-3xl font-extrabold leading-tight">
                        Débloquez tout le potentiel de votre business
                    </h3>

                    <p className="mt-2 text-violet-100">
                        Vous utilisez déjà OmniPOS. Passez au plan payant pour lever les limites et accélérer votre croissance.
                    </p>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {rows.map((row) => (
                            <div key={row.label} className="rounded-xl bg-white/15 border border-white/20 px-3 py-2 backdrop-blur-sm">
                                <p className="text-xs text-violet-100">{row.label}</p>
                                <p className="text-lg font-bold">
                                    {Number.isFinite(row.used) ? row.used : 0}
                                    /
                                    {Number.isFinite(row.limit) ? row.limit : '∞'}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-5 space-y-2 text-sm text-violet-50">
                        <p className="flex items-center gap-2"><CheckCircle2 className="h-4 w-4" /> Plus de limites sur vos opérations</p>
                        <p className="flex items-center gap-2"><CheckCircle2 className="h-4 w-4" /> Fonctionnalités avancées pour vendre plus</p>
                    </div>

                    <div className="mt-6 flex flex-col sm:flex-row gap-3">
                        <Link
                            href="/onboarding/payment"
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-white text-indigo-700 px-5 py-2.5 font-bold hover:bg-indigo-50 transition-all duration-300 hover:scale-[1.02]"
                        >
                            <Rocket className="h-4 w-4" />
                            Upgrade maintenant
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="inline-flex items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-2.5 font-semibold hover:bg-white/20 transition-all duration-300"
                        >
                            Continuer en Trial
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
