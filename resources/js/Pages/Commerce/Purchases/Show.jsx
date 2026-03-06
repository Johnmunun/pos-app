import PurchasesShow from '@/Pages/Pharmacy/Purchases/Show';

/**
 * Détail bon de commande — Global Commerce.
 * Réutilise la page Pharmacie (confirm, receive, cancel, drawers) avec routePrefix commerce.
 */
export default function CommercePurchasesShow(props) {
    const { purchase, purchase_order, lines, ...rest } = props;

    const normalizedPurchaseOrder = purchase_order ?? purchase ?? null;
    const normalizedLines =
        lines ??
        normalizedPurchaseOrder?.lines ??
        [];

    return (
        <PurchasesShow
            {...rest}
            purchase_order={normalizedPurchaseOrder}
            lines={normalizedLines}
            suppliers={props.suppliers || []}
            products={props.products || []}
            routePrefix="commerce"
        />
    );
}
