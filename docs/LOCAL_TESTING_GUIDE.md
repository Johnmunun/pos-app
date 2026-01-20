# 🧪 Guide de test local - POS SaaS ROOT Admin System

## 📋 Checklist de test complète

### Phase 1: Installation (5-10 min)

- [ ] Clone/télécharger le projet
- [ ] `composer install`
- [ ] `npm install`
- [ ] Copier `.env.example` → `.env`
- [ ] `php artisan key:generate`
- [ ] Configurer DB dans `.env` (MySQL/Postgres)
- [ ] `php artisan migrate`
- [ ] `php artisan db:seed --class=CreateRootUserSeeder`

### Phase 2: Build & Démarrage (2-5 min)

- [ ] `npm run build`
- [ ] Terminal 1: `npm run dev` (watch mode)
- [ ] Terminal 2: `php artisan serve`
- [ ] Accéder http://localhost:8000

### Phase 3: Landing Page (2-3 min)

- [ ] Landing visible
- [ ] Tous les éléments présents:
  - [ ] Header avec logo
  - [ ] Hero section
  - [ ] Features grid
  - [ ] Testimonials
  - [ ] Pricing
  - [ ] Footer
- [ ] Couleurs amber-orange visibles
- [ ] Responsive design OK (devtools mobile)

### Phase 4: Authentication (5 min)

#### Login
- [ ] Accéder `/login`
- [ ] Voir formulaire blanc avec inputs
- [ ] Focus rings ambr OK
- [ ] Se connecter ROOT:
  - Email: `root@pos-saas.local`
  - Password: `RootPassword123`
- [ ] Redirection auto → `/admin/select-tenant` ✅

#### Register
- [ ] Accéder `/register`
- [ ] Voir champs: company_name, name, email, password
- [ ] Remplir avec données de test:
  - company_name: "Test Shop"
  - name: "Jean Dupont"
  - email: "jean@test.local"
  - password: "SecurePass123"
- [ ] Soumettre
- [ ] Redirection → `/dashboard`
- [ ] Vérifier nouveau tenant créé via Tinker:
  ```php
  php artisan tinker
  >>> Tenant::latest()->first()  # Vérifier "Test Shop"
  >>> User::where('email', 'jean@test.local')->first()  # type='TENANT_ADMIN'
  ```

### Phase 5: ROOT Admin Panel (10-15 min)

#### SelectTenant Page
- [ ] Se reconnecter ROOT (logout d'abord)
- [ ] Redirection auto → `/admin/select-tenant`
- [ ] Voir titre "Bienvenue, Administrator"
- [ ] Voir grille des tenants:
  - [ ] "Test Shop" visible
  - [ ] Badges statut (Actif)
  - [ ] User counts (1)
  - [ ] Dates de création

#### Tenant Dashboard
- [ ] Cliquer sur tenant "Test Shop"
- [ ] Page `/admin/tenant/{id}/dashboard` chargée
- [ ] Voir statistiques:
  - [ ] "Total users" = 1
  - [ ] "Utilisateurs actifs" = 1
  - [ ] "Dernière activité" visible
- [ ] Voir tableau utilisateurs:
  - [ ] Jean Dupont en ligne
  - [ ] Email: jean@test.local
  - [ ] Rôle: Admin Tenant (amber badge)
  - [ ] Statut: Actif (emerald)
  - [ ] Bouton "Désactiver"

#### Toggle User Action
- [ ] Clicker "Désactiver" sur Jean Dupont
- [ ] Voir loader spinner
- [ ] Utilisateur row change (statut = Inactif)
- [ ] Bouton devient "Activer"
- [ ] Vérifier BD via Tinker:
  ```php
  >>> User::where('email', 'jean@test.local')->first()->is_active
  ```

#### Manage Tenants Page
- [ ] Clicker lien "Gérer les tenants" (bottom SelectTenant)
- [ ] Page `/admin/tenants` chargée
- [ ] Voir tableau:
  - [ ] "Test Shop" listée
  - [ ] Slug affiché
  - [ ] User count = 1
  - [ ] Statut = Actif
  - [ ] Bouton "Désactiver"
- [ ] Clicker "Désactiver"
- [ ] Voir tenant devenir Inactif

#### Manage Users Page
- [ ] Clicker lien "Gérer les utilisateurs" (bottom SelectTenant)
- [ ] Page `/admin/users` chargée
- [ ] Voir utilisateurs groupés par tenant:
  - [ ] "Test Shop" section
  - [ ] Jean Dupont listé
  - [ ] ROOT user listé (type = Administrateur)
- [ ] Bouton toggle sur Jean Dupont
- [ ] Ne peut pas désactiver ROOT (aucun bouton/grisé)

### Phase 6: Logout & Re-login (2 min)

- [ ] Logout ROOT (menu profile top-right)
- [ ] Redirected → `/`
- [ ] Se reconnecter ROOT
- [ ] Redirection auto → `/admin/select-tenant` ✅

- [ ] Logout ROOT
- [ ] Voir formulaire login
- [ ] Se connecter avec Jean (jean@test.local / SecurePass123)
- [ ] Redirection → `/dashboard` (pas `/admin/select-tenant`)

### Phase 7: Tests automatisés (2 min)

```bash
# Terminal 3
php artisan test tests/Feature/RootUserAccessTest.php

# Vérifier:
# ✓ root_user_can_access_admin_pages
# ✓ non_root_user_cannot_access_admin_pages
# ✓ unauthenticated_user_is_redirected_from_admin
# ✓ root_user_redirected_to_admin_after_login
# ✓ normal_user_redirected_to_dashboard_after_login
```

### Phase 8: Erreurs & Logs (1-2 min)

- [ ] Ouvrir devtools (F12)
- [ ] Onglet Console: aucun erreur rouge
- [ ] Onglet Network: 
  - [ ] Les requêtes réussissent (200)
  - [ ] Pas de 403/404/500
- [ ] Fichier logs: `storage/logs/laravel.log`
  - [ ] Aucune erreur critique

### Phase 9: Browser compatibility (3-5 min)

- [ ] Chrome/Edge - OK
- [ ] Firefox - OK
- [ ] Safari - OK
- [ ] Mobile view (devtools) - OK

---

## 🐛 Problèmes courants & solutions

### "Impossible to connect to database"
```bash
# Solution:
# 1. Vérifier .env (DB_* variables)
# 2. Créer database: mysql -u root -e "CREATE DATABASE pos_saas;"
# 3. Tester: php artisan migrate
```

### "Class not found"
```bash
# Solution:
composer dump-autoload
```

### "npm: command not found"
```bash
# Solution:
# Installer Node.js depuis https://nodejs.org/
```

### "Assets not compiled"
```bash
# Solution:
npm run build
```

### ROOT user introuvable
```bash
# Solution:
php artisan db:seed --class=CreateRootUserSeeder
```

### Tenants/Users pas affichés
```bash
# Solution:
# 1. Vérifier migrations: php artisan migrate:status
# 2. Reset: php artisan migrate:fresh --seed
# 3. Vérifier DB directement:
php artisan tinker
>>> Tenant::count()
>>> User::count()
```

---

## ✅ Checklist finale

Avant de considérer le système comme "prêt":

- [ ] Installation complète sans erreur
- [ ] Landing page affichée correctement
- [ ] Login/Register fonctionnent
- [ ] ROOT redirected to admin panel
- [ ] Tous les pages admin accessibles
- [ ] Tableaux affichent les bonnes données
- [ ] Boutons toggle fonctionnent
- [ ] Tests passent (5/5)
- [ ] Aucune erreur console
- [ ] Responsive design OK
- [ ] Thème couleurs OK (amber-orange)

---

## 🎯 Cas de test suggérés

### Test 1: Créer multiple tenants
```
1. Logout ROOT
2. Register 3 tenants différents
3. Reconnect ROOT
4. Vérifier tous les 3 affichés
```

### Test 2: Toggle cascade
```
1. Désactiver tenant
2. Vérifier utilisateurs pas affectés (bug ou pas?)
3. Réactiver tenant
```

### Test 3: Permissions
```
1. Connecter utilisateur normal
2. Essayer d'accéder /admin/users
3. Vérifier redirection vers home
```

### Test 4: Email verification
```
1. Register nouvel utilisateur
2. Vérifier email nécessaire? (si Breeze configuré)
3. Clicker lien email
4. Vérifier email_verified_at
```

---

## 📊 Performance checks

- [ ] Landing charge < 2s
- [ ] Admin pages < 1s
- [ ] No console errors
- [ ] Network tab clean
- [ ] Devtools Lighthouse score > 80

---

## 📝 Rapporter les bugs

Si vous trouvez un bug:

1. Noter le scénario exact
2. Screenshot ou vidéo
3. Vérifier les logs: `storage/logs/laravel.log`
4. Vérifier console devtools
5. Documenter: email/Slack/Github issue

Format:
```
Bug: [Description brève]
Étapes:
1. ...
2. ...
3. ...
Résultat attendu: ...
Résultat réel: ...
Environment: [Laravel 12, PHP 8.2, React 18]
Logs: [Copier errors du log]
```

---

## 🎓 Fichiers à examiner

Pour mieux comprendre le système:

1. **AdminController.php** - Logique backend
2. **SelectTenant.jsx** - Interface tenant selection
3. **TenantDashboard.jsx** - Stats et tableau users
4. **CheckRootUser.php** - Middleware protection
5. **AuthenticatedSessionController.php** - Login redirect
6. **config/roles.php** - Rôles définis
7. **routes/web.php** - Routes admin

---

## 🏁 Test Completion

**Temps total estimé**: 30-40 minutes

Après completion:
- ✅ Système est fonctionnel
- ✅ Prêt pour développement futur
- ✅ Prêt pour production (après checklist de déploiement)

---

**Bonne chance au testing! 🧪**
