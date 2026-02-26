import ProductsIndex from '@/Pages/Pharmacy/Products/Index';

/**
 * Page Produits du module Quincaillerie.
 * Réutilise la page Pharmacie avec routePrefix et titre adaptés.
 */
export default function HardwareProductsIndex(props) {
    return (
        <ProductsIndex
            {...props}
            routePrefix="hardware"
            pageTitle="Produits - Quincaillerie"
        />
    );
}
