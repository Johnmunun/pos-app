import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    Home,
    LayoutDashboard,
    Bell,
    ClipboardList,
    ShoppingCart,
    Plus,
    FileText,
    Receipt,
    Users,
    BookOpen,
    User,
    TrendingUp,
    Package,
    Tag,
    AlertTriangle,
    CreditCard,
    Scroll,
    LifeBuoy,
    Ticket,
    AlertCircle,
    HelpCircle,
    MessageCircle,
    Settings,
    UserCog,
    Lock,
    Palette,
    Building,
    BarChart,
    Download,
    Pill,
    Calendar,
    DollarSign,
    Truck,
    Warehouse,
    Wrench,
    ArrowLeftRight,
    Store,
    ShoppingBag,
    Globe,
    Truck as TruckIcon,
    Gift,
    Star,
    FileText as PageIcon,
    Image,
    LayoutTemplate,
    BookOpen as BlogIcon,
    FolderOpen,
    Eye,
    Mail as MailIcon,
} from 'lucide-react';

/**
 * Component: Sidebar
 * 
 * Sidebar groupée avec visibilité basée sur les permissions
 * Mobile-first : drawer/off-canvas sur mobile, fixe sur desktop
 */
export default function Sidebar({ permissions: permissionsProp, tenantSector = null, isRoot = false, isOpen, onClose, currentUrl }) {
    const [expandedGroups, setExpandedGroups] = useState({});
    const permissions = Array.isArray(permissionsProp) ? permissionsProp : [];

    const page = usePage();
    const url = currentUrl || page.url;
    const appLogoUrl = page.props?.appLogoUrl || null;
    const featureFlags = page.props?.auth?.featureFlags || {};
    const planFeatures = page.props?.auth?.planFeatures || {};
    const billingSummary = page.props?.auth?.billingSummary || null;
    const planExpiryLabel = billingSummary?.expires_at
        ? new Date(billingSummary.expires_at).toLocaleDateString()
        : '-';
    
    // Fonction pour vérifier si une route est active
    const isActiveRoute = (href) => {
        if (!href || href === '#') return false;
        
        // Normaliser les URLs (enlever les trailing slashes)
        const normalizeUrl = (u) => u.replace(/\/$/, '') || '/';
        const currentPath = normalizeUrl(url);
        const itemPath = normalizeUrl(href);
        
        // Correspondance exacte
        if (currentPath === itemPath) return true;
        
        // Correspondance pour les routes enfants (ex: /pharmacy/products active si on est sur /pharmacy/products/123)
        if (currentPath.startsWith(itemPath + '/')) return true;
        
        return false;
    };

    // Toggle groupe expandé
    const toggleGroup = (groupKey) => {
        setExpandedGroups(prev => ({
            ...prev,
            [groupKey]: !prev[groupKey]
        }));
    };

    // Vérifier si l'utilisateur a au moins une des permissions d'un item (supporte "perm1|perm2")
    const hasItemPermission = (itemPermission) => {
        if (isRoot || permissions.includes('*')) return true;
        if (itemPermission === '*') return true;
        const perms = String(itemPermission).split('|').map(p => p.trim()).filter(Boolean);
        return perms.some(p => permissions.includes(p));
    };

    // Vérifier si un groupe a au moins une permission visible
    const hasVisibleItem = (groupPermissions) => {
        if (isRoot) return true;
        if (!Array.isArray(permissions) || permissions.length === 0) return false;
        if (permissions.includes('*')) return true;
        return groupPermissions.some(perm => permissions.includes(perm));
    };

    // Pour le groupe Pharmacy : afficher si l'utilisateur a une permission pharmacy.* ou module.pharmacy
    // Ne pas inclure stock.* ou inventory.* car ils sont partagés avec Hardware
    // Si tenantSector est 'hardware', ne jamais montrer Pharmacy
    const hasPharmacyAccess = () => {
        if (tenantSector === 'hardware') return false; // Si on est dans Hardware, ne pas montrer Pharmacy
        if (isRoot || permissions.includes('*')) return true;
        return permissions.some(
            (p) =>
                p === 'module.pharmacy' ||
                (typeof p === 'string' && p.startsWith('pharmacy.'))
        );
    };

    // Pour le groupe Quincaillerie : module.hardware, hardware.* ou tenant secteur "hardware"
    // Si tenantSector est 'pharmacy', ne jamais montrer Hardware
    const hasHardwareAccess = () => {
        if (tenantSector === 'pharmacy') return false; // Si on est dans Pharmacy, ne pas montrer Hardware
        if (isRoot || permissions.includes('*')) return true;
        if (tenantSector === 'hardware') return true;
        return permissions.some(
            (p) => p === 'module.hardware' || (typeof p === 'string' && p.startsWith('hardware.'))
        );
    };

    // Module Global Commerce (Kiosque, Supermarché, Boucherie, Autre)
    // IMPORTANT: Ne pas confondre avec module.ecommerce (vente en ligne)
    const hasCommerceModuleAccess = () => {
        if (typeof url === 'string' && url.startsWith('/ecommerce')) return false;
        if (isRoot || permissions.includes('*')) return true;
        // Si le tenant est explicitement pharmacy, hardware ou ecommerce, ne pas montrer Commerce
        if (tenantSector === 'pharmacy' || tenantSector === 'hardware' || tenantSector === 'ecommerce') return false;
        // Si le tenant est commerce ou un secteur commerce (kiosk, supermarket, butchery, other)
        if (tenantSector === 'commerce' || ['kiosk', 'supermarket', 'butchery', 'other'].includes(tenantSector)) return true;
        // Vérifier les permissions - mais exclure ecommerce
        if (permissions.includes('module.ecommerce')) return false; // E-commerce est un module séparé
        // Sinon, vérifier les permissions commerce
        return permissions.some(
            (p) => p === 'module.commerce' || (typeof p === 'string' && p.startsWith('commerce.'))
        );
    };

    // Module E-commerce (Vente en ligne) - séparé du module Commerce
    const hasEcommerceModuleAccess = () => {
        if (typeof url === 'string' && url.startsWith('/commerce')) return false;
        if (isRoot || permissions.includes('*')) return true;
        if (tenantSector === 'ecommerce') return true;
        return permissions.some(
            (p) => p === 'module.ecommerce' || (typeof p === 'string' && p.startsWith('ecommerce.'))
        );
    };

    // Définition des groupes de navigation
    const navigationGroups = [
        {
            key: 'general',
            label: 'Général',
            icon: Home,
            permissions: ['dashboard.view', 'notifications.view', 'activity.view', '*'],
            items: [
                { label: 'Dashboard', href: '/dashboard', permission: '*', icon: LayoutDashboard, rootOnly: true, excludeSectors: ['ecommerce'] },
                { label: 'Mon profil', href: '/profile', permission: '*', icon: User },
                { label: 'Notifications', href: '#', permission: 'notifications.view', icon: Bell },
                { label: 'Activité récente', href: '#', permission: 'activity.view', icon: ClipboardList },
            ]
        },
        {
            key: 'commerce',
            label: 'Commerce / Vente',
            icon: ShoppingCart,
            permissions: ['sales.view', 'sales.create', 'invoices.view', 'customers.view', 'sellers.view'],
            items: [
                { label: 'Nouvelle vente', href: '#', permission: 'sales.create', icon: Plus },
                { label: 'Liste des ventes', href: '#', permission: 'sales.view', icon: FileText },
                { label: 'Factures', href: '#', permission: 'invoices.view', icon: Receipt },
                { label: 'Reçus', href: '#', permission: 'invoices.view', icon: Receipt },
                { label: 'Liste des clients', href: '#', permission: 'customers.view', icon: Users },
                { label: 'Historique client', href: '#', permission: 'customers.view', icon: BookOpen },
                { label: 'Créer un seller', href: '#', permission: 'sellers.create', icon: Plus },
                { label: 'Liste des sellers', href: '#', permission: 'sellers.view', icon: User },
                { label: 'Performance des sellers', href: '#', permission: 'sellers.view', icon: TrendingUp },
            ]
        },
        {
            key: 'products',
            label: 'Produits & Stock',
            icon: Package,
            permissions: ['products.view', 'products.create', 'categories.view', 'inventory.view'],
            items: [
                { label: 'Ajouter produit', href: '#', permission: 'products.create', icon: Plus },
                { label: 'Liste produits', href: '#', permission: 'products.view', icon: Package },
                { label: 'Catégories', href: '/categories', permission: 'categories.view', icon: Tag },
                { label: 'Mouvement de stock', href: '#', permission: 'inventory.view', icon: BarChart },
                { label: 'Alertes stock bas', href: '#', permission: 'inventory.view', icon: AlertTriangle },
            ]
        },
        {
            key: 'pharmacy',
            label: 'Pharmacy',
            icon: Pill,
            permissions: ['module.pharmacy', 'pharmacy.pharmacy.product.manage', 'pharmacy.product.manage', 'pharmacy.category.view', 'pharmacy.pharmacy.stock.manage', 'stock.view', 'inventory.view', 'pharmacy.sales.view', 'pharmacy.sales.manage', 'pharmacy.purchases.view', 'pharmacy.purchases.manage', 'pharmacy.supplier.view', 'pharmacy.customer.view', 'pharmacy.seller.view', 'pharmacy.expiration.view', 'pharmacy.batch.view', 'pharmacy.report.view', 'admin.modules.view'],
            items: [
                { label: 'Dashboard', href: '/pharmacy/dashboard', permission: 'module.pharmacy|pharmacy.sales.view|pharmacy.product.manage|pharmacy.pharmacy.product.manage|stock.view|inventory.view|pharmacy.report.view|pharmacy.purchases.view', icon: LayoutDashboard },
                { label: 'Produits', href: '/pharmacy/products', permission: 'pharmacy.pharmacy.product.manage|pharmacy.product.manage', icon: Package },
                { label: 'Catégories', href: '/pharmacy/categories', permission: 'pharmacy.category.view', icon: Tag },
                { label: 'Stock', href: '/pharmacy/stock', permission: 'pharmacy.pharmacy.stock.manage|stock.view', icon: BarChart },
                { label: 'Inventaires', href: '/pharmacy/inventories', permission: 'inventory.view', icon: ClipboardList },
                { label: 'Expirations', href: '/pharmacy/expirations', permission: 'pharmacy.expiration.view|pharmacy.batch.view', icon: Calendar },
                { label: 'Ventes', href: '/pharmacy/sales', permission: 'pharmacy.sales.view|pharmacy.sales.manage|pharmacy.pharmacy.sale.create', icon: ShoppingCart },
                { label: 'Achats', href: '/pharmacy/purchases', permission: 'pharmacy.purchases.view|pharmacy.purchases.manage', icon: Receipt },
                { label: 'Fournisseurs', href: '/pharmacy/suppliers', permission: 'pharmacy.supplier.view', icon: Truck },
                { label: 'Clients', href: '/pharmacy/customers', permission: 'pharmacy.customer.view', icon: Users },
                { label: 'Vendeurs', href: '/pharmacy/sellers', permission: 'pharmacy.seller.view', icon: User },
                { label: 'Dépôts', href: '/pharmacy/depots', permission: 'pharmacy.seller.view', icon: Warehouse },
                { label: 'Transferts', href: '/pharmacy/transfers', permission: 'transfer.view|transfer.create', icon: ArrowLeftRight },
                { label: 'Rapports', href: '/pharmacy/reports', permission: 'pharmacy.sales.view|pharmacy.report.view', icon: FileText },
            ]
        },
        {
            key: 'global_commerce',
            label: 'Global Commerce',
            icon: Store,
            permissions: ['module.commerce', 'commerce.product.view', 'commerce.category.view', 'commerce.sales.view', 'commerce.purchases.view', 'commerce.supplier.view', 'commerce.customer.view', 'commerce.seller.view', 'commerce.stock.view', 'commerce.inventory.view', 'commerce.transfer.view', 'commerce.depot.view', 'commerce.report.view'],
            items: [
                { label: 'Dashboard', href: '/commerce/dashboard', permission: 'module.commerce', icon: LayoutDashboard },
                { label: 'Produits', href: '/commerce/products', permission: 'commerce.product.view|commerce.product.manage', icon: Package },
                { label: 'Catégories', href: '/commerce/categories', permission: 'commerce.category.view|commerce.category.manage', icon: Tag },
                { label: 'Stock', href: '/commerce/stock', permission: 'commerce.stock.view|commerce.stock.manage|module.commerce', icon: Warehouse },
                { label: 'Inventaires', href: '/commerce/inventories', permission: 'commerce.inventory.view|commerce.inventory.manage|module.commerce', icon: ClipboardList },
                { label: 'Transferts', href: '/commerce/transfers', permission: 'commerce.transfer.view|commerce.transfer.create|module.commerce', icon: ArrowLeftRight },
                { label: 'Ventes', href: '/commerce/sales', permission: 'commerce.sales.view|commerce.sales.manage|module.commerce', icon: ShoppingCart },
                { label: 'Achats', href: '/commerce/purchases', permission: 'commerce.purchases.view|commerce.purchases.manage', icon: Receipt },
                { label: 'Fournisseurs', href: '/commerce/suppliers', permission: 'commerce.supplier.view|commerce.supplier.create|commerce.supplier.manage', icon: Truck },
                { label: 'Clients', href: '/commerce/customers', permission: 'commerce.customer.view|commerce.customer.manage|module.commerce', icon: Users },
                { label: 'Vendeurs', href: '/commerce/sellers', permission: 'commerce.seller.view|commerce.seller.manage|module.commerce', icon: User },
                { label: 'Dépôts', href: '/commerce/depots', permission: 'commerce.depot.view|commerce.depot.manage|module.commerce', icon: Warehouse },
                { label: 'Rapports', href: '/commerce/reports', permission: 'commerce.report.view|commerce.report.export|module.commerce', icon: BarChart },
            ]
        },
        {
            key: 'ecommerce',
            label: 'E-commerce',
            icon: ShoppingBag,
            permissions: ['module.ecommerce', 'ecommerce.view', 'ecommerce.dashboard.view', 'ecommerce.product.view', 'ecommerce.order.view', 'ecommerce.customer.view', 'ecommerce.catalog.view'],
            items: [
                { label: 'Dashboard', href: '/ecommerce/dashboard', permission: 'ecommerce.dashboard.view|module.ecommerce', icon: LayoutDashboard },
                { label: 'Catalogue', href: '/ecommerce/catalog', permission: 'ecommerce.catalog.view|ecommerce.product.view|module.ecommerce', icon: Package },
                { label: 'Produits', href: '/ecommerce/products', permission: 'ecommerce.product.view|ecommerce.product.manage|module.ecommerce', icon: Package },
                { label: 'Catégories', href: '/ecommerce/categories', permission: 'ecommerce.category.view|ecommerce.category.manage|module.ecommerce', icon: Tag },
                { label: 'Ventes', href: '/ecommerce/orders', permission: 'ecommerce.order.view|ecommerce.order.manage|module.ecommerce', icon: ShoppingCart },
                { label: 'Clients', href: '/ecommerce/customers', permission: 'ecommerce.customer.view|ecommerce.customer.manage|module.ecommerce', icon: Users },
                { label: 'Fournisseurs', href: '/ecommerce/suppliers', permission: 'ecommerce.view|module.ecommerce', icon: Truck },
                { label: 'Paiements', href: '/ecommerce/payments', permission: 'ecommerce.payment.view|ecommerce.payment.manage|module.ecommerce', icon: CreditCard },
                { label: 'Livraisons', href: '/ecommerce/shipping', permission: 'ecommerce.shipping.view|ecommerce.shipping.manage|module.ecommerce', icon: TruckIcon },
                { label: 'Promotions', href: '/ecommerce/promotions', permission: 'ecommerce.promotion.view|ecommerce.promotion.manage|module.ecommerce', icon: Gift },
                { label: 'Coupons', href: '/ecommerce/coupons', permission: 'ecommerce.coupon.view|ecommerce.coupon.manage|module.ecommerce', icon: Ticket },
                { label: 'Avis', href: '/ecommerce/reviews', permission: 'ecommerce.review.view|ecommerce.review.manage|module.ecommerce', icon: Star },
                { label: 'Stock', href: '/ecommerce/stock', permission: 'ecommerce.stock.view|ecommerce.stock.manage|module.ecommerce', icon: Warehouse },
                { label: 'Rapports', href: '/ecommerce/reports', permission: 'ecommerce.report.view|ecommerce.analytics.view|module.ecommerce', icon: BarChart },
                { label: 'Paramètres', href: '/ecommerce/settings', permission: 'ecommerce.settings.view|ecommerce.settings.update|module.ecommerce', icon: Settings },
                { label: 'Marketing', href: '/ecommerce/marketing', permission: 'ecommerce.marketing.view|ecommerce.marketing.manage|ecommerce.settings.view|module.ecommerce', icon: BarChart },
                { label: 'Prévisualiser la boutique', href: '/ecommerce/storefront', permission: 'ecommerce.catalog.view|ecommerce.view|module.ecommerce', icon: Eye },
                { header: true, label: 'CMS' },
                { label: 'Pages', href: '/ecommerce/cms/pages', permission: 'ecommerce.cms.view|ecommerce.settings.view|module.ecommerce', icon: PageIcon },
                { label: 'Bannières', href: '/ecommerce/cms/banners', permission: 'ecommerce.cms.view|ecommerce.settings.view|module.ecommerce', icon: Image },
                { label: 'Sections accueil', href: '/ecommerce/storefront/cms', permission: 'ecommerce.cms.view|ecommerce.settings.view|module.ecommerce', icon: LayoutTemplate },
                { label: 'Blog / Articles', href: '/ecommerce/cms/blog', permission: 'ecommerce.cms.view|ecommerce.settings.view|module.ecommerce', icon: BlogIcon },
                { label: 'Médias', href: '/ecommerce/cms/media', permission: 'ecommerce.cms.view|ecommerce.settings.view|module.ecommerce', icon: FolderOpen },
            ]
        },
        {
            key: 'hardware',
            label: 'Quincaillerie',
            icon: Wrench,
            permissions: ['module.hardware', 'hardware.product.view', 'hardware.sales.view', 'hardware.stock.view', 'hardware.report.view'],
            items: [
                { label: 'Dashboard', href: '/hardware/dashboard', permission: 'module.hardware|hardware.sales.view|hardware.product.view|hardware.stock.view|hardware.report.view', icon: LayoutDashboard },
                { label: 'Produits', href: '/hardware/products', permission: 'hardware.product.view|hardware.product.manage', icon: Package },
                { label: 'Catégories', href: '/hardware/categories', permission: 'hardware.category.view', icon: Tag },
                { label: 'Stock', href: '/hardware/stock', permission: 'hardware.stock.view|hardware.stock.manage', icon: Warehouse },
                { label: 'Mouvements', href: '/hardware/stock/movements', permission: 'hardware.stock.movement.view|hardware.stock.manage', icon: Scroll },
                { label: 'Ventes', href: '/hardware/sales', permission: 'hardware.sales.view|hardware.sales.manage', icon: ShoppingCart },
                { label: 'Caisse', href: '/hardware/sales/create', permission: 'hardware.sales.manage', icon: Plus },
                { label: 'Clients', href: '/hardware/customers', permission: 'hardware.customer.view', icon: Users },
                { label: 'Fournisseurs', href: '/hardware/suppliers', permission: 'hardware.supplier.view', icon: Truck },
                { label: 'Bons de commande', href: '/hardware/purchases', permission: 'hardware.purchases.view|hardware.purchases.manage', icon: FileText },
                { label: 'Dépôts', href: '/hardware/depots', permission: 'hardware.warehouse.view_all|hardware.warehouse.view|hardware.stock.view|hardware.stock.manage', icon: Warehouse },
                { label: 'Transferts', href: '/hardware/transfers', permission: 'transfer.view|transfer.create', icon: ArrowLeftRight },
                { label: 'Rapports', href: '/hardware/reports', permission: 'hardware.sales.view|hardware.report.view', icon: BarChart },
            ]
        },
        {
            key: 'payments',
            label: 'Paiements & Finance',
            icon: CreditCard,
            permissions: ['payments.view', 'payments.methods', 'finance.reports', 'finance.dashboard.view', 'finance.expense.view'],
            items: [
                { label: 'Méthodes de paiement', href: '#', permission: 'payments.methods', icon: CreditCard },
                { label: 'Historique financier', href: '#', permission: 'payments.view', icon: Scroll },
                { label: 'Dashboard Finance', href: '/finance/dashboard', permission: 'finance.dashboard.view|finance.report.view|finance.reports', icon: BarChart },
                { label: 'Dépenses', href: '/finance/expenses', permission: 'finance.expense.view|finance.expense.manage', icon: DollarSign },
            ]
        },
        {
            key: 'support',
            label: 'IT / Support',
            icon: LifeBuoy,
            permissions: ['support.tickets.create', 'support.tickets.view', 'support.admin', 'support.faq'],
            items: [
                { label: 'Créer un ticket', href: '/support/tickets/create', permission: 'support.tickets.create', icon: Plus },
                { label: 'Mes tickets', href: '/support/tickets', permission: 'support.tickets.view', icon: Ticket },
                { label: 'Tous les tickets', href: '/support/admin/tickets', permission: 'support.admin', icon: ClipboardList },
                { label: 'Support Chat', href: '/support/admin/chat', permission: 'support.admin', icon: MessageCircle },
                { label: 'Incidents', href: '/support/incidents', permission: 'support.admin', icon: AlertCircle },
                { label: 'FAQ / Base de connaissance', href: '/support/faq', permission: 'support.faq', icon: HelpCircle },
                { label: 'Contact support', href: '/support/contact', permission: 'support.tickets.create', icon: MessageCircle },
                { label: 'Statut système', href: '/support/status', permission: 'support.admin', icon: Settings },
            ]
        },
        {
            key: 'reports',
            label: 'Rapports & Analytics',
            icon: BarChart,
            permissions: ['reports.view', 'reports.export', 'analytics.view'],
            items: [
                { label: 'Rapports globaux', href: '#', permission: 'reports.view', icon: BarChart },
                { label: 'Statistiques ventes', href: '#', permission: 'analytics.view', icon: TrendingUp },
                { label: 'Performance sellers', href: '#', permission: 'analytics.view', icon: Users },
                { label: 'Exports (PDF / Excel)', href: '#', permission: 'reports.export', icon: Download },
            ]
        },
        {
            key: 'access',
            label: 'Utilisateurs & Accès',
            icon: Users,
            permissions: ['access.manage', 'admin.users.view', 'access.roles.view', 'admin.access.permissions.view', 'access.permissions.view'],
            items: [
                { label: 'Utilisateurs', href: '/admin/users', permission: 'admin.users.view', icon: User },
                { label: 'Rôles', href: '/admin/access-manager/roles', permission: 'access.roles.view', icon: UserCog },
                { label: 'Permissions', href: '/admin/access-manager/permissions', permission: 'admin.access.permissions.view', icon: Lock },
                { label: 'Access Mode', href: '#', permission: 'access.manage', icon: Settings },
            ]
        },
        {
            key: 'settings',
            label: 'Paramètres',
            icon: Settings,
            permissions: ['settings.view', 'settings.update', 'settings.branding', 'settings.ui', 'settings.currency.view', 'settings.settings.currency.view', 'admin.billing.manage', 'settings.mail.manage'],
            items: [
                { label: 'Paramètres boutique', href: '/settings', permission: 'settings.view', icon: Building },
                { label: 'Gestion des devises', href: '/settings/currencies', permission: 'settings.currency.view|settings.settings.currency.view', icon: DollarSign },
                { label: 'Branding (logo, couleurs)', href: '/admin/branding', permission: 'settings.branding', icon: Palette },
                { label: 'Configuration Mail', href: '/admin/mail-settings', permission: 'settings.mail.manage', icon: MailIcon },
                { label: 'Plans & Limitations', href: '/admin/billing/plans', permission: 'admin.billing.manage', icon: CreditCard },
                { label: 'Transactions (abonnements)', href: '/admin/billing/transactions', permission: 'admin.billing.manage', icon: Scroll },
                { label: 'Préférences UI', href: '#', permission: 'settings.ui', icon: Palette },
                { label: 'Referral / Parrainage', href: '/referrals/settings', permission: 'referral.settings.view|referral.settings.manage', icon: Users },
            ]
        },
        {
            key: 'referral',
            label: 'Referral',
            icon: Users,
            permissions: ['referral.view', 'referral.stats.view'],
            items: [
                { label: 'Mon programme', href: '/referrals', permission: 'referral.view|referral.stats.view', icon: Users },
            ]
        },
        {
            key: 'logs',
            label: 'Logs & Audit',
            icon: Scroll,
            permissions: ['logs.system', 'logs.actions', 'logs.connections'],
            items: [
                { label: 'Logs système', href: '/logs/system', permission: 'logs.system', icon: ClipboardList },
                { label: 'Historique des actions', href: '/logs/actions', permission: 'logs.actions', icon: Scroll },
                { label: 'Connexions utilisateurs', href: '/logs/connections', permission: 'logs.connections', icon: Lock },
            ]
        },
        {
            key: 'crm',
            label: 'CRM Admin',
            icon: BarChart,
            permissions: ['crm.dashboard.view'],
            items: [
                { label: 'Dashboard CRM', href: '/admin/crm', permission: 'crm.dashboard.view', icon: BarChart },
                { label: 'Support Chat', href: '/support/admin/chat', permission: 'crm.dashboard.view|support.admin', icon: MessageCircle },
            ]
        },
    ];

    // Filtrer les groupes visibles (Général toujours visible ; Pharmacy/Hardware/Commerce/Ecommerce via hasXxxAccess)
    const visibleGroups = navigationGroups.filter((group) => {
        // Pour le ROOT, masquer les menus de modules métiers (Pharmacy, Hardware, Global Commerce, E-commerce)
        if (isRoot && ['pharmacy', 'hardware', 'global_commerce', 'ecommerce'].includes(group.key)) {
            return false;
        }
        if (group.key === 'general') return true;
        if (group.key === 'pharmacy') return hasPharmacyAccess();
        if (group.key === 'hardware') return hasHardwareAccess();
        if (group.key === 'global_commerce') return hasCommerceModuleAccess();
        // E-commerce est géré séparément si un groupe existe
        if (group.key === 'ecommerce') return hasEcommerceModuleAccess();
        return hasVisibleItem(group.permissions);
    });

    const filterItemsByFeatures = (group) => group;

    const isItemPlanLocked = (groupKey, item) => {
        const href = item?.href || '';
        if (!href || href === '#') return false;

        if (groupKey === 'ecommerce' && planFeatures.ecommerce_module === false) return true;
        if (groupKey === 'pharmacy' && planFeatures.pharmacy_module === false) return true;
        if (groupKey === 'global_commerce' && planFeatures.commerce_module === false) return true;
        if (groupKey === 'hardware' && planFeatures.hardware_module === false) return true;

        if (href === '/ecommerce/payments' && featureFlags.api_payments === false) return true;
        if (href === '/ecommerce/reports' && featureFlags.analytics_advanced === false) return true;
        if (href === '/ecommerce/catalog' && planFeatures.ecommerce_catalog === false) return true;
        if (href === '/ecommerce/orders' && planFeatures.ecommerce_orders === false) return true;
        if ((href === '/ecommerce/promotions' || href === '/ecommerce/coupons') && planFeatures.ecommerce_promotions === false) return true;

        if ((href === '/commerce/depots' || href === '/pharmacy/depots' || href === '/hardware/depots') && planFeatures.multi_depot === false) return true;

        return false;
    };

    return (
        <>
            {/* Sidebar Desktop - Fixe */}
            <aside className="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col sidebar-scrollbar">
                <div className="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 px-6 pb-4">
                    {/* Logo */}
                    <div className="flex h-16 shrink-0 items-center">
                        <Link href="/" className="flex items-center space-x-2 group">
                            {appLogoUrl ? (
                                <div className="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden shadow-lg group-hover:shadow-xl transition-shadow">
                                    <img
                                        src={appLogoUrl}
                                        alt="OmniPOS"
                                        className="max-w-full max-h-full object-contain"
                                    />
                                </div>
                            ) : (
                                <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                                    <span className="text-white font-bold text-lg">OP</span>
                                </div>
                            )}
                            <span className="text-xl font-bold text-gray-900 dark:text-white">OmniPOS</span>
                        </Link>
                    </div>

                    {/* Navigation Groups */}
                    <nav data-onboarding="module-sidebar-nav" className="flex flex-1 flex-col" aria-label="Menu principal">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            {visibleGroups.map((group) => {
                                group = filterItemsByFeatures(group);
                                const isExpanded = expandedGroups[group.key] ?? true;
                                // Pour Pharmacy, Hardware, Global Commerce et E-commerce : si l'utilisateur a accès au module, afficher tous les items
                                const hasModuleAccess = (group.key === 'pharmacy' && hasPharmacyAccess()) || 
                                                       (group.key === 'hardware' && hasHardwareAccess()) ||
                                                       (group.key === 'global_commerce' && hasCommerceModuleAccess()) ||
                                                       (group.key === 'ecommerce' && hasEcommerceModuleAccess());
                                
                                const visibleItems = group.items.filter((item) => {
                                    if (item.header) return true;
                                    if (item.rootOnly && !isRoot) return false;
                                    // Exclure les items pour certains secteurs
                                    if (item.excludeSectors && item.excludeSectors.includes(tenantSector)) return false;
                                    // Si l'utilisateur a accès au module, afficher tous les items
                                    if (hasModuleAccess) return true;
                                    // Sinon, vérifier les permissions individuelles
                                    if (!hasItemPermission(item.permission)) return false;
                                    return true;
                                });

                                if (visibleItems.length === 0) return null;

                                return (
                                    <li key={group.key}>
                                        <div className="flex items-center gap-2 text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                            {group.icon && <group.icon className="h-4 w-4" />}
                                            {group.label}
                                        </div>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {visibleItems.map((item) => {
                                                if (item.header) {
                                                    return (
                                                        <li key={item.label} className="pt-3 mt-2 border-t border-gray-200 dark:border-gray-600 first:border-0 first:pt-0 first:mt-0">
                                                            <div className="px-2 py-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{item.label}</div>
                                                        </li>
                                                    );
                                                }
                                                const IconComponent = item.icon;
                                                const isActive = isActiveRoute(item.href);
                                                const isLocked = isItemPlanLocked(group.key, item);
                                                return (
                                                    <li key={item.label}>
                                                        <div
                                                            className={`group flex items-center justify-between gap-x-2 rounded-lg p-2 text-sm leading-6 font-medium transition-colors ${
                                                                isLocked
                                                                    ? 'bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                                    : (isActive
                                                                        ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                                                        : 'text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-gray-50 dark:hover:bg-gray-700')
                                                            }`}
                                                        >
                                                            {isLocked ? (
                                                                <div className="flex items-center gap-x-3 min-w-0 opacity-80">
                                                                    {IconComponent && (
                                                                        <IconComponent className="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                                                                    )}
                                                                    <span className="blur-[1px] select-none">{item.label}</span>
                                                                </div>
                                                            ) : (
                                                                <Link href={item.href} className="flex items-center gap-x-3 min-w-0 w-full">
                                                                    {IconComponent && (
                                                                        <IconComponent 
                                                                            className={`h-5 w-5 shrink-0 ${
                                                                                isActive 
                                                                                    ? 'text-amber-600 dark:text-amber-400' 
                                                                                    : 'text-gray-400 dark:text-gray-500 group-hover:text-amber-600 dark:group-hover:text-amber-400'
                                                                            }`} 
                                                                        />
                                                                    )}
                                                                    <span>{item.label}</span>
                                                                </Link>
                                                            )}
                                                            {isLocked ? (
                                                                <span className="inline-flex rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 text-[10px] font-semibold">
                                                                    Monter niveau
                                                                </span>
                                                            ) : null}
                                                        </div>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>

                    {/* Footer plan indicator */}
                    {billingSummary && (
                        <div className="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                                <p className="text-xs text-amber-700 dark:text-amber-300 font-semibold">
                                    Plan: {billingSummary.plan_name}
                                </p>
                                <p className="text-[11px] text-amber-700/90 dark:text-amber-300/90 mt-1">
                                    Expire le: {planExpiryLabel}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </aside>

            {/* Sidebar Mobile - Drawer */}
            <aside
                className={`fixed inset-y-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out lg:hidden ${
                    isOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-full flex-col gap-y-5 overflow-y-auto px-6 pb-4">
                    {/* Header avec bouton fermer */}
                    <div className="flex h-16 shrink-0 items-center justify-between">
                        <Link href="/" className="flex items-center space-x-2">
                            {appLogoUrl ? (
                                <div className="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden shadow-lg">
                                    <img
                                        src={appLogoUrl}
                                        alt="OmniPOS"
                                        className="max-w-full max-h-full object-contain"
                                    />
                                </div>
                            ) : (
                                <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <span className="text-white font-bold text-lg">OP</span>
                                </div>
                            )}
                            <span className="text-xl font-bold text-gray-900 dark:text-white">OmniPOS</span>
                        </Link>
                        <button
                            onClick={onClose}
                            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {billingSummary && (
                        <div className="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                            <p className="text-xs text-amber-700 dark:text-amber-300 font-semibold">
                                Plan: {billingSummary.plan_name}
                            </p>
                            <p className="text-[11px] text-amber-700/90 dark:text-amber-300/90 mt-1">
                                Expire le: {planExpiryLabel}
                            </p>
                            <p className="text-[11px] text-amber-700/90 dark:text-amber-300/90 mt-1">
                                Produits: {billingSummary.products_used}/{billingSummary.products_limit ?? 'illimite'}
                            </p>
                            <p className="text-[11px] text-amber-700/90 dark:text-amber-300/90">
                                Utilisateurs: {billingSummary.users_used}/{billingSummary.users_limit ?? 'illimite'}
                            </p>
                        </div>
                    )}

                    {/* Navigation Groups */}
                    <nav data-onboarding="module-sidebar-nav" className="flex flex-1 flex-col" aria-label="Menu principal">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            {visibleGroups.map((group) => {
                                group = filterItemsByFeatures(group);
                                // Pour Pharmacy, Hardware, Global Commerce et E-commerce : si l'utilisateur a accès au module, afficher tous les items
                                const hasModuleAccess = (group.key === 'pharmacy' && hasPharmacyAccess()) || 
                                                       (group.key === 'hardware' && hasHardwareAccess()) ||
                                                       (group.key === 'global_commerce' && hasCommerceModuleAccess()) ||
                                                       (group.key === 'ecommerce' && hasEcommerceModuleAccess());
                                
                                const visibleItems = group.items.filter((item) => {
                                    if (item.header) return true;
                                    if (item.rootOnly && !isRoot) return false;
                                    // Exclure les items pour certains secteurs
                                    if (item.excludeSectors && item.excludeSectors.includes(tenantSector)) return false;
                                    // Si l'utilisateur a accès au module, afficher tous les items
                                    if (hasModuleAccess) return true;
                                    // Sinon, vérifier les permissions individuelles
                                    if (!hasItemPermission(item.permission)) return false;
                                    return true;
                                });

                                if (visibleItems.length === 0) return null;

                                return (
                                    <li key={group.key}>
                                        <div className="flex items-center gap-2 text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                            {group.icon && <group.icon className="h-4 w-4" />}
                                            {group.label}
                                        </div>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {visibleItems.map((item) => {
                                                if (item.header) {
                                                    return (
                                                        <li key={item.label} className="pt-3 mt-2 border-t border-gray-200 dark:border-gray-600 first:border-0 first:pt-0 first:mt-0">
                                                            <div className="px-2 py-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{item.label}</div>
                                                        </li>
                                                    );
                                                }
                                                const IconComponent = item.icon;
                                                const isActive = isActiveRoute(item.href);
                                                const isLocked = isItemPlanLocked(group.key, item);
                                                return (
                                                    <li key={item.label}>
                                                        <div
                                                            className={`group flex items-center justify-between gap-x-2 rounded-lg p-3 text-sm leading-6 font-medium transition-colors ${
                                                                isLocked
                                                                    ? 'bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                                    : (isActive
                                                                        ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                                                        : 'text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-gray-50 dark:hover:bg-gray-700')
                                                            }`}
                                                        >
                                                            {isLocked ? (
                                                                <div className="flex items-center gap-x-3 min-w-0 opacity-80">
                                                                    {IconComponent && <IconComponent className="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />}
                                                                    <span className="blur-[1px] select-none">{item.label}</span>
                                                                </div>
                                                            ) : (
                                                                <Link href={item.href} onClick={onClose} className="flex items-center gap-x-3 min-w-0 w-full">
                                                                    {IconComponent && (
                                                                        <IconComponent 
                                                                            className={`h-5 w-5 shrink-0 ${
                                                                                isActive 
                                                                                    ? 'text-amber-600 dark:text-amber-400' 
                                                                                    : 'text-gray-400 dark:text-gray-500 group-hover:text-amber-600 dark:group-hover:text-amber-400'
                                                                            }`} 
                                                                        />
                                                                    )}
                                                                    <span>{item.label}</span>
                                                                </Link>
                                                            )}
                                                            {isLocked ? (
                                                                <span className="inline-flex rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 text-[10px] font-semibold">
                                                                    Monter niveau
                                                                </span>
                                                            ) : null}
                                                        </div>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                </div>
            </aside>
        </>
    );
}

