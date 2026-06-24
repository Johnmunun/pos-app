import { useEffect } from 'react';
import { X } from 'lucide-react';

/**
 * Coque modal paiement POS — plein écran / bottom sheet sur mobile, centré sur desktop.
 */
export default function PosSaleCreatePaymentModal({
    open,
    onClose,
    title = 'Mode de paiement',
    children,
    footer,
    onKeyDown,
}) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = prev;
        };
    }, [open]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="pos-sale-create__payment-overlay fixed inset-0 z-[100] flex items-end justify-center bg-black/50 sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label={title}
            onKeyDown={onKeyDown}
        >
            <div className="pos-sale-create__payment-modal flex w-full max-h-[min(96dvh,100%)] flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl dark:bg-slate-900 sm:max-h-[min(90vh,720px)] sm:max-w-md sm:rounded-2xl">
                <div className="flex shrink-0 items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-slate-700 sm:px-6 sm:py-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">{title}</h3>
                    <span className="hidden text-xs text-gray-500 dark:text-gray-400 sm:inline">
                        Échap · F2 tout · Entrée valider
                    </span>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800 dark:hover:text-gray-200"
                        aria-label="Fermer"
                    >
                        <X className="h-6 w-6" />
                    </button>
                </div>

                <div className="pos-sale-create__payment-body min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6 sm:py-5">
                    {children}
                </div>

                {footer ? (
                    <div className="shrink-0 border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800 sm:px-6 sm:py-4 pb-[calc(0.75rem+env(safe-area-inset-bottom,0))]">
                        {footer}
                    </div>
                ) : null}
            </div>
        </div>
    );
}
