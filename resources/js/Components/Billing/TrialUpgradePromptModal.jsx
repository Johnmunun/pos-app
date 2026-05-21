import { useEffect, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { Sparkles, Rocket, CheckCircle2, Crown } from 'lucide-react';

const ENTRY_CODES = ['trial', 'starter'];
const NAME_HINTS = ['trial', 'starter', 'essai', 'découverte', 'decouverte', 'gratuit', 'free'];

/** @param {unknown[]} arr @param {number} count */
function pickRandomUnique(arr, count) {
    const pool = [...arr];
    const n = Math.min(count, pool.length);
    const out = [];
    for (let i = 0; i < n; i++) {
        const idx = Math.floor(Math.random() * pool.length);
        out.push(pool.splice(idx, 1)[0]);
    }
    return out;
}

function pickOne(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

const HEADLINES = [
    'Passez à la vitesse supérieure',
    'Faites grandir votre activité sans plafonds frustrants',
    'Le plan supérieur : plus de marge pour vendre sereinement',
    'Débloquez le plein potentiel d’OmniPOS',
    'Votre commerce mérite plus que les limites du plan actuel',
    'Moins de contraintes, plus de temps pour vos clients',
    'Passez au niveau au-dessus : stock, ventes, équipe',
];

const SUBTITLES = [
    'Ventes, stocks multi-dépôts, rapports avancés et accompagnement : tout s’aligne quand le plan suit votre rythme.',
    'Levez les plafonds produits, catégories et ventes pour respirer sur la gestion quotidienne.',
    'Un plan supérieur, c’est surtout la tranquillité quand le trafic augmente et que les données comptent.',
    'Centralisez mieux, automatisez plus, et gardez une vision claire sur votre performance.',
    'Les équipes qui montent en gamme passent souvent au plan au-dessus avant d’être bloquées.',
];

const BENEFIT_LINES = [
    'Plafonds produits, catégories et ventes relevés (ou supprimés selon le plan).',
    'Multi-dépôts et transferts pour une logistique qui tient la route.',
    'Rapports et indicateurs plus poussés pour décider vite.',
    'Support et priorités adaptés quand vous avez besoin d’aide.',
    'Fonctions avancées e-commerce, pharmacie, commerce ou quincaillerie selon vos modules.',
    'Moins d’allers-retours manuels : plus d’automatisation au quotidien.',
    'Meilleure visibilité sur marges, stocks et équipe sur une seule base.',
    'Préparez la saison forte sans craindre d’atteindre une limite au mauvais moment.',
    'Accès aux évolutions produit en priorité sur les plans supérieurs.',
    'Une image plus pro pour vos clients et partenaires.',
];

const FOOTER_LINES = [
    'Chaque connexion ne montre cette invitation qu’une fois — profitez-en pour comparer les plans.',
    'Vous pourrez revenir sur la facturation à tout moment depuis le menu.',
    'Les plans supérieurs sont pensés pour les boutiques qui accélèrent.',
];

function isEntryBillingPlan(billingSummary) {
    const code = String(billingSummary?.plan_code ?? '').toLowerCase().trim();
    if (code && ENTRY_CODES.includes(code)) return true;
    const name = String(billingSummary?.plan_name ?? '').toLowerCase();
    return NAME_HINTS.some((h) => h && name.includes(h));
}

function planModeLabel(billingSummary) {
    const code = String(billingSummary?.plan_code ?? '').toLowerCase().trim();
    if (code === 'starter') return 'Starter';
    if (code === 'trial') return 'Trial';
    const name = String(billingSummary?.plan_name ?? '').toLowerCase();
    if (name.includes('starter')) return 'Starter';
    if (name.includes('trial') || name.includes('essai') || name.includes('gratuit')) return 'Trial';
    return 'Plan actuel';
}

export default function TrialUpgradePromptModal() {
    const { auth, flash } = usePage().props;
    const user = auth?.user ?? null;
    const billingSummary = auth?.billingSummary ?? null;

    const [open, setOpen] = useState(false);

    const isEntryPlan = useMemo(() => isEntryBillingPlan(billingSummary), [billingSummary]);

    const copyBundle = useMemo(() => {
        if (!flash?.trial_upgrade_prompt || !isEntryPlan) return null;
        return {
            headline: pickOne(HEADLINES),
            subtitle: pickOne(SUBTITLES),
            benefits: pickRandomUnique(BENEFIT_LINES, 4),
            footer: pickOne(FOOTER_LINES),
        };
    }, [flash?.trial_upgrade_prompt, isEntryPlan]);

    useEffect(() => {
        const shouldShow = Boolean(flash?.trial_upgrade_prompt);
        if (!user || !isEntryPlan || !shouldShow) return undefined;

        const timer = window.setTimeout(() => {
            setOpen(true);
        }, 650);

        return () => window.clearTimeout(timer);
    }, [user, isEntryPlan, flash?.trial_upgrade_prompt]);

    useEffect(() => {
        if (!flash?.trial_upgrade_prompt) {
            setOpen(false);
        }
    }, [flash?.trial_upgrade_prompt]);

    if (!user || !isEntryPlan) return null;

    const rows = [
        { label: 'Produits', used: billingSummary?.products_used, limit: billingSummary?.products_limit },
        { label: 'Catégories', used: billingSummary?.categories_used, limit: billingSummary?.categories_limit },
        { label: 'Ventes (mois)', used: billingSummary?.sales_used, limit: billingSummary?.sales_limit },
    ];

    const modeLabel = planModeLabel(billingSummary);
    const dismissLabel =
        String(billingSummary?.plan_code ?? '').toLowerCase() === 'starter' || modeLabel === 'Starter'
            ? 'Continuer avec Starter'
            : 'Continuer avec Trial';

    return (
        <Modal show={open} onClose={() => setOpen(false)} maxWidth="lg">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-500 opacity-95" />
                <div className="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/15 blur-2xl" />
                <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-amber-300/25 blur-2xl" />

                <div className="relative p-6 sm:p-7 text-white">
                    <div className="flex flex-wrap items-center gap-2 text-violet-100">
                        <Sparkles className="h-5 w-5 shrink-0" />
                        <span className="text-sm font-semibold tracking-wide uppercase">
                            Plan {modeLabel} — passez au niveau supérieur
                        </span>
                    </div>

                    <div className="mt-3 flex items-start gap-3">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25">
                            <Crown className="h-6 w-6 text-amber-200" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <h3 className="text-2xl sm:text-3xl font-extrabold leading-tight">
                                {copyBundle?.headline ?? 'Passez à la vitesse supérieure'}
                            </h3>
                            <p className="mt-2 text-sm sm:text-base text-violet-100 leading-relaxed">
                                {copyBundle?.subtitle ??
                                    'Passez au plan payant pour lever les limites et accélérer votre croissance.'}
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {rows.map((row) => (
                            <div
                                key={row.label}
                                className="rounded-xl bg-white/15 border border-white/20 px-3 py-2 backdrop-blur-sm"
                            >
                                <p className="text-xs text-violet-100">{row.label}</p>
                                <p className="text-lg font-bold">
                                    {Number.isFinite(row.used) ? row.used : 0}
                                    <span className="text-white/70 font-semibold"> / </span>
                                    {Number.isFinite(row.limit) ? row.limit : '∞'}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-5 space-y-2 text-sm text-violet-50">
                        {(copyBundle?.benefits ?? [
                            'Plus de limites sur vos opérations',
                            'Fonctionnalités avancées pour vendre plus',
                            'Meilleure visibilité sur vos stocks',
                            'Support adapté à votre croissance',
                        ]).map((line) => (
                            <p key={line} className="flex items-start gap-2">
                                <CheckCircle2 className="h-4 w-4 shrink-0 mt-0.5 text-emerald-200" />
                                <span>{line}</span>
                            </p>
                        ))}
                    </div>

                    {copyBundle?.footer && (
                        <p className="mt-4 text-xs text-violet-200/90 leading-relaxed border-t border-white/10 pt-4">
                            {copyBundle.footer}
                        </p>
                    )}

                    <div className="mt-6 flex flex-col sm:flex-row gap-3">
                        <Link
                            href="/onboarding/payment"
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-white text-indigo-700 px-5 py-2.5 font-bold hover:bg-indigo-50 transition-all duration-300 hover:scale-[1.02]"
                        >
                            <Rocket className="h-4 w-4 shrink-0" />
                            Voir les plans supérieurs
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="inline-flex items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-2.5 font-semibold hover:bg-white/20 transition-all duration-300"
                        >
                            {dismissLabel}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
