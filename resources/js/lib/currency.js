/**
 * Currency utilities for the POS application.
 * Centralized currency formatting to ensure consistency across all pages.
 */

/**
 * Normalise un code devise vers un code ISO 4217 valide.
 * FCFA n'est pas un code ISO - on le mappe vers XAF (Franc CFA d'Afrique centrale).
 */
export function normalizeCurrencyCode(currencyCode) {
    if (!currencyCode || typeof currencyCode !== 'string') return 'CDF';
    const code = currencyCode.toUpperCase().trim();
    // FCFA -> XAF (Intl.NumberFormat attend un code ISO, XAF affiche "FCFA" en fr-FR)
    if (code === 'FCFA' || code === 'FRANC CFA') return 'XAF';
    if (code === 'XOF' || code === 'XAF') return code;
    return code || 'CDF';
}

/**
 * Format an amount with the specified currency.
 * 
 * @param {number} amount - The amount to format
 * @param {string} currencyCode - The ISO 4217 currency code (e.g., 'CDF', 'USD', 'EUR', 'FCFA' -> XAF)
 * @param {string} locale - The locale for formatting (default: 'fr-FR')
 * @returns {string} The formatted currency string
 */
export function formatCurrency(amount, currencyCode = 'CDF', locale = 'fr-FR') {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return formatCurrency(0, currencyCode, locale);
    }

    const isoCode = normalizeCurrencyCode(currencyCode);

    try {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: isoCode,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    } catch (error) {
        // Fallback : symbole personnalisé pour codes non supportés
        const symbol = getCurrencySymbol(isoCode);
        return `${formatNumber(amount, locale)} ${symbol}`;
    }
}

/**
 * Format a number with locale-specific grouping.
 * 
 * @param {number} amount - The amount to format
 * @param {string} locale - The locale for formatting (default: 'fr-FR')
 * @returns {string} The formatted number string
 */
export function formatNumber(amount, locale = 'fr-FR') {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
}

/**
 * Get the currency symbol for a given currency code.
 * 
 * @param {string} currencyCode - The ISO 4217 currency code
 * @returns {string} The currency symbol
 */
export function getCurrencySymbol(currencyCode = 'CDF') {
    const symbols = {
        'CDF': 'FC',
        'USD': '$',
        'EUR': '€',
        'XAF': 'FCFA',
        'XOF': 'FCFA',
        'GBP': '£',
        'NGN': '₦',
        'KES': 'KSh',
        'ZAR': 'R',
        'GHS': 'GH₵',
        'TZS': 'TSh',
        'UGX': 'USh',
        'RWF': 'FRw',
        'BIF': 'FBu',
        'AOA': 'Kz',
        'ZMW': 'ZK',
    };

    return symbols[currencyCode] || currencyCode;
}

/**
 * Create a currency formatter function bound to a specific currency.
 * Useful for components that need to format multiple amounts with the same currency.
 * 
 * @param {string} currencyCode - The ISO 4217 currency code
 * @param {string} locale - The locale for formatting (default: 'fr-FR')
 * @returns {function} A function that formats amounts with the bound currency
 */
export function createCurrencyFormatter(currencyCode = 'CDF', locale = 'fr-FR') {
    return (amount) => formatCurrency(amount, currencyCode, locale);
}

/**
 * Hook-friendly currency formatter that returns a formatter function.
 * Use this in React components: const format = useCurrencyFormatter(shop?.currency);
 * 
 * @param {string} currencyCode - The ISO 4217 currency code from shop config
 * @returns {function} A memoizable formatter function
 */
export function useCurrencyFormatter(currencyCode) {
    const code = currencyCode || 'CDF';
    return (amount) => formatCurrency(amount, code);
}

/**
 * Parse a currency string back to a number.
 * 
 * @param {string} value - The formatted currency string
 * @returns {number} The parsed numeric value
 */
export function parseCurrency(value) {
    if (typeof value === 'number') return value;
    if (!value) return 0;
    
    // Remove currency symbols and spaces, handle French number format
    const cleaned = String(value)
        .replace(/[^\d,.-]/g, '')
        .replace(/\s/g, '')
        .replace(',', '.');
    
    return parseFloat(cleaned) || 0;
}

export default {
    formatCurrency,
    formatNumber,
    getCurrencySymbol,
    normalizeCurrencyCode,
    createCurrencyFormatter,
    useCurrencyFormatter,
    parseCurrency,
};
