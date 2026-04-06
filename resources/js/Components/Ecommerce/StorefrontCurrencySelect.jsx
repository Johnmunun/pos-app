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
        variant === 'compact'
            ? 'rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 px-2 py-1.5 text-xs font-medium text-slate-800 dark:text-slate-100 max-w-[5.5rem]'
            : 'text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 py-1.5 pl-2 pr-8';

    return (
        <label className="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
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
