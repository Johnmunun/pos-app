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
} from 'lucide-react';

/**
 * Component: Sidebar
 * 
 * Sidebar groupée avec visibilité basée sur les permissions
 * Mobile-first : drawer/off-canvas sur mobile, fixe sur desktop
 */
export default function Sidebar({ permissions, isRoot = false, isOpen, onClose, currentUrl }) {
    const [expandedGroups, setExpandedGroups] = useState({});
    
    // Utiliser l'URL depuis usePage si currentUrl n'est pas fourni
    const page = usePage();
    const url = currentUrl || page.url;
    
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

    // Définition des groupes de navigation
    const navigationGroups = [
        {
            key: 'general',
            label: 'Général',
            icon: Home,
            permissions: ['dashboard.view', 'notifications.view', 'activity.view', '*'], // '*' pour toujours afficher le groupe
            items: [
                { label: 'Dashboard', href: '/dashboard', permission: '*', icon: LayoutDashboard }, // Accessible à tous
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
            permissions: ['module.pharmacy', 'pharmacy.pharmacy.product.manage', 'pharmacy.product.manage', 'pharmacy.category.view', 'pharmacy.pharmacy.stock.manage', 'stock.view', 'inventory.view', 'pharmacy.sales.view', 'pharmacy.sales.manage', 'pharmacy.purchases.view', 'pharmacy.purchases.manage', 'pharmacy.supplier.view', 'pharmacy.customer.view', 'pharmacy.expiration.view', 'pharmacy.batch.view', 'pharmacy.pharmacy.report.view', 'admin.modules.view'],
            items: [
                { label: 'Dashboard', href: '/pharmacy/dashboard', permission: 'module.pharmacy', icon: LayoutDashboard },
                { label: 'Produits', href: '/pharmacy/products', permission: 'pharmacy.pharmacy.product.manage|pharmacy.product.manage', icon: Package },
                { label: 'Catégories', href: '/pharmacy/categories', permission: 'pharmacy.category.view', icon: Tag },
                { label: 'Stock', href: '/pharmacy/stock', permission: 'pharmacy.pharmacy.stock.manage|stock.view', icon: BarChart },
                { label: 'Inventaires', href: '/pharmacy/inventories', permission: 'inventory.view', icon: ClipboardList },
                { label: 'Expirations', href: '/pharmacy/expirations', permission: 'pharmacy.expiration.view|pharmacy.batch.view', icon: Calendar },
                { label: 'Ventes', href: '/pharmacy/sales', permission: 'pharmacy.sales.view|pharmacy.sales.manage|pharmacy.pharmacy.sale.create', icon: ShoppingCart },
                { label: 'Achats', href: '/pharmacy/purchases', permission: 'pharmacy.purchases.view|pharmacy.purchases.manage', icon: Receipt },
                { label: 'Fournisseurs', href: '/pharmacy/suppliers', permission: 'pharmacy.supplier.view', icon: Truck },
                { label: 'Clients', href: '/pharmacy/customers', permission: 'pharmacy.customer.view', icon: Users },
                { label: 'Rapports', href: '/pharmacy/reports', permission: 'pharmacy.pharmacy.report.view', icon: FileText },
            ]
        },
        {
            key: 'payments',
            label: 'Paiements & Finance',
            icon: CreditCard,
            permissions: ['payments.view', 'payments.methods', 'finance.reports'],
            items: [
                { label: 'Méthodes de paiement', href: '#', permission: 'payments.methods', icon: CreditCard },
                { label: 'Historique financier', href: '#', permission: 'payments.view', icon: Scroll },
                { label: 'Rapports financiers', href: '#', permission: 'finance.reports', icon: BarChart },
            ]
        },
        {
            key: 'support',
            label: 'IT / Support',
            icon: LifeBuoy,
            permissions: ['support.tickets.create', 'support.tickets.view', 'support.admin', 'support.faq'],
            items: [
                { label: 'Créer un ticket', href: '#', permission: 'support.tickets.create', icon: Plus },
                { label: 'Mes tickets', href: '#', permission: 'support.tickets.view', icon: Ticket },
                { label: 'Tous les tickets', href: '#', permission: 'support.admin', icon: ClipboardList },
                { label: 'Incidents', href: '#', permission: 'support.admin', icon: AlertCircle },
                { label: 'FAQ / Base de connaissance', href: '#', permission: 'support.faq', icon: HelpCircle },
                { label: 'Contact support', href: '#', permission: 'support.tickets.create', icon: MessageCircle },
                { label: 'Statut système', href: '#', permission: 'support.admin', icon: Settings },
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
            permissions: ['settings.view', 'settings.update', 'settings.branding', 'settings.ui', 'settings.currency.view', 'settings.settings.currency.view'],
            items: [
                { label: 'Paramètres boutique', href: '/settings', permission: 'settings.view', icon: Building },
                { label: 'Gestion des devises', href: '/settings/currencies', permission: 'settings.currency.view|settings.settings.currency.view', icon: DollarSign },
                { label: 'Branding (logo, couleurs)', href: '#', permission: 'settings.branding', icon: Palette },
                { label: 'Préférences UI', href: '#', permission: 'settings.ui', icon: Palette },
            ]
        },
        {
            key: 'logs',
            label: 'Logs & Audit',
            icon: Scroll,
            permissions: ['logs.system', 'logs.actions', 'logs.connections'],
            items: [
                { label: 'Logs système', href: '#', permission: 'logs.system', icon: ClipboardList },
                { label: 'Historique des actions', href: '#', permission: 'logs.actions', icon: Scroll },
                { label: 'Connexions utilisateurs', href: '#', permission: 'logs.connections', icon: Lock },
            ]
        },
    ];

    // Filtrer les groupes visibles
    const visibleGroups = navigationGroups.filter(group => 
        hasVisibleItem(group.permissions)
    );

    return (
        <>
            {/* Sidebar Desktop - Fixe */}
            <aside className="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col sidebar-scrollbar">
                <div className="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 px-6 pb-4">
                    {/* Logo */}
                    <div className="flex h-16 shrink-0 items-center">
                        <Link href="/" className="flex items-center space-x-2 group">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                                <span className="text-white font-bold text-lg">POS</span>
                            </div>
                            <span className="text-xl font-bold text-gray-900 dark:text-white">POS SaaS</span>
                        </Link>
                    </div>

                    {/* Navigation Groups */}
                    <nav className="flex flex-1 flex-col">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            {visibleGroups.map((group) => {
                                const isExpanded = expandedGroups[group.key] ?? true;
                                const visibleItems = group.items.filter(item => hasItemPermission(item.permission));

                                if (visibleItems.length === 0) return null;

                                return (
                                    <li key={group.key}>
                                        <div className="flex items-center gap-2 text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                            {group.icon && <group.icon className="h-4 w-4" />}
                                            {group.label}
                                        </div>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {visibleItems.map((item) => {
                                                const IconComponent = item.icon;
                                                const isActive = isActiveRoute(item.href);
                                                return (
                                                    <li key={item.label}>
                                                        <Link
                                                            href={item.href}
                                                            className={`group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-colors ${
                                                                isActive
                                                                    ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                                                    : 'text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                            }`}
                                                        >
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
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span className="text-white font-bold text-lg">POS</span>
                            </div>
                            <span className="text-xl font-bold text-gray-900 dark:text-white">POS SaaS</span>
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

                    {/* Navigation Groups */}
                    <nav className="flex flex-1 flex-col">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            {visibleGroups.map((group) => {
                                const visibleItems = group.items.filter(item => hasItemPermission(item.permission));

                                if (visibleItems.length === 0) return null;

                                return (
                                    <li key={group.key}>
                                        <div className="flex items-center gap-2 text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                            {group.icon && <group.icon className="h-4 w-4" />}
                                            {group.label}
                                        </div>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {visibleItems.map((item) => {
                                                const IconComponent = item.icon;
                                                const isActive = isActiveRoute(item.href);
                                                return (
                                                    <li key={item.label}>
                                                        <Link
                                                            href={item.href}
                                                            onClick={onClose}
                                                            className={`group flex gap-x-3 rounded-lg p-3 text-sm leading-6 font-medium transition-colors ${
                                                                isActive
                                                                    ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                                                    : 'text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                            }`}
                                                        >
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

