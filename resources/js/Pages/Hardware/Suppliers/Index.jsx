import SuppliersIndex from '@/Pages/Pharmacy/Suppliers/Index';

/**
 * Page Fournisseurs - Quincaillerie.
 * Réutilise la page Pharmacie avec drawer (création/édition) et routePrefix hardware.
 */
export default function HardwareSuppliersIndex(props) {
    return <SuppliersIndex {...props} routePrefix="hardware" />;
}
