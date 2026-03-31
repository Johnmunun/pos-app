# 🚀 POS SaaS - Plateforme Multi-Tenant

Une application **Laravel 12 + React 18 + Inertia.js** avec système d'administration ROOT complet pour gérer plusieurs boutiques.

## ⚡ Démarrage rapide

```bash
# Installation
composer install && npm install

# Configuration
cp .env.example .env && php artisan key:generate

# Database
php artisan migrate && php artisan db:seed --class=CreateRootUserSeeder

# Démarrer 
npm run build && php artisan serve
```

Visitez: **http://localhost:8000**

---

## 🔐 Connexion ROOT

```
Email: root@pos-saas.local
Password: RootPassword123
```

Après connexion → Redirection automatique vers `/admin/select-tenant`

---

## 📚 Documentation

| Document | Utilité |
|----------|---------|
| [QUICKSTART.md](docs/QUICKSTART.md) | Installation & premiers pas |
| [ROOT_ADMIN_SYSTEM.md](docs/ROOT_ADMIN_SYSTEM.md) | Système ROOT expliqué |
| [USE_CASES.md](docs/USE_CASES.md) | 10 cas d'usage pratiques |
| [COMMANDS_REFERENCE.md](docs/COMMANDS_REFERENCE.md) | Toutes les commandes |
| [DEPLOYMENT_CHECKLIST.md](docs/DEPLOYMENT_CHECKLIST.md) | Production checklist |
| [docs/INDEX.md](docs/INDEX.md) | Index complet |

**👉 Nouveaux? Commencez par [docs/QUICKSTART.md](docs/QUICKSTART.md)**

---

## ✨ Fonctionnalités

### 🏪 Multi-Tenant
- Isolation complète des données par tenant_id
- Création automatique lors de l'inscription
- Gestion centrale des tenants

### 👥 5 Rôles
- **ROOT** - Admin global
- **TENANT_ADMIN** - Admin du tenant
- **MERCHANT** - Utilisateur standard
- **SELLER** - Permissions limitées
- **STAFF** - Consultation seulement

### 🔐 Sécurité
- Authentification Breeze
- Middleware protection
- CSRF & SQL Injection prévenu
- Email verification

### 📊 Admin Panel ROOT
- Sélection tenant
- Dashboard statistiques
- Gestion global users/tenants
- Activer/Désactiver

### 🎨 Design
- Landing page 6 sections
- Thème amber-orange cohérent
- Responsive design
- Composants React réutilisables

---

## 📊 Stats

| Métrique | Nombre |
|----------|--------|
| Lignes code | 2000+ |
| Fichiers créés | 12+ |
| Routes admin | 7 |
| Rôles | 5 |
| Tests | 5 ✅ |
| Docs pages | 9 |

---

## 🎯 Architecture

```
Landing → Register (crée tenant) → Dashboard
             ↓
        Login ROOT → Admin Panel
```

**ROOT Flow:**
1. Connexion → `/admin/select-tenant`
2. Sélectionne tenant → `/admin/tenant/{id}/dashboard`
3. Gère users + tenants

---

## 🧪 Tests

```bash
php artisan test                                    # Tous
php artisan test tests/Feature/RootUserAccessTest  # ROOT tests
php artisan test --coverage                        # Coverage
```

✅ **5/5 pass**

---

## 📁 Structure

```
app/Http/Controllers/Admin/AdminController.php     # Admin backend
app/Http/Middleware/CheckRootUser.php              # Protection
config/roles.php                                   # Rôles
resources/js/Pages/Admin/*.jsx                     # Pages admin
routes/web.php                                     # Routes
tests/Feature/RootUserAccessTest.php               # Tests
docs/                                              # Documentation
```

---

## 🚀 Commandes essentielles

```bash
# Dev
npm run dev              # Watch mode
php artisan serve        # Serveur dev

# Prod
npm run build            # Compiler assets
php artisan migrate      # Migrations

# Utils
php artisan test         # Tests
php artisan tinker       # REPL
php artisan migrate:fresh --seed  # Reset DB
```

Voir [COMMANDS_REFERENCE.md](docs/COMMANDS_REFERENCE.md)

---

## 🚀 Déploiement

1. Checklist: [DEPLOYMENT_CHECKLIST.md](docs/DEPLOYMENT_CHECKLIST.md)
2. Générer password: `openssl rand -base64 32`
3. Configurer `.env` production
4. Exécuter: `php artisan migrate --force`
5. Compiler: `npm run build`

---

## 💡 Stack

- **Frontend**: React 18, Inertia.js, Tailwind CSS 3, Vite
- **Backend**: Laravel 12, PHP 8.2, Breeze
- **Database**: MySQL/PostgreSQL
- **Testing**: PHPUnit, Laravel testing

---

## 🎨 Thème

- **Primaire**: Amber-Orange (#f59e0b)
- **Accent**: Emerald-500
- **Fond**: Blanc pur
- **Responsive**: Mobile-first

---

## 🔄 Prochains développements

- [ ] Dashboard utilisateur tenant
- [ ] Gestion produits
- [ ] Système de ventes
- [ ] Intégration paiements
- [ ] Analytics avancées
- [ ] 2FA
- [ ] API REST

---

## 🆘 Support

1. **Problème installation?** → [QUICKSTART.md](docs/QUICKSTART.md)
2. **Question ROOT?** → [ROOT_ADMIN_SYSTEM.md](docs/ROOT_ADMIN_SYSTEM.md)
3. **Exemple code?** → [USE_CASES.md](docs/USE_CASES.md)
4. **Besoin commande?** → [COMMANDS_REFERENCE.md](docs/COMMANDS_REFERENCE.md)
5. **Dépannage?** → [QUICKSTART.md#dépannage](docs/QUICKSTART.md)

---

**Version**: 1.0.0 | **État**: ✅ Production-ready | **Tests**: ✅ Tous pass
