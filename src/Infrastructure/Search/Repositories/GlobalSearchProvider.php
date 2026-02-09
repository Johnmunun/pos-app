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
                requiredPermission: 'manage.tenants',
                module: 'Admin',
                icon: 'Building2'
            ),
            new GlobalSearchItem(
                label: 'Utilisateurs',
                description: 'Gérer tous les utilisateurs du système (Users, Gestion utilisateurs)',
                routeName: 'admin.users.view',
                requiredPermission: 'manage.users',
                module: 'Admin',
                icon: 'UserCog'
            ),
            new GlobalSearchItem(
                label: 'Users',
                description: 'Manage all system users (Utilisateurs, Gestion utilisateurs)',
                routeName: 'admin.users.view',
                requiredPermission: 'manage.users',
                module: 'Admin',
                icon: 'UserCog'
            ),
            new GlobalSearchItem(
                label: 'Gestion Utilisateurs',
                description: 'Gérer les utilisateurs, rôles et permissions',
                routeName: 'admin.users.view',
                requiredPermission: 'manage.users',
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
                label: 'Produits',
                description: 'Gérer les produits de la pharmacie',
                routeName: 'pharmacy.products.index',
                requiredPermission: 'pharmacy.product.manage',
                module: 'Pharmacy',
                icon: 'Package'
            ),
            new GlobalSearchItem(
                label: 'Créer Produit',
                description: 'Ajouter un nouveau produit',
                routeName: 'pharmacy.products.create',
                requiredPermission: 'pharmacy.product.manage',
                module: 'Pharmacy',
                icon: 'PlusCircle'
            ),
            new GlobalSearchItem(
                label: 'Catégories',
                description: 'Gérer les catégories de produits',
                routeName: 'pharmacy.categories.index',
                requiredPermission: 'pharmacy.category.manage',
                module: 'Pharmacy',
                icon: 'FolderTree'
            ),
            new GlobalSearchItem(
                label: 'Créer Catégorie',
                description: 'Ajouter une nouvelle catégorie',
                routeName: 'pharmacy.categories.create',
                requiredPermission: 'pharmacy.category.manage',
                module: 'Pharmacy',
                icon: 'FolderPlus'
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
