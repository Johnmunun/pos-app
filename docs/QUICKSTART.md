# Guide Démarrage Rapide - POS SaaS

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Node.js 18+
- MySQL/PostgreSQL
- Composer
- Git

### Étapes d'installation

#### 1. Cloner et installer les dépendances
```bash
git clone <repo> pos-saas
cd pos-saas

# PHP
composer install

# Node.js
npm install
```

#### 2. Configuration de l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

#### 3. Configurer la base de données dans `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_saas
DB_USERNAME=root
DB_PASSWORD=
```

#### 4. Créer la base de données
```bash
mysql -u root -p -e "CREATE DATABASE pos_saas;"
```

#### 5. Exécuter les migrations
```bash
php artisan migrate
```

#### 6. Créer l'utilisateur ROOT
```bash
php artisan db:seed --class=CreateRootUserSeeder
```

#### 7. Compiler les assets
```bash
npm run build
```

#### 8. Démarrer le serveur
```bash
php artisan serve
```

L'application est accessible sur **http://localhost:8000**

---

## 🔐 Premiers pas

### Connexion en tant que ROOT

**URL:** http://localhost:8000/login

**Identifiants:**
```
Email: root@pos-saas.local
Mot de passe: RootPassword123
```

**Page d'administration:** http://localhost:8000/admin/select-tenant

### Créer un nouveau tenant

1. Allez à http://localhost:8000/ (Landing page)
2. Cliquez sur "Vendre" (en haut à droite)
3. Remplissez le formulaire d'inscription:
   - Nom de la boutique
   - Votre nom complet
   - Email
   - Mot de passe
4. Cliquez "Créer mon compte"
5. Vous êtes automatiquement:
   - Redirigé vers le dashboard
   - Fait admin (TENANT_ADMIN) du nouveau tenant

---

## 📁 Structure du projet

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/AdminController.php      # Gestion ROOT
│   │   │   ├── Auth/RegisteredUserController  # Inscription
│   │   │   └── ProfileController.php
│   │   └── Middleware/
│   │       └── CheckRootUser.php              # Vérif ROOT
│   ├── Models/
│   │   └── User.php
│   └── Providers/
├── config/
│   └── roles.php                              # Définition rôles
├── database/
│   ├── migrations/
│   └── seeders/
│       └── CreateRootUserSeeder.php           # ROOT user
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Landing.jsx                    # Landing page
│   │   │   ├── Auth/
│   │   │   │   ├── Login.jsx
│   │   │   │   └── Register.jsx
│   │   │   └── Admin/
│   │   │       ├── SelectTenant.jsx           # Sélection
│   │   │       ├── TenantDashboard.jsx        # Dashboard
│   │   │       ├── ManageTenants.jsx
│   │   │       └── ManageUsers.jsx
│   │   └── Components/
│   ├── css/
│   │   └── app.css                            # Tailwind
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── web.php                                # Routes principales
│   └── auth.php                               # Routes Breeze
├── tests/
│   └── Feature/
│       └── RootUserAccessTest.php
├── docs/
│   └── ROOT_ADMIN_SYSTEM.md                   # Documentation
├── .env.example                               # Configuration
└── package.json / composer.json               # Dépendances
```

---

## 🔄 Architecture multi-tenant

### Modèle de données

**Tenants (Boutiques)**
- id
- name (ex: "Ma Boutique")
- slug (ex: "ma-boutique-abc123")
- is_active (true/false)
- created_at

**Users (Utilisateurs)**
- id
- tenant_id (NULL pour ROOT)
- first_name, last_name
- email
- password
- type (ROOT, TENANT_ADMIN, MERCHANT, SELLER, STAFF)
- is_active (true/false)
- email_verified_at

### Flux de données

```
Landing Page
    ↓
Login / Register
    ↓
Authentification
    ↓
    ├─→ Type = ROOT → /admin/select-tenant
    └─→ Autre → /dashboard
```

---

## 🎨 Thème et design

### Palette de couleurs

- **Primaire:** Amber-Orange (#f59e0b, #ea580c)
- **Accent:** Emerald-500 (actifs, succès)
- **Arrière-plan:** Blanc pur (#ffffff)
- **Texte:** Gris (#111827 pour titres, #4b5563 pour corps)
- **Bordures:** Gris-200 (#e5e7eb)

### Composants utilisés

- React 18 + Inertia.js (Frontend)
- Laravel 12 + Breeze (Backend)
- Tailwind CSS 3 (Styling)
- Vite 7.3 (Build)

---

## 📝 Routes principales

### Public
- `GET /` → Landing page
- `GET /login` → Connexion
- `GET /register` → Inscription

### Authentifiées (Tenant users)
- `GET /dashboard` → Dashboard du tenant
- `GET /profile` → Profil utilisateur

### Admin ROOT
- `GET /admin/select-tenant` → Sélection tenant
- `GET /admin/tenant/{id}/dashboard` → Dashboard tenant
- `GET /admin/tenants` → Gestion tenants
- `GET /admin/users` → Gestion utilisateurs
- `POST /admin/tenant/{id}/toggle` → Activer/Désactiver tenant
- `POST /admin/user/{id}/toggle` → Activer/Désactiver utilisateur

---

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques ROOT
php artisan test tests/Feature/RootUserAccessTest.php

# Avec couverture
php artisan test --coverage
```

### Tests disponibles

- ✅ ROOT access control
- ✅ Login redirection
- ✅ Register tenant creation
- ✅ Admin pages access

---

## 🐛 Dépannage

### "SQLSTATE[HY000]: General error: 1030 Got error"
→ Vérifiez la connexion base de données dans `.env`

### "Class not found" pour les migrations
→ Exécutez `composer dump-autoload`

### Assets non compilés
→ Exécutez `npm run build`

### ROOT user introuvable
```bash
php artisan db:seed --class=CreateRootUserSeeder
```

### Réinitialiser la base de données
```bash
php artisan migrate:fresh --seed
```

---

## 📚 Documentation

- [ROOT Admin System](../docs/ROOT_ADMIN_SYSTEM.md)
- [Project Rules](../project_rules.txt)
- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)

---

## 🚀 Prochaines étapes

1. Créer le dashboard pour les utilisateurs tenant
2. Implémenter les fonctionnalités de produits
3. Ajouter le système de ventes
4. Intégrer les paiements (Stripe)
5. Ajouter les rapports et analytics

---

## 📧 Support

Pour toute question ou problème, consultez la documentation ou créez une issue.

**Version:** 1.0.0  
**Dernière mise à jour:** 2024
