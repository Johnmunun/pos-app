import {
    hasPharmacyAccess,
    hasHardwareAccess,
    hasCommerceModuleAccess,
    hasEcommerceModuleAccess,
    hasItemPermission,
} from '@/lib/sidebarModuleAccess';
import { LayoutDashboard, ShoppingCart, Warehouse, User } from 'lucide-react';

/**
 * Raccourcis tactile bas d'écran (<768px) — mêmes routes conceptuelles que la sidebar.
 */
export function buildMobileBottomNavItems({ permissions, tenantSector, isRoot, url }) {
    const ctx = { permissions, tenantSector, isRoot, url };
    const p = Array.isArray(permissions) ? permissions : [];

    const items = [];

    // Accueil
    if (isRoot || p.includes('admin.dashboard.view')) {
        items.push({
            key: 'home',
            label: 'Accueil',
            href: '/dashboard',
            icon: LayoutDashboard,
        });
    } else if (hasPharmacyAccess(ctx)) {
        if (hasItemPermission(p, isRoot, 'module.pharmacy|pharmacy.sales.view|pharmacy.product.manage|pharmacy.pharmacy.product.manage|stock.view|inventory.view|pharmacy.report.view|pharmacy.purchases.view')) {
            items.push({ key: 'home', label: 'Accueil', href: '/pharmacy/dashboard', icon: LayoutDashboard });
        }
    } else if (hasHardwareAccess(ctx)) {
        if (hasItemPermission(p, isRoot, 'module.hardware|hardware.sales.view|hardware.product.view|hardware.stock.view|hardware.report.view')) {
            items.push({ key: 'home', label: 'Accueil', href: '/hardware/dashboard', icon: LayoutDashboard });
        }
    } else if (hasCommerceModuleAccess(ctx)) {
        if (hasItemPermission(p, isRoot, 'module.commerce')) {
            items.push({ key: 'home', label: 'Accueil', href: '/commerce/dashboard', icon: LayoutDashboard });
        }
    } else if (hasEcommerceModuleAccess(ctx)) {
        if (hasItemPermission(p, isRoot, 'ecommerce.dashboard.view|module.ecommerce')) {
            items.push({ key: 'home', label: 'Accueil', href: '/ecommerce/dashboard', icon: LayoutDashboard });
        }
    } else if (hasItemPermission(p, isRoot, '*')) {
        items.push({ key: 'home', label: 'Accueil', href: '/dashboard', icon: LayoutDashboard });
    }

    // Vente
    if (hasPharmacyAccess(ctx) && hasItemPermission(p, isRoot, 'pharmacy.sales.view|pharmacy.sales.manage|pharmacy.pharmacy.sale.create')) {
        items.push({ key: 'sale', label: 'Vente', href: '/pharmacy/sales', icon: ShoppingCart });
    } else if (hasHardwareAccess(ctx) && hasItemPermission(p, isRoot, 'hardware.sales.view|hardware.sales.manage')) {
        items.push({ key: 'sale', label: 'Vente', href: '/hardware/sales', icon: ShoppingCart });
    } else if (hasCommerceModuleAccess(ctx) && hasItemPermission(p, isRoot, 'commerce.sales.view|commerce.sales.manage|module.commerce')) {
        items.push({ key: 'sale', label: 'Vente', href: '/commerce/sales', icon: ShoppingCart });
    } else if (hasEcommerceModuleAccess(ctx) && hasItemPermission(p, isRoot, 'ecommerce.order.view|ecommerce.order.manage|module.ecommerce')) {
        items.push({ key: 'sale', label: 'Vente', href: '/ecommerce/orders', icon: ShoppingCart });
    }

    // Stock
    if (hasPharmacyAccess(ctx) && hasItemPermission(p, isRoot, 'pharmacy.pharmacy.stock.manage|stock.view')) {
        items.push({ key: 'stock', label: 'Stock', href: '/pharmacy/stock', icon: Warehouse });
    } else if (hasHardwareAccess(ctx) && hasItemPermission(p, isRoot, 'hardware.stock.view|hardware.stock.manage')) {
        items.push({ key: 'stock', label: 'Stock', href: '/hardware/stock', icon: Warehouse });
    } else if (hasCommerceModuleAccess(ctx) && hasItemPermission(p, isRoot, 'commerce.stock.view|commerce.stock.manage|module.commerce')) {
        items.push({ key: 'stock', label: 'Stock', href: '/commerce/stock', icon: Warehouse });
    } else if (hasEcommerceModuleAccess(ctx) && hasItemPermission(p, isRoot, 'ecommerce.stock.view|ecommerce.stock.manage|module.ecommerce')) {
        items.push({ key: 'stock', label: 'Stock', href: '/ecommerce/stock', icon: Warehouse });
    }

    // Profil (toujours si connecté)
    if (hasItemPermission(p, isRoot, '*')) {
        items.push({ key: 'profile', label: 'Profil', href: '/profile', icon: User });
    }

    // Dédupliquer par key en gardant l'ordre
    const seen = new Set();
    return items.filter((it) => {
        if (seen.has(it.key)) return false;
        seen.add(it.key);
        return true;
    });
}
