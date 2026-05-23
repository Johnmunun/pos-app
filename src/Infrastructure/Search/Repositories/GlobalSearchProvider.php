<?php

namespace Src\Infrastructure\Search\Repositories;

use Src\Application\GlobalSearch\Repositories\GlobalSearchRepositoryInterface;
use Src\Domains\GlobalSearch\ValueObjects\GlobalSearchItem;

/**
 * Provider GlobalSearchProvider
 *
 * Déclare toutes les pages recherchables du système.
 * Chaque entrée est associée à :
 * - une route Laravel existante
 * - une permission existante (optionnelle)
 * - un module
 *
 * @package Infrastructure\Search\Repositories
 */
class GlobalSearchProvider implements GlobalSearchRepositoryInterface
{
    /**
     * Récupère tous les items recherchables
     *
     * @return array<GlobalSearchItem>
     */
    public function getAllSearchableItems(): array
    {
        return [
            // ============================================
            // MODULE: ACCESS (Rôles & Permissions)
            // ============================================
            new GlobalSearchItem(
                label: 'Permissions',
                description: 'Gérer les permissions du système',
                routeName: 'admin.access.permissions',
                requiredPermission: 'access.permissions.view',
                module: 'Access',
                icon: 'Shield'
            ),
            new GlobalSearchItem(
                label: 'Rôles',
                description: 'Gérer les rôles et leurs permissions (Roles, Gestion rôles)',
                routeName: 'admin.access.roles',
                requiredPermission: 'access.roles.view',
                module: 'Access',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Roles',
                description: 'Manage roles and their permissions (Rôles, Gestion rôles, role)',
                routeName: 'admin.access.roles',
                requiredPermission: 'access.roles.view',
                module: 'Access',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Role',
                description: 'Gestion des rôles utilisateurs (Roles, Rôles)',
                routeName: 'admin.access.roles',
                requiredPermission: 'access.roles.view',
                module: 'Access',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Gestion Rôles',
                description: 'Gérer les rôles, permissions et accès',
                routeName: 'admin.access.roles',
                requiredPermission: 'access.roles.view',
                module: 'Access',
                icon: 'Users'
            ),

            // ============================================
            // MODULE: ADMIN (Administration)
            // ============================================
            new GlobalSearchItem(
                label: 'Tenants',
                description: 'Gérer les tenants et leurs statuts',
                routeName: 'admin.tenants.view',
                requiredPermission: 'admin.tenants.view',
                module: 'Admin',
                icon: 'Building2'
            ),
            new GlobalSearchItem(
                label: 'Utilisateurs',
                description: 'Gérer tous les utilisateurs du système (Users, Gestion utilisateurs)',
                routeName: 'admin.users.view',
                requiredPermission: 'admin.users.view',
                module: 'Admin',
                icon: 'UserCog'
            ),
            new GlobalSearchItem(
                label: 'Users',
                description: 'Manage all system users (Utilisateurs, Gestion utilisateurs)',
                routeName: 'admin.users.view',
                requiredPermission: 'admin.users.view',
                module: 'Admin',
                icon: 'UserCog'
            ),
            new GlobalSearchItem(
                label: 'Gestion Utilisateurs',
                description: 'Gérer les utilisateurs, rôles et permissions',
                routeName: 'admin.users.view',
                requiredPermission: 'admin.users.view',
                module: 'Admin',
                icon: 'UserCog'
            ),
            new GlobalSearchItem(
                label: 'Sélectionner Tenant',
                description: 'Changer de tenant (ROOT uniquement)',
                routeName: 'admin.tenants.select.view',
                requiredPermission: null, // ROOT uniquement via middleware
                module: 'Admin',
                icon: 'ArrowRightLeft'
            ),

            // ============================================
            // MODULE: PHARMACY (Pharmacie)
            // ============================================
            new GlobalSearchItem(
                label: 'Dashboard Pharmacie',
                description: 'Tableau de bord du module pharmacie',
                routeName: 'pharmacy.dashboard',
                requiredPermission: 'module.pharmacy',
                module: 'Pharmacy',
                icon: 'LayoutDashboard'
            ),
            new GlobalSearchItem(
                label: 'Produits Pharmacie',
                description: 'Liste et gestion des produits (pharmacie, stock)',
                routeName: 'pharmacy.products',
                requiredPermission: 'pharmacy.product.manage',
                module: 'Pharmacy',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Créer Produit Pharmacie',
                description: 'Ajouter un nouveau produit en pharmacie',
                routeName: 'pharmacy.products.create',
                requiredPermission: 'pharmacy.product.manage',
                module: 'Pharmacy',
                icon: 'PlusCircle'
            ),
            new GlobalSearchItem(
                label: 'Catégories Pharmacie',
                description: 'Catégories de produits pharmacie',
                routeName: 'pharmacy.categories.index',
                requiredPermission: 'pharmacy.category.view',
                module: 'Pharmacy',
                icon: 'FolderTree'
            ),
            new GlobalSearchItem(
                label: 'Stock Pharmacie',
                description: 'Gestion du stock et alertes',
                routeName: 'pharmacy.stock.index',
                requiredPermission: 'stock.view',
                module: 'Pharmacy',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Mouvements Stock Pharmacie',
                description: 'Historique des mouvements de stock',
                routeName: 'pharmacy.stock.movements.index',
                requiredPermission: 'stock.view',
                module: 'Pharmacy',
                icon: 'ArrowLeftRight'
            ),
            new GlobalSearchItem(
                label: 'Inventaires Pharmacie',
                description: 'Inventaires et comptages',
                routeName: 'pharmacy.inventories.index',
                requiredPermission: 'inventory.view',
                module: 'Pharmacy',
                icon: 'ClipboardList'
            ),
            new GlobalSearchItem(
                label: 'Expirations Pharmacie',
                description: 'Lots et dates d\'expiration',
                routeName: 'pharmacy.expirations.index',
                requiredPermission: 'pharmacy.expiration.view',
                module: 'Pharmacy',
                icon: 'Calendar'
            ),
            new GlobalSearchItem(
                label: 'Ventes Pharmacie',
                description: 'Liste des ventes pharmacie',
                routeName: 'pharmacy.sales.index',
                requiredPermission: 'pharmacy.sales.view',
                module: 'Pharmacy',
                icon: 'ShoppingCart'
            ),
            new GlobalSearchItem(
                label: 'Caisse Pharmacie',
                description: 'Point de vente / caisse pharmacie (POS)',
                routeName: 'pharmacy.sales.create',
                requiredPermission: 'pharmacy.sales.manage',
                module: 'Pharmacy',
                icon: 'Plus'
            ),
            new GlobalSearchItem(
                label: 'Achats Pharmacie',
                description: 'Bons de commande et achats fournisseurs',
                routeName: 'pharmacy.purchases.index',
                requiredPermission: 'pharmacy.purchases.view',
                module: 'Pharmacy',
                icon: 'Receipt'
            ),
            new GlobalSearchItem(
                label: 'Fournisseurs Pharmacie',
                description: 'Gestion des fournisseurs',
                routeName: 'pharmacy.suppliers.index',
                requiredPermission: 'pharmacy.supplier.view',
                module: 'Pharmacy',
                icon: 'Truck'
            ),
            new GlobalSearchItem(
                label: 'Clients Pharmacie',
                description: 'Liste des clients pharmacie',
                routeName: 'pharmacy.customers.index',
                requiredPermission: 'pharmacy.customer.view',
                module: 'Pharmacy',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Vendeurs Pharmacie',
                description: 'Gestion des vendeurs pharmacie',
                routeName: 'pharmacy.sellers.index',
                requiredPermission: 'pharmacy.seller.view',
                module: 'Pharmacy',
                icon: 'User'
            ),
            new GlobalSearchItem(
                label: 'Dépôts Pharmacie',
                description: 'Entrepôts et dépôts',
                routeName: 'pharmacy.depots.index',
                requiredPermission: 'pharmacy.seller.view',
                module: 'Pharmacy',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Transferts Pharmacie',
                description: 'Transferts inter-dépôts',
                routeName: 'pharmacy.transfers.index',
                requiredPermission: 'transfer.view',
                module: 'Pharmacy',
                icon: 'ArrowLeftRight'
            ),
            new GlobalSearchItem(
                label: 'Caisses Pharmacie',
                description: 'Caisses enregistreuses et sessions',
                routeName: 'pharmacy.cash-registers.index',
                requiredPermission: 'pharmacy.sales.view',
                module: 'Pharmacy',
                icon: 'CreditCard'
            ),
            new GlobalSearchItem(
                label: 'Rapports Pharmacie',
                description: 'Rapports et statistiques pharmacie',
                routeName: 'pharmacy.reports.index',
                requiredPermission: 'pharmacy.report.view',
                module: 'Pharmacy',
                icon: 'FileText'
            ),
            new GlobalSearchItem(
                label: 'Assistant IA Pharmacie',
                description: 'Chatbot assistant intelligent pharmacie',
                routeName: 'pharmacy.dashboard',
                requiredPermission: 'module.pharmacy',
                module: 'Pharmacy',
                icon: 'Sparkles'
            ),

            // ============================================
            // MODULE: GLOBAL COMMERCE
            // ============================================
            new GlobalSearchItem(
                label: 'Dashboard Commerce',
                description: 'Tableau de bord Global Commerce',
                routeName: 'commerce.dashboard',
                requiredPermission: 'module.commerce',
                module: 'Global Commerce',
                icon: 'LayoutDashboard'
            ),
            new GlobalSearchItem(
                label: 'Produits Commerce',
                description: 'Catalogue produits commerce / supermarché',
                routeName: 'commerce.products.index',
                requiredPermission: 'commerce.product.view',
                module: 'Global Commerce',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Créer Produit Commerce',
                description: 'Ajouter un produit commerce',
                routeName: 'commerce.products.create',
                requiredPermission: 'commerce.product.manage',
                module: 'Global Commerce',
                icon: 'PlusCircle'
            ),
            new GlobalSearchItem(
                label: 'Catégories Commerce',
                description: 'Catégories produits commerce',
                routeName: 'commerce.categories.index',
                requiredPermission: 'commerce.category.view',
                module: 'Global Commerce',
                icon: 'Tag'
            ),
            new GlobalSearchItem(
                label: 'Stock Commerce',
                description: 'Stock et niveaux commerce',
                routeName: 'commerce.stock.index',
                requiredPermission: 'commerce.stock.view',
                module: 'Global Commerce',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Inventaires Commerce',
                description: 'Inventaires commerce',
                routeName: 'commerce.inventories.index',
                requiredPermission: 'commerce.inventory.view',
                module: 'Global Commerce',
                icon: 'ClipboardList'
            ),
            new GlobalSearchItem(
                label: 'Transferts Commerce',
                description: 'Transferts de stock commerce',
                routeName: 'commerce.transfers.index',
                requiredPermission: 'commerce.transfer.view',
                module: 'Global Commerce',
                icon: 'ArrowLeftRight'
            ),
            new GlobalSearchItem(
                label: 'Ventes Commerce',
                description: 'Historique des ventes commerce',
                routeName: 'commerce.sales.index',
                requiredPermission: 'commerce.sales.view',
                module: 'Global Commerce',
                icon: 'ShoppingCart'
            ),
            new GlobalSearchItem(
                label: 'Caisse Commerce',
                description: 'Point de vente / caisse commerce (POS)',
                routeName: 'commerce.sales.create',
                requiredPermission: 'commerce.sales.manage',
                module: 'Global Commerce',
                icon: 'Plus'
            ),
            new GlobalSearchItem(
                label: 'Achats Commerce',
                description: 'Achats et réceptions commerce',
                routeName: 'commerce.purchases.index',
                requiredPermission: 'commerce.purchases.view',
                module: 'Global Commerce',
                icon: 'Receipt'
            ),
            new GlobalSearchItem(
                label: 'Fournisseurs Commerce',
                description: 'Fournisseurs commerce',
                routeName: 'commerce.suppliers.index',
                requiredPermission: 'commerce.supplier.view',
                module: 'Global Commerce',
                icon: 'Truck'
            ),
            new GlobalSearchItem(
                label: 'Clients Commerce',
                description: 'Clients commerce',
                routeName: 'commerce.customers.index',
                requiredPermission: 'commerce.customer.view',
                module: 'Global Commerce',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Vendeurs Commerce',
                description: 'Gestion vendeurs commerce / global commerce',
                routeName: 'commerce.sellers.index',
                requiredPermission: 'commerce.seller.view',
                module: 'Global Commerce',
                icon: 'User'
            ),
            new GlobalSearchItem(
                label: 'Dépôts Commerce',
                description: 'Dépôts et entrepôts commerce',
                routeName: 'commerce.depots.index',
                requiredPermission: 'commerce.depot.view',
                module: 'Global Commerce',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Rapports Commerce',
                description: 'Rapports commerce et exports',
                routeName: 'commerce.reports.index',
                requiredPermission: 'commerce.report.view',
                module: 'Global Commerce',
                icon: 'BarChart'
            ),

            // ============================================
            // MODULE: HARDWARE (Quincaillerie)
            // ============================================
            new GlobalSearchItem(
                label: 'Dashboard Quincaillerie',
                description: 'Tableau de bord hardware / quincaillerie',
                routeName: 'hardware.dashboard',
                requiredPermission: 'module.hardware',
                module: 'Quincaillerie',
                icon: 'LayoutDashboard'
            ),
            new GlobalSearchItem(
                label: 'Produits Quincaillerie',
                description: 'Produits quincaillerie',
                routeName: 'hardware.products',
                requiredPermission: 'hardware.product.view',
                module: 'Quincaillerie',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Créer Produit Quincaillerie',
                description: 'Nouveau produit quincaillerie',
                routeName: 'hardware.products.create',
                requiredPermission: 'hardware.product.manage',
                module: 'Quincaillerie',
                icon: 'PlusCircle'
            ),
            new GlobalSearchItem(
                label: 'Catégories Quincaillerie',
                description: 'Catégories quincaillerie',
                routeName: 'hardware.categories.index',
                requiredPermission: 'hardware.category.view',
                module: 'Quincaillerie',
                icon: 'Tag'
            ),
            new GlobalSearchItem(
                label: 'Stock Quincaillerie',
                description: 'Stock quincaillerie',
                routeName: 'hardware.stock.index',
                requiredPermission: 'hardware.stock.view',
                module: 'Quincaillerie',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Mouvements Stock Quincaillerie',
                description: 'Mouvements de stock quincaillerie',
                routeName: 'hardware.stock.movements.index',
                requiredPermission: 'hardware.stock.movement.view',
                module: 'Quincaillerie',
                icon: 'Scroll'
            ),
            new GlobalSearchItem(
                label: 'Ventes Quincaillerie',
                description: 'Ventes quincaillerie',
                routeName: 'hardware.sales.index',
                requiredPermission: 'hardware.sales.view',
                module: 'Quincaillerie',
                icon: 'ShoppingCart'
            ),
            new GlobalSearchItem(
                label: 'Caisse Quincaillerie',
                description: 'Caisse / POS quincaillerie',
                routeName: 'hardware.sales.create',
                requiredPermission: 'hardware.sales.manage',
                module: 'Quincaillerie',
                icon: 'Plus'
            ),
            new GlobalSearchItem(
                label: 'Clients Quincaillerie',
                description: 'Clients quincaillerie',
                routeName: 'hardware.customers.index',
                requiredPermission: 'hardware.customer.view',
                module: 'Quincaillerie',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Vendeurs Quincaillerie',
                description: 'Vendeurs quincaillerie',
                routeName: 'hardware.sellers.index',
                requiredPermission: 'hardware.seller.view',
                module: 'Quincaillerie',
                icon: 'User'
            ),
            new GlobalSearchItem(
                label: 'Fournisseurs Quincaillerie',
                description: 'Fournisseurs quincaillerie',
                routeName: 'hardware.suppliers.index',
                requiredPermission: 'hardware.supplier.view',
                module: 'Quincaillerie',
                icon: 'Truck'
            ),
            new GlobalSearchItem(
                label: 'Achats Quincaillerie',
                description: 'Bons de commande quincaillerie',
                routeName: 'hardware.purchases.index',
                requiredPermission: 'hardware.purchases.view',
                module: 'Quincaillerie',
                icon: 'FileText'
            ),
            new GlobalSearchItem(
                label: 'Dépôts Quincaillerie',
                description: 'Dépôts quincaillerie',
                routeName: 'hardware.depots.index',
                requiredPermission: 'hardware.stock.view',
                module: 'Quincaillerie',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Transferts Quincaillerie',
                description: 'Transferts quincaillerie',
                routeName: 'hardware.transfers.index',
                requiredPermission: 'transfer.view',
                module: 'Quincaillerie',
                icon: 'ArrowLeftRight'
            ),
            new GlobalSearchItem(
                label: 'Inventaires Quincaillerie',
                description: 'Inventaires quincaillerie',
                routeName: 'hardware.inventories.index',
                requiredPermission: 'hardware.stock.view',
                module: 'Quincaillerie',
                icon: 'ClipboardList'
            ),
            new GlobalSearchItem(
                label: 'Rapports Quincaillerie',
                description: 'Rapports quincaillerie',
                routeName: 'hardware.reports.index',
                requiredPermission: 'hardware.report.view',
                module: 'Quincaillerie',
                icon: 'BarChart'
            ),

            // ============================================
            // MODULE: E-COMMERCE
            // ============================================
            new GlobalSearchItem(
                label: 'Dashboard E-commerce',
                description: 'Tableau de bord boutique en ligne',
                routeName: 'ecommerce.dashboard',
                requiredPermission: 'ecommerce.dashboard.view',
                module: 'E-commerce',
                icon: 'LayoutDashboard'
            ),
            new GlobalSearchItem(
                label: 'Catalogue E-commerce',
                description: 'Catalogue produits boutique',
                routeName: 'ecommerce.catalog.index',
                requiredPermission: 'ecommerce.catalog.view',
                module: 'E-commerce',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Produits E-commerce',
                description: 'Produits boutique en ligne',
                routeName: 'ecommerce.products.index',
                requiredPermission: 'ecommerce.product.view',
                module: 'E-commerce',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Catégories E-commerce',
                description: 'Catégories boutique',
                routeName: 'ecommerce.categories.index',
                requiredPermission: 'ecommerce.category.view',
                module: 'E-commerce',
                icon: 'Tag'
            ),
            new GlobalSearchItem(
                label: 'Commandes E-commerce',
                description: 'Ventes et commandes en ligne',
                routeName: 'ecommerce.orders.index',
                requiredPermission: 'ecommerce.order.view',
                module: 'E-commerce',
                icon: 'ShoppingCart'
            ),
            new GlobalSearchItem(
                label: 'Clients E-commerce',
                description: 'Clients boutique',
                routeName: 'ecommerce.customers.index',
                requiredPermission: 'ecommerce.customer.view',
                module: 'E-commerce',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Fournisseurs E-commerce',
                description: 'Fournisseurs e-commerce',
                routeName: 'ecommerce.suppliers.index',
                requiredPermission: 'ecommerce.view',
                module: 'E-commerce',
                icon: 'Truck'
            ),
            new GlobalSearchItem(
                label: 'Paiements E-commerce',
                description: 'Méthodes et paiements boutique',
                routeName: 'ecommerce.payments.index',
                requiredPermission: 'ecommerce.payment.view',
                module: 'E-commerce',
                icon: 'CreditCard'
            ),
            new GlobalSearchItem(
                label: 'Livraisons E-commerce',
                description: 'Expédition et livraison',
                routeName: 'ecommerce.shipping.index',
                requiredPermission: 'ecommerce.shipping.view',
                module: 'E-commerce',
                icon: 'Truck'
            ),
            new GlobalSearchItem(
                label: 'Promotions E-commerce',
                description: 'Promotions boutique',
                routeName: 'ecommerce.promotions.index',
                requiredPermission: 'ecommerce.promotion.view',
                module: 'E-commerce',
                icon: 'Gift'
            ),
            new GlobalSearchItem(
                label: 'Coupons E-commerce',
                description: 'Codes promo et coupons',
                routeName: 'ecommerce.coupons.index',
                requiredPermission: 'ecommerce.coupon.view',
                module: 'E-commerce',
                icon: 'Ticket'
            ),
            new GlobalSearchItem(
                label: 'Avis E-commerce',
                description: 'Avis clients produits',
                routeName: 'ecommerce.reviews.index',
                requiredPermission: 'ecommerce.review.view',
                module: 'E-commerce',
                icon: 'Star'
            ),
            new GlobalSearchItem(
                label: 'Stock E-commerce',
                description: 'Stock boutique en ligne',
                routeName: 'ecommerce.stock.index',
                requiredPermission: 'ecommerce.stock.view',
                module: 'E-commerce',
                icon: 'Warehouse'
            ),
            new GlobalSearchItem(
                label: 'Rapports E-commerce',
                description: 'Rapports et analytics e-commerce',
                routeName: 'ecommerce.reports.index',
                requiredPermission: 'ecommerce.report.view',
                module: 'E-commerce',
                icon: 'BarChart'
            ),
            new GlobalSearchItem(
                label: 'Paramètres E-commerce',
                description: 'Configuration boutique, domaine, IA',
                routeName: 'ecommerce.settings.index',
                requiredPermission: 'ecommerce.settings.view',
                module: 'E-commerce',
                icon: 'Settings'
            ),
            new GlobalSearchItem(
                label: 'Marketing E-commerce',
                description: 'Marketing et campagnes',
                routeName: 'ecommerce.marketing.index',
                requiredPermission: 'ecommerce.marketing.view',
                module: 'E-commerce',
                icon: 'Megaphone'
            ),
            new GlobalSearchItem(
                label: 'Vitrine E-commerce',
                description: 'Prévisualiser la boutique en ligne',
                routeName: 'ecommerce.storefront.index',
                requiredPermission: 'ecommerce.catalog.view',
                module: 'E-commerce',
                icon: 'Eye'
            ),
            new GlobalSearchItem(
                label: 'CMS Pages',
                description: 'Pages CMS boutique',
                routeName: 'ecommerce.cms.pages.index',
                requiredPermission: 'ecommerce.cms.view',
                module: 'E-commerce',
                icon: 'FileText'
            ),
            new GlobalSearchItem(
                label: 'CMS Bannières',
                description: 'Bannières vitrine',
                routeName: 'ecommerce.cms.banners.index',
                requiredPermission: 'ecommerce.cms.view',
                module: 'E-commerce',
                icon: 'Image'
            ),
            new GlobalSearchItem(
                label: 'CMS Accueil',
                description: 'Sections page d\'accueil boutique',
                routeName: 'ecommerce.storefront.cms',
                requiredPermission: 'ecommerce.cms.view',
                module: 'E-commerce',
                icon: 'LayoutTemplate'
            ),
            new GlobalSearchItem(
                label: 'Blog E-commerce',
                description: 'Articles et blog boutique',
                routeName: 'ecommerce.cms.blog.index',
                requiredPermission: 'ecommerce.cms.view',
                module: 'E-commerce',
                icon: 'BookOpen'
            ),
            new GlobalSearchItem(
                label: 'Médias E-commerce',
                description: 'Bibliothèque médias CMS',
                routeName: 'ecommerce.cms.media.index',
                requiredPermission: 'ecommerce.cms.view',
                module: 'E-commerce',
                icon: 'FolderOpen'
            ),

            // ============================================
            // MODULE: FINANCE
            // ============================================
            new GlobalSearchItem(
                label: 'Dashboard Finance',
                description: 'Tableau de bord financier',
                routeName: 'finance.dashboard',
                requiredPermission: 'finance.dashboard.view',
                module: 'Finance',
                icon: 'BarChart'
            ),
            new GlobalSearchItem(
                label: 'Dépenses',
                description: 'Gestion des dépenses',
                routeName: 'finance.expenses.index',
                requiredPermission: 'finance.expense.view',
                module: 'Finance',
                icon: 'DollarSign'
            ),
            new GlobalSearchItem(
                label: 'Dettes',
                description: 'Dettes et règlements',
                routeName: 'finance.debts.index',
                requiredPermission: 'finance.debt.view',
                module: 'Finance',
                icon: 'AlertCircle'
            ),
            new GlobalSearchItem(
                label: 'Factures Finance',
                description: 'Factures et documents',
                routeName: 'finance.invoices.index',
                requiredPermission: 'finance.invoice.view',
                module: 'Finance',
                icon: 'Receipt'
            ),

            // ============================================
            // MODULE: SUPPORT
            // ============================================
            new GlobalSearchItem(
                label: 'Créer Ticket Support',
                description: 'Ouvrir un ticket d\'assistance',
                routeName: 'support.tickets.create',
                requiredPermission: 'support.tickets.create',
                module: 'Support',
                icon: 'Plus'
            ),
            new GlobalSearchItem(
                label: 'Mes Tickets',
                description: 'Mes tickets support',
                routeName: 'support.tickets.mine',
                requiredPermission: 'support.tickets.view',
                module: 'Support',
                icon: 'Ticket'
            ),
            new GlobalSearchItem(
                label: 'Tickets Support Admin',
                description: 'Tous les tickets (admin support)',
                routeName: 'support.tickets.index',
                requiredPermission: 'support.admin',
                module: 'Support',
                icon: 'ClipboardList'
            ),
            new GlobalSearchItem(
                label: 'Chat Support Admin',
                description: 'Chat support administrateur',
                routeName: 'support.chat.admin',
                requiredPermission: 'support.admin',
                module: 'Support',
                icon: 'MessageCircle'
            ),
            new GlobalSearchItem(
                label: 'Incidents',
                description: 'Incidents système',
                routeName: 'support.incidents.index',
                requiredPermission: 'support.admin',
                module: 'Support',
                icon: 'AlertCircle'
            ),
            new GlobalSearchItem(
                label: 'FAQ Support',
                description: 'Base de connaissance / FAQ',
                routeName: 'support.faq.index',
                requiredPermission: 'support.faq',
                module: 'Support',
                icon: 'HelpCircle'
            ),
            new GlobalSearchItem(
                label: 'Contact Support',
                description: 'Contacter le support',
                routeName: 'support.contact.show',
                requiredPermission: 'support.tickets.create',
                module: 'Support',
                icon: 'Mail'
            ),
            new GlobalSearchItem(
                label: 'Statut Système',
                description: 'Statut des services',
                routeName: 'support.status.index',
                requiredPermission: 'support.admin',
                module: 'Support',
                icon: 'Activity'
            ),

            // ============================================
            // MODULE: SETTINGS & GENERAL
            // ============================================
            new GlobalSearchItem(
                label: 'Paramètres Boutique',
                description: 'Configuration de la boutique / tenant',
                routeName: 'settings.index',
                requiredPermission: 'settings.view',
                module: 'Paramètres',
                icon: 'Settings'
            ),
            new GlobalSearchItem(
                label: 'Devises',
                description: 'Gestion des devises et taux de change',
                routeName: 'settings.currencies',
                requiredPermission: 'settings.currency.view',
                module: 'Paramètres',
                icon: 'DollarSign'
            ),
            new GlobalSearchItem(
                label: 'Branding',
                description: 'Logo, couleurs et identité visuelle',
                routeName: 'admin.branding',
                requiredPermission: 'settings.branding',
                module: 'Paramètres',
                icon: 'Palette'
            ),
            new GlobalSearchItem(
                label: 'Configuration Mail',
                description: 'Paramètres SMTP et e-mails',
                routeName: 'admin.mail-settings',
                requiredPermission: 'settings.mail.manage',
                module: 'Paramètres',
                icon: 'Mail'
            ),
            new GlobalSearchItem(
                label: 'Plans Billing',
                description: 'Plans d\'abonnement et limitations',
                routeName: 'admin.billing.plans.index',
                requiredPermission: 'admin.billing.manage',
                module: 'Admin',
                icon: 'CreditCard'
            ),
            new GlobalSearchItem(
                label: 'Transactions Billing',
                description: 'Transactions et abonnements',
                routeName: 'admin.billing.transactions.index',
                requiredPermission: 'admin.billing.manage',
                module: 'Admin',
                icon: 'Scroll'
            ),
            new GlobalSearchItem(
                label: 'Retraits Admin',
                description: 'Retraits marchands (admin)',
                routeName: 'admin.billing.withdrawals.index',
                requiredPermission: 'admin.billing.manage',
                module: 'Admin',
                icon: 'Building2'
            ),
            new GlobalSearchItem(
                label: 'Signalements Boutiques',
                description: 'Signalements e-commerce admin',
                routeName: 'admin.ecommerce.shop-reports.index',
                requiredPermission: 'admin.dashboard.view',
                module: 'Admin',
                icon: 'Flag'
            ),
            new GlobalSearchItem(
                label: 'Dashboard Admin ROOT',
                description: 'Administration plateforme',
                routeName: 'admin.dashboard',
                requiredPermission: 'admin.dashboard.view',
                module: 'Admin',
                icon: 'LayoutDashboard'
            ),
            new GlobalSearchItem(
                label: 'Mes Retraits',
                description: 'Retraits et paiements marchand',
                routeName: 'withdrawals.index',
                requiredPermission: null,
                module: 'Général',
                icon: 'DollarSign'
            ),
            new GlobalSearchItem(
                label: 'Upgrade Plan',
                description: 'Mettre à niveau l\'abonnement',
                routeName: 'billing.onboarding.payment',
                requiredPermission: null,
                module: 'Général',
                icon: 'CreditCard'
            ),
            new GlobalSearchItem(
                label: 'Tutoriel',
                description: 'Guide et tutoriel application',
                routeName: 'tutorial.index',
                requiredPermission: null,
                module: 'Général',
                icon: 'BookOpen'
            ),
            new GlobalSearchItem(
                label: 'Parrainage',
                description: 'Programme referral / parrainage',
                routeName: 'referrals.dashboard',
                requiredPermission: 'referral.view',
                module: 'Général',
                icon: 'Users'
            ),
            new GlobalSearchItem(
                label: 'Paramètres Parrainage',
                description: 'Configuration referral',
                routeName: 'referrals.settings.index',
                requiredPermission: 'referral.settings.view',
                module: 'Général',
                icon: 'Settings'
            ),
            new GlobalSearchItem(
                label: 'Logs Système',
                description: 'Journaux système',
                routeName: 'logs.system',
                requiredPermission: 'logs.system',
                module: 'Logs',
                icon: 'ClipboardList'
            ),
            new GlobalSearchItem(
                label: 'Historique Actions',
                description: 'Audit des actions utilisateurs',
                routeName: 'logs.actions',
                requiredPermission: 'logs.actions',
                module: 'Logs',
                icon: 'Scroll'
            ),
            new GlobalSearchItem(
                label: 'Connexions Utilisateurs',
                description: 'Historique des connexions',
                routeName: 'logs.connections',
                requiredPermission: 'logs.connections',
                module: 'Logs',
                icon: 'Lock'
            ),
            new GlobalSearchItem(
                label: 'Dashboard CRM',
                description: 'CRM administration',
                routeName: 'crm.dashboard',
                requiredPermission: 'crm.dashboard.view',
                module: 'CRM',
                icon: 'BarChart'
            ),

            // ============================================
            // MODULE: PROFILE (Profil utilisateur)
            // ============================================
            new GlobalSearchItem(
                label: 'Profil',
                description: 'Gérer votre profil utilisateur',
                routeName: 'profile.edit',
                requiredPermission: null, // Accessible à tous les utilisateurs authentifiés
                module: 'Profile',
                icon: 'User'
            ),
            new GlobalSearchItem(
                label: 'Dashboard',
                description: 'Tableau de bord principal',
                routeName: 'dashboard',
                requiredPermission: null, // Accessible à tous les utilisateurs authentifiés
                module: 'General',
                icon: 'LayoutDashboard'
            ),
        ];
    }
}
