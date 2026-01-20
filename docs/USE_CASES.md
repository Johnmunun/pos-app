# 📚 Cas d'usage - Système ROOT Admin

## Cas d'usage 1: ROOT crée un nouveau tenant manuellement

**Scénario:** L'administrateur ROOT veut créer un nouveau tenant sans que l'utilisateur s'inscrive.

**Étapes:**
1. ROOT se connecte: `root@pos-saas.local`
2. Va à `/admin/tenants`
3. Clique "Créer un nouveau tenant" (à implémenter)
4. Remplit: nom, slug, email admin
5. Tenant est créé avec un utilisateur TENANT_ADMIN
6. Un email est envoyé à l'administrateur du tenant

**Fichiers à modifier:**
```php
// AdminController.php - Ajouter méthode
public function createTenant(Request $request) { }
public function storeTenant(Request $request) { }

// CreateTenant.jsx - Nouveau formulaire
```

---

## Cas d'usage 2: ROOT désactive un utilisateur problématique

**Scénario:** Un utilisateur pose problème, ROOT doit le désactiver rapidement.

**Étapes:**
1. ROOT se connecte
2. Va à `/admin/users`
3. Trouve l'utilisateur dans le tableau
4. Clique "Désactiver"
5. Utilisateur ne peut plus se connecter

**Code déjà implémenté:**
```jsx
// ManageUsers.jsx
const handleToggleUser = (userId) => {
    router.post(route('admin.user.toggle', userId), {});
};

// AdminController::toggleUser()
public function toggleUser($id) {
    $user = User::findOrFail($id);
    
    // Prévention: ne pas désactiver ROOT
    if ($user->type === 'ROOT') {
        return redirect()->back()->with('error', 'Cannot disable ROOT user');
    }
    
    $user->update(['is_active' => !$user->is_active]);
    return redirect()->back();
}
```

---

## Cas d'usage 3: ROOT vérifie la santé d'un tenant

**Scénario:** ROOT veut vérifier que tout va bien avec un tenant spécifique.

**Étapes:**
1. ROOT se connecte → `/admin/select-tenant`
2. Sélectionne le tenant → `/admin/tenant/{id}/dashboard`
3. Voit:
   - Nombre total d'utilisateurs
   - Nombre d'utilisateurs actifs
   - Dernière activité (utilisateur, timestamp)
   - Table des utilisateurs avec leurs rôles et statuts
4. Peut désactiver/activer des utilisateurs si besoin

**Données disponibles:**
```php
// TenantDashboard.jsx reçoit:
{
    tenant: {
        id: 1,
        name: "Ma Boutique",
        slug: "ma-boutique",
        is_active: true,
        users_count: 15,
        active_users: 12,
        last_activity: {
            user_name: "Jean Dupont",
            timestamp: "2024-01-15 14:30:00"
        }
    },
    users: [
        {
            id: 5,
            name: "Jean Dupont",
            email: "jean@boutique.com",
            type: "TENANT_ADMIN",
            is_active: true,
            created_at: "2024-01-01"
        },
        // ... autres utilisateurs
    ]
}
```

---

## Cas d'usage 4: ROOT réactive un tenant suspendu

**Scénario:** Un tenant a été suspendu, ROOT veut le réactiver.

**Étapes:**
1. ROOT va à `/admin/tenants`
2. Cherche le tenant dans le tableau (statut = "Inactif")
3. Clique "Activer"
4. Tenant peut être utilisé à nouveau

**Code implémenté:**
```jsx
// ManageTenants.jsx
<button onClick={() => handleToggleTenant(tenant.id)}>
    {tenant.is_active ? 'Désactiver' : 'Activer'}
</button>

// AdminController::toggleTenant()
public function toggleTenant($id) {
    $tenant = Tenant::findOrFail($id);
    
    // Prévention: tous les tenants peuvent être toggle
    $tenant->update(['is_active' => !$tenant->is_active]);
    
    return redirect()->back();
}
```

---

## Cas d'usage 5: ROOT exporte les données d'un tenant

**Scénario:** ROOT veut exporter toutes les données d'un tenant (statuts légaux, etc).

**À implémenter:**
```php
// AdminController.php - Nouvelle méthode
public function exportTenant($id) {
    $tenant = Tenant::with('users')->findOrFail($id);
    
    $data = [
        'tenant' => $tenant,
        'users' => $tenant->users,
        'exported_at' => now(),
    ];
    
    return Excel::download(new TenantExport($data), "tenant-{$id}.xlsx");
}

// routes/web.php
Route::get('/admin/tenant/{id}/export', [AdminController::class, 'exportTenant'])
    ->name('admin.tenant.export');
```

---

## Cas d'usage 6: Audit des actions ROOT

**Scénario:** Pour la conformité, ROOT veut voir l'historique de ses actions.

**À implémenter:**
```php
// Traits/LogsActivity.php
trait LogsActivity {
    protected static function booted() {
        static::created(function ($model) {
            if (auth()->check() && auth()->user()->type === 'ROOT') {
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'created',
                    'model' => get_class($model),
                    'model_id' => $model->id,
                ]);
            }
        });
    }
}

// AdminController.php
public function auditLog() {
    $logs = AuditLog::latest()->paginate(50);
    return Inertia::render('Admin/AuditLog', ['logs' => $logs]);
}
```

---

## Cas d'usage 7: ROOT change son mot de passe

**Scénario:** ROOT veut changer son mot de passe pour la sécurité.

**Étapes:**
1. ROOT clique sur son profil (coin top-right)
2. Accède à `/profile`
3. Remplit ancien + nouveau mot de passe
4. Soumet

**Déjà implémenté avec Breeze:**
```jsx
// ProfileController.php
public function update(ProfileUpdateRequest $request) {
    $request->user()->fill($request->validated());
    
    if ($request->user()->isDirty('email')) {
        $request->user()->email_verified_at = null;
    }
    
    $request->user()->save();
}
```

---

## Cas d'usage 8: Un utilisateur oublie son mot de passe

**Scénario:** Un utilisateur tenant veut réinitialiser son mot de passe.

**Étapes:**
1. Utilisateur va à `/forgot-password`
2. Entre son email
3. Reçoit email avec lien de réinitialisation
4. Clique lien, rentre nouveau mot de passe
5. Peut se reconnecter

**Déjà implémenté avec Breeze:**
```php
// Routes - routes/auth.php
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');
```

---

## Cas d'usage 9: ROOT assigne un nouvel utilisateur à un tenant

**Scénario:** ROOT veut ajouter un nouvel utilisateur à un tenant existant.

**À implémenter:**
```php
// AdminController.php
public function addUserToTenant(Request $request, $tenantId) {
    $validated = $request->validate([
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'email' => 'required|email|unique:users',
        'type' => 'required|in:MERCHANT,SELLER,STAFF',
        'password' => 'required|min:8',
    ]);
    
    $user = User::create([
        'tenant_id' => $tenantId,
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'],
        'type' => $validated['type'],
        'password' => bcrypt($validated['password']),
        'is_active' => true,
    ]);
    
    // Optionnel: Envoyer email avec identifiants
    Mail::send('emails.user-created', [
        'user' => $user,
        'password' => $validated['password'],
    ], function ($m) use ($user) {
        $m->to($user->email);
    });
    
    return redirect()->back()->with('success', 'User created');
}
```

---

## Cas d'usage 10: ROOT génère un rapport de tous les tenants

**Scénario:** ROOT veut une vue complète de la plateforme.

**À implémenter:**
```php
// AdminController.php
public function platformReport() {
    $tenants = Tenant::with(['users' => function ($q) {
        $q->select('id', 'tenant_id', 'type', 'is_active');
    }])->get();
    
    $report = $tenants->map(function ($tenant) {
        return [
            'name' => $tenant->name,
            'users_count' => $tenant->users->count(),
            'active_users' => $tenant->users->where('is_active', true)->count(),
            'admins' => $tenant->users->where('type', 'TENANT_ADMIN')->count(),
            'status' => $tenant->is_active ? 'Active' : 'Inactive',
        ];
    });
    
    return Inertia::render('Admin/PlatformReport', ['report' => $report]);
}
```

---

## 🎯 Workflow complèt: Du landing au panel admin

```
1. Visiteur accède landing → http://localhost:8000/
   ↓
2. Clique "Vendre" → http://localhost:8000/register
   ↓
3. Remplit inscription (company_name, name, email, password)
   ↓
4. RegisteredUserController crée:
   - Nouveau tenant
   - Nouvel utilisateur (TENANT_ADMIN)
   ↓
5. Utilisateur redirigé → /dashboard
   ↓
6. ROOT se connecte → /login
   Email: root@pos-saas.local
   Password: RootPassword123
   ↓
7. AuthenticatedSessionController détecte type='ROOT'
   ↓
8. ROOT redirigé → /admin/select-tenant
   ↓
9. ROOT voit tous les tenants créés
   ↓
10. Clique sur le nouveau tenant → /admin/tenant/{id}/dashboard
    ↓
11. Voit stats du tenant et ses utilisateurs
    ↓
12. Peut gérer: tenants globalement, utilisateurs globalement
```

---

## 📊 Matrice de permissions

| Action | ROOT | TENANT_ADMIN | MERCHANT | SELLER | STAFF |
|--------|------|--------------|----------|--------|-------|
| Voir tous tenants | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gérer tenant | ✅ | ❌ | ❌ | ❌ | ❌ |
| Voir tous utilisateurs | ✅ | ❌ | ❌ | ❌ | ❌ |
| Créer utilisateur tenant | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gérer utilisateur tenant | ✅ | ✅ | ❌ | ❌ | ❌ |
| Voir tenant | ✅ | ✅ | ✅ | ✅ | ✅ |
| Accéder dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Créer vente | ❌ | ✅ | ✅ | ✅ | ❌ |
| Voir ventes | ❌ | ✅ | ✅ | ✅ | ✅ |

---

## 🔐 Sécurité par cas d'usage

### Root user security
- ✅ Hachage du mot de passe (bcrypt)
- ✅ Impossible de désactiver ROOT
- ✅ Middleware obligatoire
- ⏳ À ajouter: 2FA, logs d'audit, rate limiting

### Tenant isolation
- ✅ Vérification tenant_id
- ⏳ À ajouter: Row-level security, encryption des données sensibles

### User permissions
- ✅ Vérification du type d'utilisateur
- ⏳ À ajouter: Permission-based access control (PBAC)

---

## 📈 Métriques pour monitoring

```php
// Ajouter monitoring
- Nombre de connexions ROOT par jour
- Nombre d'utilisateurs désactivés
- Nombre de tenants crées/supprimés
- Tentatives de accès non autorisé
- Durée moyenne de session ROOT
```

---

**✅ Tous les cas d'usage courants sont couverts ou documentés pour implémentation future.**
