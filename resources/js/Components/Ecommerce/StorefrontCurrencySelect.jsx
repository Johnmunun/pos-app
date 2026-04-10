import { router } from '@inertiajs/react';

/**
 * Sélecteur de devise vitrine (?currency= + session serveur).
 * Affiché dès qu’au moins une devise active existe (même une seule : l’utilisateur voit la devise courante).
 */
export default function StorefrontCurrencySelect({
    availableCurrencies = [],
    value,
    variant = 'default',
}) {
    if (!Array.isArray(availableCurrencies) || availableCurrencies.length === 0) {
        return null;
    }

    const code = (value || 'USD').toString().toUpperCase();

    const handleChange = (e) => {
        const next = e.target.value;
        const qs = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
        qs.set('currency', next);
        const q = qs.toString();
        router.get(`${typeof window !== 'undefined' ? window.location.pathname : ''}${q ? `?${q}` : ''}`, {}, { preserveScroll: true });
    };

    const selectClass =
        variant === 'inverse'
            ? 'text-xs font-bold rounded-xl border border-white/40 bg-zinc-900/70 text-zinc-50 py-1.5 pl-2 pr-8 max-w-[5.5rem] [text-shadow:0_1px_2px_rgba(0,0,0,0.35)]'
            : variant === 'compact'
              ? 'rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs font-medium text-slate-900 dark:text-slate-100 max-w-[5.5rem]'
              : 'text-xs font-medium rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 py-1.5 pl-2 pr-8';

    const labelClass =
        variant === 'inverse'
            ? 'flex items-center gap-1.5 text-xs font-semibold text-zinc-100 [text-shadow:0_1px_2px_rgba(0,0,0,0.4)]'
            : 'flex items-center gap-1.5 text-xs font-semibold text-slate-800 dark:text-slate-100';

    return (
        <label className={labelClass}>
            <span className="hidden sm:inline">Devise</span>
            <select value={code} onChange={handleChange} className={selectClass} aria-label="Choisir la devise">
                {availableCurrencies.map((c) => (
                    <option key={c.code} value={c.code}>
                        {c.code}
                    </option>
                ))}
            </select>
        </label>
    );
}
