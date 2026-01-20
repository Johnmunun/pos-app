# Système d'Administration ROOT

## Vue d'ensemble

Le système POS SaaS dispose d'un utilisateur **ROOT** (administrateur principal) qui peut :
- Accéder à tous les tenants de la plateforme
- Gérer les tenants (activation/désactivation)
- Gérer les utilisateurs globalement
- Voir les statistiques de chaque tenant

## Utilisateur ROOT par défaut

L'utilisateur ROOT est créé **une seule fois** via un seeder et ne s'inscrit **jamais** via le formulaire d'inscription public.

### Identifiants par défaut (DÉVELOPPEMENT UNIQUEMENT)

```
Email: root@pos-saas.local
Mot de passe: RootPassword123
Rôle: ROOT
```

⚠️ **IMPORTANT**: En production, changer immédiatement le mot de passe et utiliser des variables d'environnement.

## Commandes de gestion

### Créer le ROOT user initial

```bash
php artisan db:seed --class=CreateRootUserSeeder
```

Cette commande :
- Crée l'utilisateur ROOT avec les identifiants par défaut
- Vérifie qu'il n'existe pas déjà (prévient les doublons)
- N'a aucun `tenant_id` (pas lié à un tenant spécifique)

### Vérifier si ROOT existe

```bash
php artisan tinker
>>> App\Models\User::where('type', 'ROOT')->count();
```

## Flux de connexion ROOT

```
1. Utilisateur accède à /login
2. Rentre email: root@pos-saas.local et mot de passe
3. Authentification réussie
4. Contrôleur vérifie type === 'ROOT'
5. Redirection vers /admin/select-tenant
6. Affiche liste de tous les tenants
7. Sélection d'un tenant → /admin/tenant/{id}/dashboard
```

## Structure des rôles

| Rôle | tenant_id | Permissions |
|------|-----------|------------|
| **ROOT** | NULL | Accès à tous les tenants, gestion globale |
| **TENANT_ADMIN** | {id} | Admin d'un tenant spécifique |
| **MERCHANT** | {id} | Utilisateur standard du tenant |
| **SELLER** | {id} | Vendeur du tenant |
| **STAFF** | {id} | Personnel du tenant |

## Pages d'administration

### 1. `/admin/select-tenant` (SelectTenant.jsx)
- Liste de tous les tenants
- Statistiques rapides (utilisateurs, statut, date création)
- Accès aux panels d'administration

### 2. `/admin/tenant/{id}/dashboard` (TenantDashboard.jsx)
- Dashboard du tenant sélectionné
- Statistiques: utilisateurs, utilisateurs actifs, dernière activité
- Table des utilisateurs avec actions (activer/désactiver)

### 3. `/admin/tenants` (ManageTenants.jsx)
- Gestion globale des tenants
- Activé/Désactiver les tenants
- Créer/Modifier/Supprimer (à implémenter)

### 4. `/admin/users` (ManageUsers.jsx)
- Tous les utilisateurs de la plateforme
- Groupés par tenant
- Activer/Désactiver les utilisateurs

## Sécurité

### Middleware de protection

```php
// app/Http/Controllers/Admin/AdminController.php
public function __construct()
{
    $this->middleware(function ($request, $next) {
        if (auth()->check() && auth()->user()->type === 'ROOT') {
            return $next($request);
        }
        return redirect('/');
    });
}
```

Chaque action admin nécessite :
- ✓ Utilisateur authentifié
- ✓ Type = 'ROOT'
- Redirection sinon

### Restrictions

- Impossible de supprimer le ROOT user
- Impossible de créer un autre ROOT user (validation au controller)
- Les routes admin nécessitent `auth` et `verified`

## Configuration en production

### 1. Variables d'environnement

```bash
# .env.production
ROOT_EMAIL=administrateur@societe.com
ROOT_PASSWORD=MotDePasseTresSecurisé123!@#
```

### 2. Modifier le seeder

```php
// database/seeders/CreateRootUserSeeder.php
UserModel::create([
    'email' => env('ROOT_EMAIL', 'root@pos-saas.local'),
    'password' => bcrypt(env('ROOT_PASSWORD', 'default')),
    // ...
]);
```

### 3. Exécuter après le déploiement

```bash
php artisan migrate --force
php artisan db:seed --class=CreateRootUserSeeder --force
```

## Workflow type

### Installation

```bash
# 1. Migration des tables
php artisan migrate

# 2. Création du ROOT user
php artisan db:seed --class=CreateRootUserSeeder

# 3. Démarrage du serveur
php artisan serve

# 4. Accès à l'application
# Landing: http://localhost:8000/
# Login: http://localhost:8000/login
# Admin (après connexion ROOT): http://localhost:8000/admin/select-tenant
```

### Création d'un nouveau tenant (par un utilisateur)

```bash
# 1. Utilisateur clique "Vendre" sur landing
# 2. Remplit formulaire d'inscription avec:
#    - Nom de la boutique
#    - Son nom complet
#    - Email
#    - Mot de passe
# 3. Nouveau tenant créé automatiquement
# 4. Utilisateur devient TENANT_ADMIN
# 5. Redirection vers dashboard du tenant
```

### Supervision par ROOT

```bash
# 1. ROOT se connecte
# 2. Voit la liste de tous les tenants
# 3. Clique sur un tenant pour voir:
#    - Statistiques (utilisateurs, activité)
#    - Liste des utilisateurs
#    - Statut des utilisateurs
# 4. Peut activer/désactiver des utilisateurs
# 5. Peut activer/désactiver des tenants
```

## À implémenter

- [ ] Page de création/modification de tenant par ROOT
- [ ] Page de modification de mot de passe ROOT
- [ ] Logs d'audit pour les actions ROOT
- [ ] Backup/Restore de tenants
- [ ] Analytics globales
- [ ] Export de données de tenant

## Dépannage

### ROOT user n'existe pas
```bash
php artisan db:seed --class=CreateRootUserSeeder
```

### Oublié le mot de passe ROOT
```bash
# Utiliser tinker pour le réinitialiser
php artisan tinker
>>> $user = App\Models\User::where('type', 'ROOT')->first();
>>> $user->password = bcrypt('NouveauMotDePasse123');
>>> $user->save();
```

### Ne peut pas se connecter en tant que ROOT
- Vérifier que type = 'ROOT' dans la DB
- Vérifier que is_active = true
- Vérifier les logs d'erreur: `storage/logs/`

## Références

- Model: `app/Models/User.php`
- Controller: `app/Http/Controllers/Admin/AdminController.php`
- Seeder: `database/seeders/CreateRootUserSeeder.php`
- Routes: `routes/web.php`
