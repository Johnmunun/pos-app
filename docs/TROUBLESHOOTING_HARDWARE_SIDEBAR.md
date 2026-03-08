# 🔧 Résolution : Module Hardware n'apparaît pas dans la Sidebar

## Problème
Après avoir exécuté le seeder `DefaultSectorRolesSeeder`, le module Hardware n'apparaît pas dans la sidebar.

## Causes possibles

### 1. Le rôle n'a pas été assigné à l'utilisateur
Le seeder crée le rôle "Vendeur Hardware", mais il doit être **assigné manuellement** à l'utilisateur.

### 2. L'utilisateur n'a pas rechargé la session
Les permissions sont chargées au moment de la connexion. Il faut se **déconnecter et se reconnecter**.

### 3. Le secteur du tenant n'est pas "hardware"
Le module Hardware n'apparaît que si le tenant a le secteur "hardware" OU si l'utilisateur a la permission `module.hardware`.

## Solutions

### Solution 1 : Assigner le rôle via l'interface Commerce

1. **Se connecter en tant que propriétaire du tenant** (TENANT_ADMIN ou MERCHANT)
2. **Aller dans le module Commerce** : `/commerce/sellers`
3. **Créer ou modifier un vendeur**
4. **Dans le drawer, sélectionner le rôle "Vendeur Hardware"**
5. **Sauvegarder**

### Solution 2 : Assigner le rôle via Tinker (pour tester)

```bash
php artisan tinker
```

```php
// 1. Trouver le rôle "Vendeur Hardware"
$role = App\Models\Role::where('name', 'Vendeur Hardware')->whereNull('tenant_id')->first();
echo "Rôle trouvé: " . $role->name . " (ID: " . $role->id . ")\n";

// 2. Trouver votre utilisateur
$user = App\Models\User::where('email', 'votre-email@example.com')->first();
echo "Utilisateur trouvé: " . $user->name . " (ID: " . $user->id . ", Tenant: " . $user->tenant_id . ")\n";

// 3. Assigner le rôle au tenant
$tenantId = $user->tenant_id;
DB::table('user_role')->insert([
    'user_id' => $user->id,
    'role_id' => $role->id,
    'tenant_id' => $tenantId,
    'created_at' => now(),
    'updated_at' => now(),
]);

// 4. Vérifier les permissions
$user->refresh();
$permissions = $user->permissionCodes();
echo "Permissions: " . implode(', ', $permissions) . "\n";

// Vérifier si module.hardware est présent
if (in_array('module.hardware', $permissions)) {
    echo "✅ Permission module.hardware trouvée!\n";
} else {
    echo "❌ Permission module.hardware manquante!\n";
}
```

### Solution 3 : Vérifier le secteur du tenant

```php
// Dans Tinker
$user = App\Models\User::where('email', 'votre-email@example.com')->first();
$tenant = App\Models\Tenant::find($user->tenant_id);
echo "Secteur du tenant: " . ($tenant->sector ?? 'non défini') . "\n";

// Si le secteur n'est pas "hardware", le définir
if ($tenant->sector !== 'hardware') {
    $tenant->sector = 'hardware';
    $tenant->save();
    echo "✅ Secteur mis à jour vers 'hardware'\n";
}
```

### Solution 4 : Vérifier que le rôle existe et a les bonnes permissions

```php
// Dans Tinker
$role = App\Models\Role::where('name', 'Vendeur Hardware')->whereNull('tenant_id')->first();

if (!$role) {
    echo "❌ Le rôle 'Vendeur Hardware' n'existe pas. Exécutez le seeder:\n";
    echo "php artisan db:seed --class=DefaultSectorRolesSeeder\n";
} else {
    echo "✅ Rôle trouvé: " . $role->name . "\n";
    $permissions = $role->permissions->pluck('code')->toArray();
    echo "Permissions (" . count($permissions) . "):\n";
    foreach ($permissions as $perm) {
        echo "  - $perm\n";
    }
    
    // Vérifier la permission essentielle
    if (in_array('module.hardware', $permissions)) {
        echo "✅ Permission module.hardware présente\n";
    } else {
        echo "❌ Permission module.hardware manquante!\n";
    }
}
```

## Checklist de vérification

- [ ] Le seeder a été exécuté : `php artisan db:seed --class=DefaultSectorRolesSeeder`
- [ ] Le rôle "Vendeur Hardware" existe dans la base de données
- [ ] Le rôle a la permission `module.hardware`
- [ ] Le rôle a été assigné à l'utilisateur (table `user_role`)
- [ ] Le secteur du tenant est "hardware" OU l'utilisateur a `module.hardware`
- [ ] L'utilisateur s'est déconnecté et reconnecté après l'assignation

## Après l'assignation

1. **Se déconnecter complètement**
2. **Se reconnecter**
3. **Vérifier la sidebar** - Le module "Quincaillerie" devrait apparaître

## Commandes utiles

```bash
# Vérifier les rôles créés
php artisan tinker
>>> App\Models\Role::whereNull('tenant_id')->where('name', 'like', '%Vendeur%')->get(['id', 'name']);

# Vérifier les permissions d'un utilisateur
php artisan tinker
>>> $user = App\Models\User::where('email', 'votre-email@example.com')->first();
>>> $user->permissionCodes();

# Vérifier le secteur du tenant
php artisan tinker
>>> $user = App\Models\User::where('email', 'votre-email@example.com')->first();
>>> App\Models\Tenant::find($user->tenant_id)->sector;
```

## Si le problème persiste

1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier la console du navigateur (F12) pour les erreurs JavaScript
3. Vérifier que les permissions sont bien passées au frontend dans `HandleInertiaRequests.php`
