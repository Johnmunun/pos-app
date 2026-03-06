import PurchasesCreate from '@/Pages/Pharmacy/Purchases/Create';

/**
 * Création bon de commande — Global Commerce.
 * Réutilise la page Pharmacie avec routePrefix commerce.
 */
export default function CommercePurchasesCreate(props) {
    return <PurchasesCreate {...props} routePrefix="commerce" />;
}

