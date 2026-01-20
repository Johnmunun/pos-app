<?php

namespace Domains\Tenant\UseCases;

use Domains\Tenant\Services\TenantService;
use Domains\Tenant\Exceptions\InvalidTenantStateException;

/**
 * Use Case: Activer un Tenant
 *
 * Cas d'usage qui orchestre l'activation d'un tenant.
 */
class ActivateTenantUseCase
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
     * @param int $tenantId L'ID du tenant à activer
     * @return ActivateTenantResponse
     */
    public function execute(int $tenantId): ActivateTenantResponse
    {
        try {
            $tenant = $this->tenantService->activateTenant($tenantId);

            return ActivateTenantResponse::success(
                tenantId: $tenant->getId(),
                message: 'Tenant activated successfully'
            );
        } catch (InvalidTenantStateException $e) {
            return ActivateTenantResponse::error(
                message: $e->getMessage(),
                errorCode: 'INVALID_STATE'
            );
        } catch (\Exception $e) {
            return ActivateTenantResponse::error(
                message: 'An unexpected error occurred: ' . $e->getMessage(),
                errorCode: 'INTERNAL_ERROR'
            );
        }
    }
}

/**
 * DTO Response: ActivateTenantResponse
 */
class ActivateTenantResponse
{
    private bool $success;
    private ?int $tenantId;
    private string $message;
    private ?string $errorCode;

    private function __construct() {}

    public static function success(int $tenantId, string $message = ''): self
    {
        $response = new self();
        $response->success = true;
        $response->tenantId = $tenantId;
        $response->message = $message;
        $response->errorCode = null;

        return $response;
    }

    public static function error(string $message, string $errorCode = 'ERROR'): self
    {
        $response = new self();
        $response->success = false;
        $response->tenantId = null;
        $response->message = $message;
        $response->errorCode = $errorCode;

        return $response;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'data' => $this->success ? [
                'id' => $this->tenantId,
            ] : null,
        ];
    }
}
