<?php

namespace Domains\Tenant\UseCases;

use Domains\Tenant\Services\TenantService;
use Domains\Tenant\Exceptions\DuplicateTenantCodeException;

/**
 * Use Case: Créer un Tenant
 *
 * Point d'entrée applicatif (application layer).
 *
 * Orchestre:
 * 1. Validation des inputs (effectuée par les requests Laravel)
 * 2. Appel au service métier
 * 3. Gestion des erreurs
 * 4. Transformation de la réponse
 *
 * Cette classe est l'interface entre les Controllers et le Domain.
 */
class CreateTenantUseCase
{
    /**
     * @param TenantService $tenantService Service métier
     */
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Exécuter le cas d'usage
     *
     * @param string $code Code unique du tenant
     * @param string $name Nom commercial
     * @param string $email Email de contact
     *
     * @return CreateTenantResponse La réponse du cas d'usage
     */
    public function execute(
        string $code,
        string $name,
        string $email
    ): CreateTenantResponse {
        try {
            // Appeler le service métier
            $tenant = $this->tenantService->createTenant(
                code: $code,
                name: $name,
                email: $email
            );

            // Retourner une réponse succès
            return CreateTenantResponse::success(
                tenantId: $tenant->getId(),
                code: $tenant->getCode()->getValue(),
                name: $tenant->getName()->getValue(),
                email: $tenant->getEmail()->getValue(),
                message: 'Tenant created successfully'
            );
        } catch (DuplicateTenantCodeException $e) {
            // Erreur métier prévisible
            return CreateTenantResponse::error(
                message: $e->getMessage(),
                errorCode: 'DUPLICATE_CODE'
            );
        } catch (\InvalidArgumentException $e) {
            // Erreur de validation
            return CreateTenantResponse::error(
                message: $e->getMessage(),
                errorCode: 'VALIDATION_ERROR'
            );
        } catch (\Exception $e) {
            // Erreur système inattendue
            return CreateTenantResponse::error(
                message: 'An unexpected error occurred: ' . $e->getMessage(),
                errorCode: 'INTERNAL_ERROR'
            );
        }
    }
}

/**
 * DTO Response: CreateTenantResponse
 *
 * Encapsule la réponse du use case.
 * Permet une sérialisation facile en JSON.
 */
class CreateTenantResponse
{
    /**
     * @var bool Succès de l'opération
     */
    private bool $success;

    /**
     * @var int|null ID du tenant créé
     */
    private ?int $tenantId;

    /**
     * @var string|null Code du tenant
     */
    private ?string $code;

    /**
     * @var string|null Nom du tenant
     */
    private ?string $name;

    /**
     * @var string|null Email du tenant
     */
    private ?string $email;

    /**
     * @var string Message (succès ou erreur)
     */
    private string $message;

    /**
     * @var string|null Code d'erreur
     */
    private ?string $errorCode;

    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Factory: Réponse succès
     *
     * @param int $tenantId
     * @param string $code
     * @param string $name
     * @param string $email
     * @param string $message
     * @return self
     */
    public static function success(
        int $tenantId,
        string $code,
        string $name,
        string $email,
        string $message = ''
    ): self {
        $response = new self();
        $response->success = true;
        $response->tenantId = $tenantId;
        $response->code = $code;
        $response->name = $name;
        $response->email = $email;
        $response->message = $message;
        $response->errorCode = null;

        return $response;
    }

    /**
     * Factory: Réponse erreur
     *
     * @param string $message
     * @param string $errorCode
     * @return self
     */
    public static function error(
        string $message,
        string $errorCode = 'ERROR'
    ): self {
        $response = new self();
        $response->success = false;
        $response->tenantId = null;
        $response->code = null;
        $response->name = null;
        $response->email = null;
        $response->message = $message;
        $response->errorCode = $errorCode;

        return $response;
    }

    // Getters pour sérialisation

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Convertir en array pour réponse JSON
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'data' => $this->success ? [
                'id' => $this->tenantId,
                'code' => $this->code,
                'name' => $this->name,
                'email' => $this->email,
            ] : null,
        ];
    }
}
