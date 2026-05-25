/**
 * Règles d'accès modules sidebar — partagées entre Sidebar et navigation mobile.
 * (Même logique qu'historiquement dans Sidebar.jsx.)
 */

export function hasPharmacyAccess({ permissions, tenantSector, isRoot }) {
    if (tenantSector === 'hardware') return false;
    if (isRoot || permissions.includes('*')) return true;
    return permissions.some(
        (perm) => perm === 'module.pharmacy' || (typeof perm === 'string' && perm.startsWith('pharmacy.')),
    );
}

export function hasHardwareAccess({ permissions, tenantSector, isRoot }) {
    if (tenantSector === 'pharmacy') return false;
    if (isRoot || permissions.includes('*')) return true;
    if (tenantSector === 'hardware') return true;
    return permissions.some(
        (perm) => perm === 'module.hardware' || (typeof perm === 'string' && perm.startsWith('hardware.')),
    );
}

export function hasCommerceModuleAccess({ permissions, tenantSector, isRoot, url }) {
    if (typeof url === 'string' && url.startsWith('/ecommerce')) return false;
    if (isRoot || permissions.includes('*')) return true;
    if (tenantSector === 'pharmacy' || tenantSector === 'hardware' || tenantSector === 'ecommerce') return false;
    if (
        tenantSector === 'commerce'
        || ['kiosk', 'supermarket', 'butchery', 'other'].includes(tenantSector)
    ) {
        return true;
    }
    if (permissions.includes('module.ecommerce')) return false;
    return permissions.some(
        (perm) => perm === 'module.commerce' || (typeof perm === 'string' && perm.startsWith('commerce.')),
    );
}

export function hasEcommerceModuleAccess({ permissions, tenantSector, isRoot, url }) {
    if (typeof url === 'string' && url.startsWith('/commerce')) return false;
    if (isRoot || permissions.includes('*')) return true;
    if (tenantSector === 'ecommerce') return true;
    return permissions.some(
        (perm) => perm === 'module.ecommerce' || (typeof perm === 'string' && perm.startsWith('ecommerce.')),
    );
}

export function hasItemPermission(permissions, isRoot, itemPermission) {
    if (isRoot || permissions.includes('*')) return true;
    if (itemPermission === '*') return true;
    const perms = String(itemPermission)
        .split('|')
        .map((p) => p.trim())
        .filter(Boolean);
    return perms.some((p) => permissions.includes(p));
}

/**
 * Bouton « Prévisualiser la boutique » : uniquement module E-commerce (pas Commerce POS / pharmacie / quincaillerie).
 */
export function canShowEcommerceStorefrontPreview({
    permissions,
    tenantSector,
    isRoot,
    url,
    planFeatures = {},
}) {
    if (planFeatures.ecommerce_module === false) {
        return false;
    }

    if (!hasItemPermission(permissions, isRoot, 'ecommerce.catalog.view|ecommerce.view|module.ecommerce')) {
        return false;
    }

    const onEcommerceContext =
        tenantSector === 'ecommerce'
        || (typeof url === 'string' && url.startsWith('/ecommerce'));

    if (!onEcommerceContext) {
        return false;
    }

    return hasEcommerceModuleAccess({ permissions, tenantSector, isRoot, url });
}
