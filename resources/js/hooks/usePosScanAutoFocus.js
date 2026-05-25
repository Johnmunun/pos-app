import { useCallback, useEffect } from 'react';

/**
 * Remet le focus sur le champ scan (douchette USB / Bluetooth clavier).
 * Compatible PC, tablette et mobile (clavier physique).
 */
export function focusPosScanInput(inputRef) {
    if (typeof window === 'undefined' || !inputRef?.current) {
        return;
    }

    window.requestAnimationFrame(() => {
        const el = inputRef.current;
        if (!el || typeof el.focus !== 'function') {
            return;
        }
        try {
            el.focus({ preventScroll: true });
        } catch {
            el.focus();
        }
    });
}

/**
 * Focus automatique pour les pages de vente POS.
 *
 * @param {object} options
 * @param {import('react').RefObject<HTMLInputElement|null>} options.inputRef
 * @param {boolean} [options.enabled]
 * @param {boolean} [options.scanModalOpen]
 * @param {boolean} [options.showPaymentModal]
 * @param {boolean} [options.showAddCustomerModal]
 */
export default function usePosScanAutoFocus({
    inputRef,
    enabled = true,
    scanModalOpen = false,
    showPaymentModal = false,
    showAddCustomerModal = false,
}) {
    const canFocus = useCallback(() => {
        return enabled && !scanModalOpen && !showPaymentModal && !showAddCustomerModal;
    }, [enabled, scanModalOpen, showPaymentModal, showAddCustomerModal]);

    const focusScanField = useCallback(() => {
        if (canFocus()) {
            focusPosScanInput(inputRef);
        }
    }, [canFocus, inputRef]);

    useEffect(() => {
        const timer = window.setTimeout(focusScanField, 150);
        return () => window.clearTimeout(timer);
    }, [focusScanField]);

    useEffect(() => {
        if (!scanModalOpen) {
            const timer = window.setTimeout(focusScanField, 80);
            return () => window.clearTimeout(timer);
        }
        return undefined;
    }, [scanModalOpen, focusScanField]);

    useEffect(() => {
        if (!showPaymentModal && !showAddCustomerModal) {
            const timer = window.setTimeout(focusScanField, 80);
            return () => window.clearTimeout(timer);
        }
        return undefined;
    }, [showPaymentModal, showAddCustomerModal, focusScanField]);

    useEffect(() => {
        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                focusScanField();
            }
        };
        document.addEventListener('visibilitychange', onVisibility);
        return () => document.removeEventListener('visibilitychange', onVisibility);
    }, [focusScanField]);

    return { focusScanField };
}
