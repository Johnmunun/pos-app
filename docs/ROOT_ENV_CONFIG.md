# Configuration des variables d'environnement ROOT

## Environnement de développement

```bash
# .env.development
ROOT_USER_EMAIL=root@pos-saas.local
ROOT_USER_PASSWORD=RootPassword123
```

## Environnement de production

```bash
# .env.production
# Générer une clé très sécurisée
ROOT_USER_EMAIL=admin@votredomaine.com
ROOT_USER_PASSWORD=$(openssl rand -base64 32)
```

## Script d'initialisation

Pour automatiser la création du ROOT user avec variables d'environnement :

```php
// database/seeders/CreateRootUserSeeder.php
public function run()
{
    User::updateOrCreate(
        ['email' => env('ROOT_USER_EMAIL', 'root@pos-saas.local')],
        [
            'first_name' => 'Administrator',
            'last_name' => 'ROOT',
            'password' => bcrypt(env('ROOT_USER_PASSWORD', 'RootPassword123')),
            'type' => 'ROOT',
            'tenant_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]
    );
}
```

## Sécurité en production

1. **Générer un mot de passe fort :**
```bash
openssl rand -base64 32
# Exemple output: 7K/x8mPq2N3vL9Z+hR4jQ8wXyE5sA0bC1dF6gH2iJ
```

2. **Configurer dans .env production :**
```
ROOT_USER_EMAIL=admin@votredomaine.com
ROOT_USER_PASSWORD=7K/x8mPq2N3vL9Z+hR4jQ8wXyE5sA0bC1dF6gH2iJ
```

3. **Exécuter après déploiement :**
```bash
php artisan migrate --force
php artisan db:seed --class=CreateRootUserSeeder --force
```

4. **Changer le mot de passe après première connexion :**
   - Se connecter au dashboard admin
   - Aller à /profile
   - Changer le mot de passe

## Variables supplémentaires recommandées

```env
# Email notifications
ROOT_NOTIFICATION_EMAIL=notifications@votredomaine.com
ROOT_LOG_ACTIVITIES=true

# Deux facteurs d'authentification (à implémenter)
ROOT_2FA_ENABLED=false
```
