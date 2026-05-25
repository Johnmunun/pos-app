import { useCallback, useMemo, useRef } from 'react';
import toast from 'react-hot-toast';
import {
    buildProductLookupIndex,
    resolveProductByScan,
    shouldIgnoreRapidScan,
} from '@/lib/posProductScan';
import { focusPosScanInput } from '@/hooks/usePosScanAutoFocus';

/**
 * Hook partagé pour scan code-barres / QR / douchette sur les pages de vente.
 *
 * @param {object} options
 * @param {Array} options.products - liste produits Inertia
 * @param {function} options.onProductFound - (product) => void
 * @param {function} [options.onClearSearch] - () => void
 * @param {boolean} [options.playSound] - feedback sonore léger
 * @param {import('react').RefObject<HTMLInputElement|null>} [options.searchInputRef] - refocus après ajout panier
 */
export default function usePosProductScan({
    products,
    onProductFound,
    onClearSearch,
    playSound = false,
    searchInputRef = null,
}) {
    const index = useMemo(() => buildProductLookupIndex(products ?? []), [products]);
    const lastScanRef = useRef(null);

    const refocusScan = useCallback(() => {
        if (searchInputRef) {
            focusPosScanInput(searchInputRef);
        }
    }, [searchInputRef]);

    const playBeep = useCallback((success) => {
        if (!playSound || typeof window === 'undefined') return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = success ? 880 : 220;
            gain.gain.value = 0.08;
            osc.start();
            osc.stop(ctx.currentTime + 0.08);
        } catch {
            // ignore
        }
    }, [playSound]);

    const processScan = useCallback(
        (raw, { silent = false } = {}) => {
            const result = resolveProductByScan(index, raw);

            if (result.status === 'empty') {
                return result;
            }

            const normalized = result.status === 'not_found' ? result.code : raw;
            if (shouldIgnoreRapidScan(lastScanRef, String(normalized).trim())) {
                return { status: 'debounced' };
            }

            if (result.status === 'found') {
                onProductFound(result.product);
                onClearSearch?.();
                refocusScan();
                if (!silent) {
                    toast.success(`Ajouté : ${result.product.name}`);
                }
                playBeep(true);
                return result;
            }

            if (result.status === 'ambiguous') {
                if (!silent) {
                    toast.error(
                        `${result.matches.length} produits correspondent à ce code. Recherchez ou sélectionnez manuellement.`,
                    );
                }
                playBeep(false);
                return result;
            }

            if (!silent) {
                toast.error('Produit introuvable pour ce code');
            }
            playBeep(false);
            return result;
        },
        [index, onProductFound, onClearSearch, playBeep, refocusScan],
    );

    /** Correspondance exacte (saisie / douchette) — pas de toast si introuvable (frappe en cours). */
    const tryResolveFromSearch = useCallback(
        (text) => {
            const trimmed = String(text ?? '').trim();
            if (!trimmed) return false;

            const result = resolveProductByScan(index, trimmed);
            if (shouldIgnoreRapidScan(lastScanRef, trimmed)) {
                return result.status === 'found';
            }

            if (result.status === 'found') {
                onProductFound(result.product);
                onClearSearch?.();
                refocusScan();
                playBeep(true);
                return true;
            }

            return false;
        },
        [index, onProductFound, onClearSearch, playBeep, refocusScan],
    );

    return {
        index,
        processScan,
        tryResolveFromSearch,
    };
}
