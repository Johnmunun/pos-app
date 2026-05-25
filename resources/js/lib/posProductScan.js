/**
 * Résolution produit par scan (code-barres, QR payload, code interne / SKU).
 * Utilisé par les pages POS Commerce, Hardware et Pharmacy.
 */

export function normalizeScanCode(raw) {
    return String(raw ?? '')
        .trim()
        .replace(/\s+/g, '');
}

/**
 * @param {Array<{ id: string|number, barcode?: string|null, code?: string|null, sku?: string|null }>} products
 */
export function buildProductLookupIndex(products) {
    const byBarcode = new Map();
    const byCode = new Map();
    const barcodeDuplicateKeys = new Set();
    const codeDuplicateKeys = new Set();

    for (const product of products) {
        const barcode = product.barcode;
        if (barcode) {
            const key = normalizeScanCode(barcode).toLowerCase();
            if (!key) continue;
            if (byBarcode.has(key)) {
                barcodeDuplicateKeys.add(key);
            } else {
                byBarcode.set(key, product);
            }
        }

        const code = product.code ?? product.sku ?? '';
        if (code) {
            const key = normalizeScanCode(code).toLowerCase();
            if (!key) continue;
            if (byCode.has(key)) {
                codeDuplicateKeys.add(key);
            } else {
                byCode.set(key, product);
            }
        }
    }

    return {
        byBarcode,
        byCode,
        barcodeDuplicateKeys,
        codeDuplicateKeys,
        all: products,
    };
}

/**
 * @returns {{ status: 'empty' }|{ status: 'not_found', code: string }|{ status: 'found', product: object, field: 'barcode'|'code' }|{ status: 'ambiguous', matches: object[], field: 'barcode'|'code' }}
 */
export function resolveProductByScan(index, raw) {
    const code = normalizeScanCode(raw);
    if (!code) {
        return { status: 'empty' };
    }

    const key = code.toLowerCase();

    if (index.barcodeDuplicateKeys.has(key)) {
        const matches = index.all.filter(
            (p) => p.barcode && normalizeScanCode(p.barcode).toLowerCase() === key,
        );
        return { status: 'ambiguous', matches, field: 'barcode' };
    }

    if (index.byBarcode.has(key)) {
        return { status: 'found', product: index.byBarcode.get(key), field: 'barcode' };
    }

    if (index.codeDuplicateKeys.has(key)) {
        const matches = index.all.filter((p) => {
            const c = p.code ?? p.sku ?? '';
            return c && normalizeScanCode(c).toLowerCase() === key;
        });
        return { status: 'ambiguous', matches, field: 'code' };
    }

    if (index.byCode.has(key)) {
        return { status: 'found', product: index.byCode.get(key), field: 'code' };
    }

    return { status: 'not_found', code };
}

/** Debounce scans identiques (douchette / caméra). */
export function shouldIgnoreRapidScan(lastRef, code, windowMs = 400) {
    const now = Date.now();
    if (lastRef.current?.code === code && now - (lastRef.current?.at ?? 0) < windowMs) {
        return true;
    }
    lastRef.current = { code, at: now };
    return false;
}
