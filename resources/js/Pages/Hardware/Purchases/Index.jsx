import PurchasesIndex from '@/Pages/Pharmacy/Purchases/Index';

/**
 * Page Bons de commande - Quincaillerie.
 * Réutilise la page Pharmacie avec drawer (création/édition) et routePrefix hardware.
 */
export default function HardwarePurchasesIndex(props) {
    console.log('🔍 HardwarePurchasesIndex - Props reçues:', {
        purchase_orders_count: props?.purchase_orders?.length || 0,
        suppliers_count: props?.suppliers?.length || 0,
        products_count: props?.products?.length || 0,
        filters: props?.filters,
        routePrefix: 'hardware',
        all_props: props,
    });
    
    return <PurchasesIndex {...props} routePrefix="hardware" />;
}
