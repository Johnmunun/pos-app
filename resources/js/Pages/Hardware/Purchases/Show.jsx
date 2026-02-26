import PurchasesShow from '@/Pages/Pharmacy/Purchases/Show';

/**
 * Détail bon de commande - Quincaillerie.
 * Réutilise la page Pharmacie (confirm, receive, cancel, drawers) avec routePrefix hardware.
 */
export default function HardwarePurchasesShow(props) {
    return <PurchasesShow {...props} routePrefix="hardware" />;
}
