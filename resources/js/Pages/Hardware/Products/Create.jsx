import ProductCreate from '@/Pages/Pharmacy/Products/Create';

/**
 * Création de produit - Quincaillerie.
 * Réutilise la page Pharmacie avec routePrefix hardware.
 */
export default function HardwareProductCreate(props) {
    return <ProductCreate {...props} routePrefix="hardware" />;
}
