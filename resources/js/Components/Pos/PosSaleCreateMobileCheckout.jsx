import { ChevronRight } from 'lucide-react';

/**
 * Barre de validation fixe (mobile uniquement, ≤767px via CSS).
 */
export default function PosSaleCreateMobileCheckout({
    cartLength,
    totalLabel,
    disabled,
    onCheckout,
}) {
    if (cartLength < 1) {
        return null;
    }

    const itemsLabel = cartLength === 1 ? '1 article' : `${cartLength} articles`;

    return (
        <button
            type="button"
            className="pos-sale-create__mobile-checkout"
            disabled={disabled}
            onClick={onCheckout}
            aria-label="Finaliser la vente"
        >
            <span>
                {itemsLabel} = {totalLabel}
            </span>
            <ChevronRight className="h-5 w-5 shrink-0" aria-hidden />
        </button>
    );
}
