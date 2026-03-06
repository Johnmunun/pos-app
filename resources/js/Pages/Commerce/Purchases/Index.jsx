import PurchasesIndex from '@/Pages/Pharmacy/Purchases/Index';

/**
 * Liste des bons de commande — Global Commerce.
 * Réutilise la page Pharmacie avec routePrefix commerce.
 */
export default function CommercePurchasesIndex(props) {
    const { purchases, purchase_orders, ...rest } = props;

    const normalizedPurchaseOrders =
        purchase_orders ?? purchases ?? [];

    return (
        <PurchasesIndex
            {...rest}
            purchase_orders={normalizedPurchaseOrders}
            routePrefix="commerce"
        />
    );
}
