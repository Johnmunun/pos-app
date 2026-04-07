export function formatShopName(name, fallback = 'Boutique') {
    const cleaned = String(name || '')
        .replace(/\s+[—-]\s+Point de vente principal$/i, '')
        .trim();

    return cleaned || fallback;
}
