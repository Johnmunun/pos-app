import ShowSupplier from '@/Pages/Pharmacy/Suppliers/Show';

/**
 * Détail fournisseur - Quincaillerie.
 * Réutilise la page Pharmacie (drawer édition, prix, etc.) avec routePrefix hardware.
 */
export default function HardwareSupplierShow(props) {
    return <ShowSupplier {...props} routePrefix="hardware" />;
}
