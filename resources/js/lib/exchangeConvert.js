import { normalizeCurrencyCode } from './currency';

/**
 * Conversion via carte de taux : units of each currency per 1 unit of la devise de référence (default tenant).
 * Même logique que CartContext (convertToCurrency).
 */
export function convertAmountToCurrency(amount, fromCurrency, toCurrency, exchangeRates = {}) {
    if (amount == null || Number.isNaN(Number(amount))) return 0;
    const fromNorm = normalizeCurrencyCode(fromCurrency);
    const toNorm = normalizeCurrencyCode(toCurrency);
    if (fromNorm === toNorm) return Number(amount);
    const fromRate = exchangeRates[fromNorm] ?? exchangeRates[fromCurrency] ?? 1;
    const toRate = exchangeRates[toNorm] ?? exchangeRates[toCurrency] ?? 1;
    if (toRate === 0) return 0;
    return (Number(amount) * toRate) / fromRate;
}
