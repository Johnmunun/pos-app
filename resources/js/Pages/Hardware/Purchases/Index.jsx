import PurchasesIndex from '@/Pages/Pharmacy/Purchases/Index';

/**
 * Page Bons de commande - Quincaillerie.
 * Réutilise la page Pharmacie avec drawer (création/édition) et routePrefix hardware.
 */
export default function HardwarePurchasesIndex(props) {
    return <PurchasesIndex {...props} routePrefix="hardware" />;
}
