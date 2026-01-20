# 📦 Tenant Domain - README

## Vue d'ensemble

Le **Tenant Domain** est la base fondationnelle du système POS SaaS.
Il implémente le multi-tenancy et l'isolation complète des données.

## ✅ Statut d'implémentation

### Domain (100% COMPLÉTÉ) ✓

- ✓ `Entities/Tenant.php` - Entity complète avec factory methods
- ✓ `ValueObjects/TenantCode.php` - Code unique validé
- ✓ `ValueObjects/TenantName.php` - Nom commercial validé
- ✓ `ValueObjects/TenantEmail.php` - Email validé
- ✓ `Repositories/TenantRepository.php` - Interface du repository
- ✓ `Services/TenantService.php` - Logique métier
- ✓ `Exceptions/InvalidTenantStateException.php` - Exception métier
- ✓ `Exceptions/DuplicateTenantCodeException.php` - Exception métier
- ✓ `UseCases/CreateTenantUseCase.php` - Créer un tenant
- ✓ `UseCases/ActivateTenantUseCase.php` - Activer un tenant

### Infrastructure (À FAIRE)

- ⬜ `app/Models/Tenant.php` - Model Eloquent
- ⬜ `app/Repositories/EloquentTenantRepository.php` - Implémentation
- ⬜ `database/migrations/create_tenants_table.php` - Schéma DB

### Application (À FAIRE)

- ⬜ `app/Http/Controllers/TenantController.php` - Endpoints HTTP
- ⬜ `app/Http/Requests/StoreTenantRequest.php` - Validation
- ⬜ `app/Http/Requests/UpdateTenantRequest.php` - Validation
- ⬜ `app/Http/Resources/TenantResource.php` - Sérialisation

### Tests (À FAIRE)

- ⬜ `tests/Unit/Domains/Tenant/...` - Tests unitaires
- ⬜ `tests/Feature/TenantControllerTest.php` - Tests d'intégration

---

## Architecture du Domain

### Hiérarchie des dépendances

```
Controllers (app/Http/Controllers)
    ↓ injecte
UseCases (src/Domains/Tenant/UseCases)
    ↓ utilise
Services (src/Domains/Tenant/Services)
    ↓ utilise
Entities + ValueObjects (src/Domains/Tenant/...)
Repositories Interface (src/Domains/Tenant/Repositories)
    ↑ implémentée par
Repository Eloquent (app/Repositories)
    ↓ utilise
Model Eloquent (app/Models)
    ↓ utilise
Database
```

### Flux d'une requête

```
HTTP POST /api/tenants
    ↓
StoreTenantRequest (validation)
    ↓
TenantController@store()
    ↓
CreateTenantUseCase->execute()
    ↓
TenantService->createTenant()
    ↓
Tenant::createNew() [factory + validation]
    ↓
TenantRepository->save()
    ↓
EloquentTenantRepository [persiste en DB]
    ↓
Response JSON
```

---

## Concepts clés

### Entity: Tenant

L'entity `Tenant` représente un commerçant/boutique.

**Caractéristiques:**

- ✓ Immuabilité du code (ne peut pas changer)
- ✓ Value Objects auto-validés
- ✓ Factory methods (`createNew()`, `hydrate()`)
- ✓ Logique métier encapsulée

**Exemples d'utilisation:**

```php
// Créer un nouveau tenant (pas encore en DB)
$tenant = Tenant::createNew(
    code: 'SHOP001',
    name: 'Ma Boutique SARL',
    email: 'contact@shop.com'
);

// Activer le tenant (logique métier)
$tenant->activate(); // Lance exception si déjà actif

// Accéder aux propriétés
$tenant->getCode()->getValue();      // "SHOP001"
$tenant->getName()->getValue();      // "Ma Boutique SARL"
$tenant->getEmail()->getValue();     // "contact@shop.com"

// Modifier le tenant
$tenant->updateName('Nouvelle Boutique');

// Persister (via le service + repository)
$persistedTenant = $service->createTenant(...);
$persistedTenant->getId(); // 1
```

### Value Objects

Les Value Objects valident et encapsulent les données simples.

**TenantCode:**

- Format: 3-10 caractères, majuscules et chiffres uniquement
- Immuable
- Comparable
- Auto-validée au construction

```php
$code = new TenantCode('SHOP001'); // ✓ OK
$code = new TenantCode('shop');    // ✗ Erreur: trop court
$code = new TenantCode('AB');      // ✗ Erreur: trop court
$code = new TenantCode('SHOP_001'); // ✗ Erreur: underscore interdit
```

**TenantName:**

- Format: 3-255 caractères, texte libre
- Nettoyage des espaces

```php
$name = new TenantName('Ma Boutique SARL');  // ✓ OK
$name = new TenantName('Ma');                // ✗ Erreur: trop court
$name = new TenantName('');                  // ✗ Erreur: vide
```

**TenantEmail:**

- Format: Email valide RFC 5322
- Normalisation en minuscules

```php
$email = new TenantEmail('contact@shop.com');     // ✓ OK
$email = new TenantEmail('invalid-email');        // ✗ Erreur: format invalide
$email = new TenantEmail('CONTACT@SHOP.COM');     // ✓ OK (normalisé)
```

### Service: TenantService

Le service orchestre la logique métier.

**Responsabilités:**

- Vérifier l'unicité du code avant création
- Créer et hydrater les entities
- Appeler le repository pour persister

```php
$service = new TenantService($repository);

// Créer un tenant
$tenant = $service->createTenant('SHOP001', 'Ma Boutique', 'contact@shop.com');
// Lance DuplicateTenantCodeException si code existe

// Activer un tenant
$tenant = $service->activateTenant($tenantId);
// Lance InvalidTenantStateException si déjà actif

// Mettre à jour un tenant
$tenant = $service->updateTenant($tenantId, name: 'Nouvelle Boutique');

// Récupérer un tenant
$tenant = $service->getTenant($tenantId);
$tenant = $service->getTenantByCode('SHOP001');
```

### Repository Interface

Le repository abstrait la persistance.

```php
interface TenantRepository
{
    public function findById(int $id): ?Tenant;
    public function findByCode(string $code): ?Tenant;
    public function findByEmail(string $email): ?Tenant;
    public function getAll(): array;
    public function getAllActive(): array;
    public function save(Tenant $tenant): Tenant;
    public function delete(int $id): bool;
    public function codeExists(string $code): bool;
    public function emailExists(string $email): bool;
}
```

### Use Cases

Les use cases orchestrent le flux applicatif.

**CreateTenantUseCase:**

```php
$useCase = new CreateTenantUseCase($service);
$response = $useCase->execute('SHOP001', 'Ma Boutique', 'contact@shop.com');

if ($response->isSuccess()) {
    $tenantId = $response->getTenantId();
} else {
    $errorCode = $response->getErrorCode(); // DUPLICATE_CODE, VALIDATION_ERROR, ...
}
```

---

## Gestion des erreurs

### Exceptions métier

**DuplicateTenantCodeException**

```php
try {
    $tenant = $service->createTenant('EXISTING_CODE', 'Name', 'email@test.com');
} catch (DuplicateTenantCodeException $e) {
    // Le code existe déjà
    // Afficher un message utilisateur
}
```

**InvalidTenantStateException**

```php
try {
    $tenant->activate(); // Déjà actif
} catch (InvalidTenantStateException $e) {
    // Le tenant est déjà actif
}
```

### Exceptions de validation

```php
try {
    $code = new TenantCode('AB'); // Trop court
} catch (\InvalidArgumentException $e) {
    // "TenantCode must be between 3 and 10 characters"
}
```

---

## Tests

### Tests unitaires du Domain

```php
// tests/Unit/Domains/Tenant/ValueObjects/TenantCodeTest.php

public function testValidCode()
{
    $code = new TenantCode('SHOP001');
    $this->assertEquals('SHOP001', $code->getValue());
}

public function testInvalidCodeTooShort()
{
    $this->expectException(\InvalidArgumentException::class);
    new TenantCode('AB');
}

public function testInvalidCodeWithSpecialCharacters()
{
    $this->expectException(\InvalidArgumentException::class);
    new TenantCode('SHOP_001');
}
```

### Tests du Service

```php
// tests/Unit/Domains/Tenant/Services/TenantServiceTest.php

public function testCreateTenant()
{
    $repository = Mockery::mock(TenantRepository::class);
    $service = new TenantService($repository);

    // Mock: le code n'existe pas
    $repository->shouldReceive('codeExists')
        ->with('SHOP001')
        ->andReturn(false);

    // Mock: sauvegarder retourne le tenant persisté
    $repository->shouldReceive('save')
        ->andReturn(...);

    $tenant = $service->createTenant('SHOP001', 'Name', 'email@test.com');

    $this->assertNotNull($tenant->getId());
}

public function testCreateTenantWithDuplicateCode()
{
    $repository = Mockery::mock(TenantRepository::class);
    $service = new TenantService($repository);

    // Mock: le code existe déjà
    $repository->shouldReceive('codeExists')
        ->with('EXISTING')
        ->andReturn(true);

    $this->expectException(DuplicateTenantCodeException::class);
    $service->createTenant('EXISTING', 'Name', 'email@test.com');
}
```

---

## Prochaines étapes

### 1. Implémenter l'Infrastructure (Laravel)

```bash
php artisan make:model Tenant -m
# Créer app/Models/Tenant.php
# Créer app/Repositories/EloquentTenantRepository.php
# Créer la migration
```

### 2. Implémenter les Contrôleurs

```bash
php artisan make:controller TenantController --api
# app/Http/Controllers/TenantController.php
```

### 3. Créer les Tests

```bash
php artisan make:test Domains/Tenant/ValueObjects/TenantCodeTest --unit
php artisan make:test Domains/Tenant/Services/TenantServiceTest --unit
php artisan make:test TenantControllerTest --feature
```

### 4. Créer le Middleware

Middleware pour contextualiser le tenant courant dans chaque requête:

```php
// app/Http/Middleware/SetCurrentTenant.php
```

---

## Conventions

### Nommage

- **Entities**: `Tenant.php` (singulier)
- **Value Objects**: `TenantCode.php`, `TenantName.php`
- **Services**: `TenantService.php`
- **Repositories**: `TenantRepository.php` (interface), `EloquentTenantRepository.php`
- **Exceptions**: `InvalidTenantStateException.php`
- **Use Cases**: `CreateTenantUseCase.php`, `ActivateTenantUseCase.php`

### Commentaires

- Documenter chaque classe avec `/**` (docbloc)
- Documenter chaque méthode publique
- Expliquer la logique métier en commentaires
- Inclure des exemples d'utilisation

### Validation

- Valider au niveau du Value Object (auto-validation)
- Vérifier l'unicité au niveau du Service
- Le Repository ne contient pas de logique métier

---

## Ressources

- [docs/README.md](../README.md) - Vue d'ensemble du projet
- [docs/ARCHITECTURE.md](../ARCHITECTURE.md) - Patterns DDD
- [docs/DOMAINS.md](../DOMAINS.md) - Tous les domaines
