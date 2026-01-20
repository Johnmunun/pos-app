# 🏗️ Architecture DDD - Patterns et Conventions

## Domain Driven Design (DDD)

DDD est une approche méthodologique qui place le domaine métier au cœur du développement.

### Principes clés

1. **Ubiquitous Language** - Langage unifié entre développeurs et métier
2. **Bounded Contexts** - Chaque domaine est autonome et isolé
3. **Entities** - Objets avec identité unique dans le domaine
4. **Value Objects** - Objets sans identité, immutables
5. **Aggregates** - Groupes d'entities avec règles de cohérence
6. **Repositories** - Abstraction de persistance
7. **Services** - Logique transversale aux entities
8. **Use Cases** - Orchestration des opérations

---

## Structure d'un Domain

### Exemple : Domain Tenant

```
src/Domains/Tenant/
├── Entities/              # Objets métier (Tenant)
│   └── Tenant.php
├── ValueObjects/          # Valeurs métier (TenantCode, TenantName)
│   ├── TenantCode.php
│   └── TenantName.php
├── Repositories/          # Interfaces (contrats)
│   └── TenantRepository.php
├── Services/              # Logique métier
│   └── TenantService.php
├── Exceptions/            # Exceptions métier
│   ├── TenantNotFoundException.php
│   └── InvalidTenantCodeException.php
├── Events/                # Événements métier
│   └── TenantCreatedEvent.php
└── ReadModels/            # Représentations pour lectures (optionnel)
    └── TenantReadModel.php
```

### Implémentation Infrastructure (Laravel)

```
app/Models/Tenant.php                        # Model Eloquent
app/Repositories/EloquentTenantRepository.php # Implémentation du Repository
app/Http/Controllers/TenantController.php     # Points d'entrée HTTP
app/Http/Requests/StoreTenantRequest.php      # Validation
database/migrations/create_tenants_table.php  # Schéma DB
```

---

## Patterns détaillés

### 1. Entity (Entité)

L'Entity est un objet métier avec une identité unique.

```php
<?php
namespace Domains\Tenant\Entities;

/**
 * Entity Tenant
 *
 * Représente un commerçant/boutique dans le système.
 * Chaque tenant a ses propres données isolées (multi-tenancy).
 */
class Tenant
{
    private int $id;
    private string $code;        // Code unique du tenant
    private string $name;        // Nom commercial
    private string $email;
    private bool $is_active;

    public function __construct(
        int $id,
        string $code,
        string $name,
        string $email,
        bool $is_active = true
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->email = $email;
        $this->is_active = $is_active;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    // Logique métier
    public function activate(): void
    {
        if ($this->is_active) {
            throw new \Exception('Tenant already active');
        }
        $this->is_active = true;
    }

    public function deactivate(): void
    {
        if (!$this->is_active) {
            throw new \Exception('Tenant already inactive');
        }
        $this->is_active = false;
    }
}
```

### 2. Value Object (Objet de Valeur)

Objets simples, immutables, sans identité propre.

```php
<?php
namespace Domains\Tenant\ValueObjects;

/**
 * Value Object TenantCode
 *
 * Représente le code unique d'un tenant.
 * Immutable et auto-validé.
 */
final class TenantCode
{
    private string $value;

    public function __construct(string $value)
    {
        // Validation métier : format du code
        if (strlen($value) < 3 || strlen($value) > 10) {
            throw new \InvalidArgumentException('Code must be 3-10 characters');
        }

        if (!preg_match('/^[A-Z0-9_]+$/', $value)) {
            throw new \InvalidArgumentException('Code must be uppercase alphanumeric');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(TenantCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### 3. Repository (Interface)

Contrat pour accéder aux données - abstrait la persistance.

```php
<?php
namespace Domains\Tenant\Repositories;

use Domains\Tenant\Entities\Tenant;

/**
 * Repository Interface pour Tenant
 *
 * Définit le contrat d'accès aux données.
 * L'implémentation utilise Eloquent (dans app/Repositories).
 */
interface TenantRepository
{
    /**
     * Trouver un tenant par ID
     */
    public function findById(int $id): ?Tenant;

    /**
     * Trouver un tenant par code
     */
    public function findByCode(string $code): ?Tenant;

    /**
     * Obtenir tous les tenants
     */
    public function getAll(): array;

    /**
     * Sauvegarder un tenant (créer ou modifier)
     */
    public function save(Tenant $tenant): Tenant;

    /**
     * Supprimer un tenant
     */
    public function delete(int $id): bool;
}
```

### 4. Service (Logique métier)

Orchestration des opérations métier complexes.

```php
<?php
namespace Domains\Tenant\Services;

use Domains\Tenant\Entities\Tenant;
use Domains\Tenant\Repositories\TenantRepository;
use Domains\Tenant\ValueObjects\TenantCode;

/**
 * Tenant Service
 *
 * Encapsule la logique métier du domaine Tenant.
 * Orchestre les opérations avec les entities et repositories.
 */
class TenantService
{
    public function __construct(
        private TenantRepository $repository
    ) {}

    /**
     * Créer un nouveau tenant
     *
     * @throws InvalidTenantCodeException
     */
    public function createTenant(
        string $code,
        string $name,
        string $email
    ): Tenant {
        // Valider le code
        $tenantCode = new TenantCode($code);

        // Vérifier l'unicité
        if ($this->repository->findByCode($tenantCode->getValue())) {
            throw new \Exception("Code '{$code}' already exists");
        }

        // Créer l'entity
        $tenant = new Tenant(
            id: null,
            code: $tenantCode->getValue(),
            name: $name,
            email: $email
        );

        // Persister et retourner
        return $this->repository->save($tenant);
    }

    /**
     * Activer un tenant
     */
    public function activateTenant(int $tenantId): Tenant
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw new \Exception("Tenant {$tenantId} not found");
        }

        $tenant->activate();
        return $this->repository->save($tenant);
    }
}
```

### 5. Use Case (Orchestration)

Point d'entrée applicatif - orchestre entre contrôleurs et domaine.

```php
<?php
namespace Domains\Tenant\UseCases;

use Domains\Tenant\Services\TenantService;
use Domains\Tenant\Repositories\TenantRepository;

/**
 * Use Case: Créer un Tenant
 *
 * Cas d'utilisation applicatif qui orchestre
 * la création d'un nouveau tenant.
 */
class CreateTenantUseCase
{
    public function __construct(
        private TenantService $tenantService,
        private TenantRepository $repository
    ) {}

    /**
     * Exécute le use case
     */
    public function execute(CreateTenantRequest $request): CreateTenantResponse
    {
        try {
            // Appeler le service métier
            $tenant = $this->tenantService->createTenant(
                code: $request->getCode(),
                name: $request->getName(),
                email: $request->getEmail()
            );

            // Retourner le résultat
            return new CreateTenantResponse(
                success: true,
                tenant: $tenant,
                message: 'Tenant created successfully'
            );
        } catch (\Exception $e) {
            return new CreateTenantResponse(
                success: false,
                message: $e->getMessage()
            );
        }
    }
}
```

---

## Dépendances entre couches

```
DOMAIN (Entités, Services, Repositories interfaces)
    ↑
APPLICATION (Use Cases, orchestration)
    ↑
INFRASTRUCTURE (Models, Repository implémentations)
    ↑
CONTROLLERS (Points d'entrée HTTP)
    ↑
REACT COMPONENTS (Interface utilisateur)
```

**Règles importantes :**

- ✅ Domain peut utiliser Application et Infrastructure (injection)
- ❌ Infrastructure NE DOIT PAS dépendre de Domain (il l'implémente)
- ✅ Controllers utilisent Use Cases
- ✅ React appelle Controllers via API

---

## Gestion des erreurs

Chaque domain doit avoir ses propres exceptions :

```php
<?php
namespace Domains\Tenant\Exceptions;

/**
 * Exception TenantNotFoundException
 *
 * Levée quand un tenant n'existe pas.
 */
class TenantNotFoundException extends \Exception
{
    public static function withId(int $id): self
    {
        return new self("Tenant with ID {$id} not found");
    }

    public static function withCode(string $code): self
    {
        return new self("Tenant with code '{$code}' not found");
    }
}
```

---

## Événements métier

Pour découpler les domaines, utiliser les événements :

```php
<?php
namespace Domains\Tenant\Events;

/**
 * Event: TenantCreated
 *
 * Déclenché quand un tenant est créé.
 * Les autres domaines peuvent s'abonner à cet événement.
 */
class TenantCreatedEvent
{
    public function __construct(
        private int $tenantId,
        private string $tenantCode
    ) {}

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getTenantCode(): string
    {
        return $this->tenantCode;
    }
}
```

---

## Testing

Chaque couche se teste indépendamment :

```php
// Test du domain (sans Laravel)
public function testTenantCodeValidation()
{
    $this->expectException(\InvalidArgumentException::class);
    new TenantCode('ab');  // Trop court
}

// Test du service
public function testCreateTenantWithDuplicateCode()
{
    // Mock du repository
    // Vérifier que l'exception est levée
}

// Test du controller
public function testCreateTenantEndpoint()
{
    $response = $this->post('/api/tenants', [
        'code' => 'SHOP1',
        'name' => 'Ma boutique'
    ]);

    $response->assertStatus(201);
}
```

---

## Ressources

- [Eric Evans - Domain Driven Design](https://www.domainlanguage.com/ddd/)
- [Vaughn Vernon - Implementing DDD](https://vaughnvernon.com/implementing-ddd/)
- [Martin Fowler - Domain Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
