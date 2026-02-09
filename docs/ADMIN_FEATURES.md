# 👑 ADMIN / ROOT - Fonctionnalités et Architecture

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture UI](#architecture-ui)
3. [Identification du ROOT](#identification-du-root)
4. [Modules et Fonctionnalités](#modules-et-fonctionnalités)
5. [Permissions et Sécurité](#permissions-et-sécurité)
6. [Module Access Mode (RBAC)](#module-access-mode-rbac)
7. [Gestion des Permissions](#gestion-des-permissions)
8. [Sidebar et Navigation](#sidebar-et-navigation)
9. [Règles de Visibilité UI](#règles-de-visibilité-ui)
10. [Todo List - Implémentation](#todo-list---implémentation)

---

## 🎯 Vue d'ensemble

### Définition

Le **ROOT** (ou **ADMIN**) est le propriétaire de l'application SaaS. Il possède un accès complet et global à tous les tenants, utilisateurs, et fonctionnalités de la plateforme.

### Caractéristiques principales

- ✅ **Accès global** : Peut voir et gérer tous les tenants
- ✅ **Permissions par défaut** : Possède toutes les permissions automatiquement
- ✅ **Création d'admins** : Peut créer d'autres administrateurs
- ✅ **Gestion RBAC** : Peut créer des rôles et assigner des permissions
- ✅ **Protection code** : Accès au module "Access Mode" garanti par le code (type = 'ROOT')

### Principe fondamental

> **Une seule interface UI pour tous les utilisateurs. La visibilité des modules et actions dépend uniquement des Permissions, jamais du rôle directement.**

---

## 🏗️ Architecture UI

### Principe : Interface Unifiée

```
┌─────────────────────────────────────────────────────────┐
│                    NAVBAR (Top)                          │
│  Logo | Recherche | Notifications | Profil | Logout     │
├─────────────────────────────────────────────────────────┤
│  │                                                      │
│  │  SIDEBAR (Left)      │    CONTENT AREA (Main)      │
│  │  - Module 1          │    [Contenu dynamique]      │
│  │  - Module 2          │                             │
│  │  - Module 3          │    [Selon permissions]      │
│  │  - ...               │                             │
│  │                      │                             │
│  │  [Visible si         │    [Actions selon           │
│  │   permission OK]     │     permissions]            │
│  │                      │                             │
└─────────────────────────────────────────────────────────┘
```

### Règles d'architecture

1. **Une seule sidebar** : Tous les utilisateurs voient la même structure
2. **Visibilité conditionnelle** : Les modules apparaissent/disparaissent selon les permissions
3. **Pas de logique basée sur le rôle** : Aucun `if (user->type === 'ROOT')` dans le frontend
4. **Permissions comme source de vérité** : `if (user->hasPermission('admin.tenants.view'))`

---

## 🔐 Identification du ROOT

### Méthode d'identification

Le ROOT est identifié par **le code**, pas par une permission :

```php
// Dans le code (backend)
if (auth()->user()->type === 'ROOT') {
    // Accès garanti au module Access Mode
    // Bypass des vérifications de permissions pour certaines actions critiques
}
```

### Caractéristiques du ROOT

| Propriété | Valeur | Description |
|-----------|--------|-------------|
| `type` | `'ROOT'` | Identifiant unique dans le code |
| `tenant_id` | `NULL` | Non associé à un tenant spécifique |
| `is_active` | `true` | Toujours actif (ne peut pas être désactivé) |
| Permissions | Toutes | Possède toutes les permissions par défaut |

### Accès au module Access Mode

- ✅ **ROOT** : Accès garanti par le code (bypass permission)
- ⚠️ **Autres utilisateurs** : Nécessitent la permission `access.mode.view`

---

## 📦 Modules et Fonctionnalités

### Module 1 : Gestion des Tenants

#### Vue d'ensemble
Gestion complète de tous les tenants (boutiques/commerçants) de la plateforme.

#### Fonctionnalités

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir la liste** | `GET /admin/tenants` | `admin.tenants.view` | Liste tous les tenants avec stats |
| **Sélectionner tenant** | `GET /admin/select-tenant` | `admin.tenants.select.view` | Page de sélection pour navigation |
| **Dashboard tenant** | `GET /admin/tenant/{id}/dashboard` | `admin.tenants.dashboard.view` | Stats et utilisateurs d'un tenant |
| **Créer tenant** | `POST /admin/tenants` | `admin.tenants.create` | Créer un nouveau tenant |
| **Modifier tenant** | `PUT /admin/tenant/{id}` | `admin.tenants.update` | Modifier infos, activer/désactiver |
| **Supprimer tenant** | `DELETE /admin/tenant/{id}` | `admin.tenants.delete` | Supprimer définitivement un tenant |

#### Données affichées

- Liste des tenants :
  - Nom, code, email
  - Statut (actif/inactif)
  - Nombre d'utilisateurs
  - Date de création
  - Dernière activité

- Dashboard tenant :
  - Statistiques utilisateurs (total, actifs)
  - Liste des utilisateurs du tenant
  - Dernière connexion
  - Actions rapides (activer/désactiver utilisateurs)

#### Actions disponibles

- ✅ Activer/Désactiver un tenant
- ✅ Voir les détails d'un tenant
- ✅ Accéder au dashboard d'un tenant
- ✅ Créer un nouveau tenant (formulaire)
- ✅ Modifier les informations d'un tenant
- ✅ Supprimer un tenant (avec confirmation)

---

### Module 2 : Gestion des Utilisateurs

#### Vue d'ensemble
Gestion globale de tous les utilisateurs de la plateforme, tous tenants confondus.

#### Fonctionnalités

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir la liste** | `GET /admin/users` | `admin.users.view` | Liste tous les utilisateurs groupés par tenant |
| **Créer utilisateur** | `POST /admin/users` | `admin.users.create` | Créer un nouvel utilisateur (formulaire) |
| **Modifier utilisateur** | `PUT /admin/user/{id}` | `admin.users.update` | Modifier infos, activer/désactiver, changer rôle |
| **Supprimer utilisateur** | `DELETE /admin/user/{id}` | `admin.users.delete` | Supprimer définitivement un utilisateur |

#### Données affichées

- Liste des utilisateurs :
  - Nom complet, email
  - Tenant associé
  - Type/Rôle
  - Statut (actif/inactif)
  - Date d'inscription
  - Dernière connexion

#### Actions disponibles

- ✅ Activer/Désactiver un utilisateur
- ✅ Voir les détails d'un utilisateur
- ✅ Créer un nouvel utilisateur
- ✅ Modifier les informations d'un utilisateur
- ✅ Assigner/Retirer des rôles
- ✅ Supprimer un utilisateur (avec confirmation)
- ⚠️ **Protection** : Impossible de désactiver/supprimer le ROOT user

#### Restrictions

- ❌ Ne peut pas désactiver un utilisateur ROOT
- ❌ Ne peut pas supprimer un utilisateur ROOT
- ✅ Peut créer d'autres utilisateurs avec type ROOT (si permission)

---

### Module 3 : Access Mode (RBAC / Permissions)

#### Vue d'ensemble
Module de gestion du système de rôles et permissions (RBAC). **Accès garanti pour ROOT via le code.**

#### Fonctionnalités - Gestion des Rôles

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir les rôles** | `GET /admin/roles` | `access.mode.view` | Liste tous les rôles (globaux + par tenant) |
| **Créer rôle** | `POST /admin/roles` | `access.mode.roles.create` | Créer un nouveau rôle (formulaire) |
| **Modifier rôle** | `PUT /admin/role/{id}` | `access.mode.roles.update` | Modifier nom, description, permissions |
| **Supprimer rôle** | `DELETE /admin/role/{id}` | `access.mode.roles.delete` | Supprimer un rôle (avec vérification) |
| **Rechercher rôle** | `GET /admin/roles?search=...` | `access.mode.roles.view` | Recherche par nom, tenant, permissions |

#### Fonctionnalités - Assignation Permissions ↔ Rôles

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir permissions d'un rôle** | `GET /admin/role/{id}/permissions` | `access.mode.roles.view` | Liste des permissions assignées |
| **Assigner permission** | `POST /admin/role/{id}/permissions` | `access.mode.roles.update` | Ajouter une permission à un rôle |
| **Retirer permission** | `DELETE /admin/role/{id}/permission/{permId}` | `access.mode.roles.update` | Retirer une permission d'un rôle |
| **Assigner toutes permissions** | `POST /admin/role/{id}/permissions/sync` | `access.mode.roles.update` | Synchroniser toutes les permissions |

#### Fonctionnalités - Assignation Rôles ↔ Utilisateurs

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir rôles d'un utilisateur** | `GET /admin/user/{id}/roles` | `admin.users.view` | Liste des rôles assignés |
| **Assigner rôle** | `POST /admin/user/{id}/roles` | `admin.users.update` | Assigner un rôle à un utilisateur |
| **Retirer rôle** | `DELETE /admin/user/{id}/role/{roleId}` | `admin.users.update` | Retirer un rôle d'un utilisateur |

#### Interface utilisateur

**Page : Liste des Rôles**
- Tableau avec colonnes :
  - Nom du rôle
  - Tenant (ou "Global")
  - Nombre de permissions
  - Nombre d'utilisateurs
  - Statut (actif/inactif)
  - Actions (éditer, supprimer, voir permissions)

**Page : Créer/Éditer Rôle**
- Formulaire :
  - Nom du rôle (requis, unique par tenant)
  - Description
  - Tenant (optionnel, NULL pour rôle global)
  - Liste des permissions disponibles (checkboxes groupées)
  - Bouton "Sauvegarder"

**Page : Permissions d'un Rôle**
- Liste des permissions assignées
- Bouton "Ajouter permission"
- Bouton "Retirer permission" pour chaque permission
- Recherche de permissions

---

### Module 4 : Gestion des Permissions

#### Vue d'ensemble
Gestion des permissions depuis l'interface utilisateur. Les permissions sont définies dans `storage/app/permissions.yaml`.

#### Fonctionnalités

| Action | Route | Permission | Description |
|--------|-------|------------|-------------|
| **Voir les permissions** | `GET /admin/permissions` | `access.mode.permissions.view` | Liste toutes les permissions |
| **Générer depuis YAML** | `POST /admin/permissions/sync` | `access.mode.permissions.sync` | Lit permissions.yaml et synchronise |
| **Rechercher permission** | `GET /admin/permissions?search=...` | `access.mode.permissions.view` | Recherche par code, groupe |
| **Supprimer permission** | `DELETE /admin/permission/{id}` | `access.mode.permissions.delete` | Supprimer une permission (marquer is_old) |
| **Exporter liste** | `GET /admin/permissions/export` | `access.mode.permissions.view` | Export CSV/PDF de la liste |

#### Bouton "Générer les permissions"

**Fonctionnement :**
1. Lit le fichier `storage/app/permissions.yaml`
2. Parse le contenu YAML
3. Compare avec les permissions existantes en DB
4. **Insère uniquement les nouvelles permissions** (ne supprime jamais)
5. Marque les permissions obsolètes comme `is_old = true` (mais les conserve)
6. Affiche un rapport :
   - X permissions créées
   - Y permissions mises à jour
   - Z permissions marquées comme anciennes

**Règles importantes :**
- ✅ **Jamais de suppression automatique** : Les permissions existantes sont conservées
- ✅ **Idempotent** : Peut être exécuté plusieurs fois sans doublon
- ✅ **Logging** : Toutes les actions sont loggées

#### Interface utilisateur

**Page : Liste des Permissions**
- Tableau avec colonnes :
  - Code de la permission
  - Groupe
  - Description
  - Statut (active/ancienne)
  - Nombre de rôles utilisant cette permission
  - Actions (supprimer, voir rôles)

**Page : Synchronisation**
- Zone de texte pour coller le contenu YAML
- OU bouton "Choisir fichier" pour uploader
- Bouton "Générer les permissions"
- Rapport de synchronisation après exécution

---

## 🔒 Permissions et Sécurité

### Permissions existantes (actuelles)

#### Groupe : `admin`

| Permission | Description | Route associée |
|------------|-------------|----------------|
| `admin.tenants.select.view` | Voir la page de sélection de tenant | `admin.tenants.select.view` |
| `admin.tenants.dashboard.view` | Voir le dashboard d'un tenant | `admin.tenants.dashboard.view` |
| `admin.tenants.view` | Voir la liste de tous les tenants | `admin.tenants.view` |
| `admin.tenants.create` | Créer un nouveau tenant | `POST /admin/tenants` |
| `admin.tenants.update` | Modifier un tenant | `admin.tenants.update` |
| `admin.tenants.delete` | Supprimer un tenant | `DELETE /admin/tenant/{id}` |
| `admin.users.view` | Voir la liste de tous les utilisateurs | `admin.users.view` |
| `admin.users.create` | Créer un nouvel utilisateur | `POST /admin/users` |
| `admin.users.update` | Modifier un utilisateur | `admin.users.update` |
| `admin.users.delete` | Supprimer un utilisateur | `DELETE /admin/user/{id}` |

### Permissions à créer (pour Access Mode)

#### Groupe : `access.mode`

| Permission | Description | Route associée |
|------------|-------------|----------------|
| `access.mode.view` | Accéder au module Access Mode | `GET /admin/access-mode` |
| `access.mode.roles.view` | Voir la liste des rôles | `GET /admin/roles` |
| `access.mode.roles.create` | Créer un rôle | `POST /admin/roles` |
| `access.mode.roles.update` | Modifier un rôle | `PUT /admin/role/{id}` |
| `access.mode.roles.delete` | Supprimer un rôle | `DELETE /admin/role/{id}` |
| `access.mode.permissions.view` | Voir la liste des permissions | `GET /admin/permissions` |
| `access.mode.permissions.sync` | Synchroniser depuis YAML | `POST /admin/permissions/sync` |
| `access.mode.permissions.delete` | Supprimer une permission | `DELETE /admin/permission/{id}` |
| `access.mode.permissions.export` | Exporter la liste | `GET /admin/permissions/export` |

### Règles de sécurité

1. **Vérification des permissions** :
   - Toutes les routes sont protégées par le middleware `permission`
   - Le middleware vérifie `user->hasPermission(route_name)`
   - Si pas de permission → 403 Forbidden

2. **Bypass ROOT** :
   - Le ROOT a accès garanti au module Access Mode (code)
   - Mais les actions CRUD restent protégées par permissions
   - Exception : Création d'autres ROOT users (protection code supplémentaire)

3. **Protection ROOT user** :
   - Impossible de désactiver un utilisateur ROOT
   - Impossible de supprimer un utilisateur ROOT
   - Vérification dans le code : `if ($user->type === 'ROOT') { abort(403); }`

---

## 🧩 Module Access Mode (RBAC)

### Architecture du module

```
Access Mode
├── Gestion des Rôles
│   ├── Liste des rôles
│   ├── Créer un rôle
│   ├── Éditer un rôle
│   ├── Supprimer un rôle
│   └── Rechercher un rôle
│
├── Assignation Permissions ↔ Rôles
│   ├── Voir permissions d'un rôle
│   ├── Assigner permission à un rôle
│   └── Retirer permission d'un rôle
│
├── Assignation Rôles ↔ Utilisateurs
│   ├── Voir rôles d'un utilisateur
│   ├── Assigner rôle à un utilisateur
│   └── Retirer rôle d'un utilisateur
│
└── Gestion des Permissions
    ├── Liste des permissions
    ├── Générer depuis YAML
    ├── Rechercher une permission
    ├── Supprimer une permission
    └── Exporter la liste
```

### Flux de création d'un rôle

```
1. Admin clique "Créer un rôle"
   ↓
2. Formulaire s'affiche
   - Nom, Description, Tenant (optionnel)
   ↓
3. Admin sélectionne les permissions (checkboxes)
   ↓
4. Admin clique "Sauvegarder"
   ↓
5. Rôle créé en DB
   ↓
6. Permissions assignées au rôle
   ↓
7. Redirection vers liste des rôles
```

### Flux d'assignation rôle → utilisateur

```
1. Admin va sur "Gestion des Utilisateurs"
   ↓
2. Clique sur un utilisateur
   ↓
3. Section "Rôles" s'affiche
   ↓
4. Admin clique "Assigner un rôle"
   ↓
5. Liste déroulante des rôles disponibles
   ↓
6. Admin sélectionne un rôle
   ↓
7. Rôle assigné à l'utilisateur
   ↓
8. Permissions de l'utilisateur mises à jour automatiquement
```

---

## 📄 Gestion des Permissions

### Source de vérité : `permissions.yaml`

**Emplacement :** `storage/app/permissions.yaml`

**Format :**
```yaml
# Groupe de permissions
admin:
  - admin.tenants.view
  - admin.tenants.create
  - admin.tenants.update
  - admin.tenants.delete

access.mode:
  - access.mode.view
  - access.mode.roles.create
  - access.mode.roles.update
```

### Processus de synchronisation

1. **Lecture du fichier** : `Storage::disk('local')->get('permissions.yaml')`
2. **Parsing YAML** : Extraction des groupes et codes
3. **Comparaison avec DB** : Vérification des permissions existantes
4. **Insertion** : Création des nouvelles permissions uniquement
5. **Marquage** : Les permissions absentes du YAML sont marquées `is_old = true`
6. **Conservation** : Aucune permission n'est supprimée

### Règles de gestion

- ✅ **Ajout** : Nouvelles permissions du YAML → Insertion en DB
- ✅ **Conservation** : Permissions existantes → Conservées même si absentes du YAML
- ✅ **Marquage** : Permissions absentes → `is_old = true` (mais toujours utilisables)
- ❌ **Suppression** : Jamais de suppression automatique
- ✅ **Idempotence** : Le processus peut être exécuté plusieurs fois sans doublon

---

## 🧭 Sidebar et Navigation

### Structure de la sidebar

```
📊 Dashboard
   └── Vue d'ensemble (si permission: dashboard.view)

🏢 Tenants
   ├── Sélectionner tenant (si: admin.tenants.select.view)
   ├── Liste des tenants (si: admin.tenants.view)
   └── Créer un tenant (si: admin.tenants.create)

👥 Utilisateurs
   ├── Liste des utilisateurs (si: admin.users.view)
   └── Créer un utilisateur (si: admin.users.create)

🔐 Access Mode
   ├── Rôles (si: access.mode.roles.view)
   ├── Permissions (si: access.mode.permissions.view)
   └── Synchroniser permissions (si: access.mode.permissions.sync)

⚙️ Paramètres
   └── Profil (si: profile.view)
```

### Règles de visibilité

1. **Module visible** : Si l'utilisateur a **au moins une permission** du module
2. **Action visible** : Si l'utilisateur a **la permission spécifique**
3. **Bouton visible** : Si l'utilisateur a **la permission de création/modification**

### Exemple de logique

```jsx
// ❌ MAUVAIS (basé sur le rôle)
{user.type === 'ROOT' && <MenuItem>Access Mode</MenuItem>}

// ✅ BON (basé sur la permission)
{user.hasPermission('access.mode.view') && <MenuItem>Access Mode</MenuItem>}
```

---

## 👁️ Règles de Visibilité UI

### Principe général

> **Tout élément UI (bouton, lien, section) est visible uniquement si l'utilisateur possède la permission correspondante.**

### Règles par type d'élément

#### 1. Menu Sidebar

```jsx
// Module visible si au moins une permission du module
const canViewTenants = user.hasPermission('admin.tenants.view') 
                    || user.hasPermission('admin.tenants.create')
                    || user.hasPermission('admin.tenants.update');

{canViewTenants && <SidebarMenuItem>Tenants</SidebarMenuItem>}
```

#### 2. Boutons d'action

```jsx
// Bouton visible si permission de création
{user.hasPermission('admin.tenants.create') && (
    <Button onClick={handleCreate}>Créer un tenant</Button>
)}
```

#### 3. Actions dans un tableau

```jsx
// Ligne d'action visible si permission de modification
{user.hasPermission('admin.tenants.update') && (
    <Button onClick={() => handleEdit(tenant)}>Modifier</Button>
)}

// Ligne d'action visible si permission de suppression
{user.hasPermission('admin.tenants.delete') && (
    <Button onClick={() => handleDelete(tenant)}>Supprimer</Button>
)}
```

#### 4. Sections de page

```jsx
// Section visible si permission de visualisation
{user.hasPermission('admin.tenants.dashboard.view') && (
    <DashboardSection>
        {/* Contenu */}
    </DashboardSection>
)}
```

### Règles spéciales pour ROOT

- ✅ **ROOT** : Accès garanti au module Access Mode (bypass permission dans le code backend)
- ✅ **ROOT** : Toutes les permissions par défaut (pas besoin de vérification supplémentaire)
- ⚠️ **ROOT** : Protection contre désactivation/suppression (code backend)

---

## ✅ Todo List - Implémentation

### Phase 1 : Infrastructure de base

- [ ] **Créer les permissions manquantes**
  - [ ] Ajouter les permissions `access.mode.*` dans `permissions.yaml`
  - [ ] Exécuter la synchronisation des permissions
  - [ ] Vérifier que toutes les permissions sont en DB

- [ ] **Créer les routes Access Mode**
  - [ ] Routes pour gestion des rôles (`/admin/roles/*`)
  - [ ] Routes pour gestion des permissions (`/admin/permissions/*`)
  - [ ] Routes pour assignation rôles ↔ utilisateurs
  - [ ] Protection par middleware `permission`

- [ ] **Créer les controllers**
  - [ ] `RoleController` (CRUD rôles)
  - [ ] `PermissionController` (liste, sync, export)
  - [ ] Méthodes dans `AdminController` pour assignation

### Phase 2 : Interface utilisateur - Sidebar

- [ ] **Créer le composant Sidebar**
  - [ ] Structure de base avec navigation
  - [ ] Logique de visibilité basée sur permissions
  - [ ] Support dark mode
  - [ ] Responsive (mobile/tablet/desktop)

- [ ] **Créer le composant Navbar**
  - [ ] Logo, recherche, notifications
  - [ ] Menu profil avec dropdown
  - [ ] Bouton logout
  - [ ] Support dark mode

- [ ] **Créer le Layout principal**
  - [ ] Layout avec Sidebar + Navbar + Content
  - [ ] Passage des permissions au frontend via Inertia
  - [ ] Gestion de l'état (sidebar collapsed/expanded)

### Phase 3 : Pages - Gestion des Tenants

- [ ] **Page : Liste des Tenants**
  - [ ] Tableau avec pagination
  - [ ] Filtres (statut, date)
  - [ ] Recherche
  - [ ] Actions (voir, modifier, supprimer) selon permissions
  - [ ] Bouton "Créer" si permission

- [ ] **Page : Créer/Éditer Tenant**
  - [ ] Formulaire avec validation
  - [ ] Champs : nom, code, email
  - [ ] Bouton "Sauvegarder"
  - [ ] Gestion des erreurs

- [ ] **Page : Dashboard Tenant**
  - [ ] Statistiques (utilisateurs, activité)
  - [ ] Liste des utilisateurs du tenant
  - [ ] Actions rapides (activer/désactiver)

### Phase 4 : Pages - Gestion des Utilisateurs

- [ ] **Page : Liste des Utilisateurs**
  - [ ] Tableau groupé par tenant
  - [ ] Filtres (tenant, type, statut)
  - [ ] Recherche
  - [ ] Actions (voir, modifier, supprimer) selon permissions
  - [ ] Bouton "Créer" si permission

- [ ] **Page : Créer/Éditer Utilisateur**
  - [ ] Formulaire avec validation
  - [ ] Champs : nom, email, password, type, tenant
  - [ ] Section "Assignation de rôles"
  - [ ] Bouton "Sauvegarder"

- [ ] **Page : Détails Utilisateur**
  - [ ] Informations de l'utilisateur
  - [ ] Liste des rôles assignés
  - [ ] Actions (assigner/retirer rôle) selon permissions

### Phase 5 : Module Access Mode - Rôles

- [ ] **Page : Liste des Rôles**
  - [ ] Tableau avec colonnes (nom, tenant, permissions, utilisateurs)
  - [ ] Filtres (tenant, statut)
  - [ ] Recherche
  - [ ] Actions (