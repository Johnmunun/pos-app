# 🛠️ Commandes essentielles - POS SaaS

## 📦 Installation initiale

```bash
# Cloner le projet
git clone <repo> pos-saas
cd pos-saas

# Installer dépendances PHP
composer install

# Installer dépendances Node.js
npm install

# Copier configuration
cp .env.example .env

# Générer clé APP
php artisan key:generate
```

## 🗄️ Base de données

```bash
# Créer la base MySQL
mysql -u root -p -e "CREATE DATABASE pos_saas;"

# Exécuter migrations
php artisan migrate

# Réinitialiser base (danger!)
php artisan migrate:fresh

# Réinitialiser + seeder
php artisan migrate:fresh --seed

# Voir statut migrations
php artisan migrate:status
```

## 👤 Gestion utilisateur ROOT

```bash
# Créer l'utilisateur ROOT initial
php artisan db:seed --class=CreateRootUserSeeder

# Vérifier existence ROOT (Tinker)
php artisan tinker
>>> App\Models\User::where('type', 'ROOT')->first();

# Réinitialiser password ROOT
php artisan tinker
>>> $user = App\Models\User::where('type', 'ROOT')->first();
>>> $user->password = bcrypt('NouveauMotDePasse123');
>>> $user->save();
>>> exit;

# Voir tous les utilisateurs
php artisan tinker
>>> App\Models\User::all();

# Voir tous les tenants
php artisan tinker
>>> App\Models\Tenant::all();
```

## 🎨 Frontend

```bash
# Compiler assets (production)
npm run build

# Mode développement (watch)
npm run dev

# Linter code
npm run lint

# Format code
npm run format
```

## 🚀 Serveur

```bash
# Démarrer serveur de développement
php artisan serve

# Démarrer sur port spécifique
php artisan serve --port=8001

# Serveur + watcher assets
# Ouvrir 2 terminaux:
Terminal 1: npm run dev
Terminal 2: php artisan serve
```

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Exécuter un fichier de test
php artisan test tests/Feature/RootUserAccessTest.php

# Exécuter avec couverture
php artisan test --coverage

# Exécuter un test spécifique
php artisan test --filter RootUserAccessTest::test_root_user_can_access_admin_pages

# Exécuter en mode refresh (db reset)
php artisan test --refresh
```

## 🔍 Debugging

```bash
# Voir logs application
tail -f storage/logs/laravel.log

# Réinitialiser logs
> storage/logs/laravel.log

# Accéder à tinker REPL
php artisan tinker

# Dump et die variable
dd($variable);

# Dump simple
dump($variable);

# Exemples tinker:
>>> User::count()           // Nombre utilisateurs
>>> Tenant::all()           // Tous tenants
>>> User::find(1)           // Utilisateur ID 1
>>> User::where('type', 'ROOT')->first()  // ROOT user
>>> DB::table('users')->count()           // Count table
```

## 📝 Logs et cache

```bash
# Vider cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Tout vider
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# Vider cache spécifique
php artisan cache:forget nom_cache

# Voir cache
php artisan cache:show nom_cache
```

## 🔧 Artisan commands utiles

```bash
# Lister toutes les commandes
php artisan list

# Voir aide pour une commande
php artisan migrate --help

# Régénérer autoload
composer dump-autoload

# Optimiser autoload production
composer install --optimize-autoloader --no-dev

# Vérifier health
php artisan about

# Afficher une route
php artisan route:list | grep admin

# Voir toutes les routes
php artisan route:list
```

## 🚨 Dépannage courant

```bash
# "Class not found" erreur
composer dump-autoload

# Migrations en attente
php artisan migrate

# Vue compilée manquante
php artisan view:cache

# "Port already in use"
php artisan serve --port=8001

# Assets non compilés
npm run build

# Node modules corrompu
rm -rf node_modules
npm install

# Composer lock outdated
composer install
```

## 🔐 Sécurité

```bash
# Générer APP key
php artisan key:generate

# Générer API token (si besoin)
php artisan tinker
>>> $user = User::find(1);
>>> $token = $user->createToken('API Token')->plainTextToken;

# Générer password sécurisé
openssl rand -base64 32

# Voir variables sensibles
php artisan tinker
>>> env('APP_KEY')
>>> env('DB_PASSWORD')
```

## 📊 Gestion données

```bash
# Créer migration nouvelle
php artisan make:migration create_users_table

# Créer model avec migration
php artisan make:model User -m

# Créer controller
php artisan make:controller AdminController

# Créer seeder
php artisan make:seeder CreateRootUserSeeder

# Créer middleware
php artisan make:middleware CheckRootUser

# Créer request validation
php artisan make:request StoreUserRequest
```

## 🎯 Workflow développement complet

```bash
# 1. Démarrer
php artisan serve &
npm run dev &

# 2. Faire changements code

# 3. Compiler si besoin
# (npm run dev le fait automatiquement en watch)

# 4. Accéder
# http://localhost:8000

# 5. Tester
php artisan test

# 6. Arrêter serveur
# Ctrl+C (2x)
```

## 📱 Mobile/Autres devices

```bash
# Accéder depuis autre device
# Remplacer localhost par IP machine

# Voir IP machine (Windows PowerShell)
ipconfig

# Voir IP machine (Mac/Linux)
ifconfig

# Accéder: http://192.168.X.X:8000/
```

## 🌐 Production

```bash
# Build optimisé
npm run build

# Clear all caches
php artisan optimize:clear

# Generate cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations production
php artisan migrate --force

# Seed production
php artisan db:seed --class=CreateRootUserSeeder --force

# Check app status
php artisan about
```

## 🔗 URLs importantes

```
Landing:  http://localhost:8000/
Login:    http://localhost:8000/login
Register: http://localhost:8000/register
Dashboard: http://localhost:8000/dashboard
Profile: http://localhost:8000/profile

Admin (ROOT):
- Sélection: http://localhost:8000/admin/select-tenant
- Dashboard: http://localhost:8000/admin/tenant/{id}/dashboard
- Tenants: http://localhost:8000/admin/tenants
- Utilisateurs: http://localhost:8000/admin/users
```

## 📚 Fichiers de configuration importants

```
.env                           # Configuration environnement
.env.example                   # Template .env
config/app.php                # Config app
config/database.php           # Config DB
config/roles.php              # Rôles et permissions
config/filesystems.php        # Storage
vite.config.js                # Vite config
tailwind.config.js            # Tailwind config
```

## 🔄 Git workflow

```bash
# Status
git status

# Ajouter fichiers
git add .

# Commit
git commit -m "Feature: add ROOT admin panel"

# Push
git push origin main

# Voir logs
git log --oneline

# Voir diff
git diff
```

## 💾 Backup/Restore

```bash
# Backup base de données
mysqldump -u root -p pos_saas > backup.sql

# Restore
mysql -u root -p pos_saas < backup.sql

# Backup complet (DB + files)
tar -czf backup-$(date +%Y%m%d).tar.gz \
  .env storage/ database/ config/

# Restore complet
tar -xzf backup-2024XXXX.tar.gz
```

## ⚡ Performance

```bash
# Voir requêtes SQL (Debugbar)
# Ajouter dans .env
DEBUGBAR_ENABLED=true

# Profiler
php artisan tinker
>>> DB::enableQueryLog();
>>> // ... exécuter code ...
>>> DB::getQueryLog();

# Voir memory usage
php artisan tinker
>>> memory_get_usage();
```

## 🆘 Emergency commands

```bash
# Réinitialiser TOUT
php artisan migrate:fresh --seed
rm -rf storage/logs/*
php artisan cache:clear
npm run build

# Restaurer backup
mysql -u root -p pos_saas < backup.sql
php artisan migrate
php artisan db:seed --class=CreateRootUserSeeder

# Redémarrer tout proprement
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
npm run build
```

---

## 📖 Aide rapide

```bash
# Besoin d'aide?
php artisan help <commande>

# Exemple:
php artisan help migrate
php artisan help tinker
php artisan help test
```

**✅ Commandes sauvegardées et prêtes à l'emploi!**
