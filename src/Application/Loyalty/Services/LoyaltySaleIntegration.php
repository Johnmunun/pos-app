<?php

namespace Src\Application\Loyalty\Services;

use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée unique pour la fidélité lors des ventes (tous modules).
 */
class LoyaltySaleIntegration
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {
    }

    public function settingsForPos(int $tenantId): array
    {
        if (!$this->loyaltyService->isEnabledForTenant($tenantId)) {
            return ['enabled' => false];
        }

        return array_merge(
            $this->loyaltyService->getSettings($tenantId),
            ['enabled' => true]
        );
    }

    public function accountForCustomer(int $tenantId, string $module, string $customerId, ?string $customerName = null): ?array
    {
        if (!$this->loyaltyService->isEnabledForTenant($tenantId)) {
            return null;
        }

        $account = $this->loyaltyService->resolveAccount($tenantId, $module, $customerId, true);
        if ($account === null) {
            return null;
        }

        return $this->loyaltyService->formatAccountPayload($account, $customerName);
    }

    public function previewRedemption(int $tenantId, string $module, ?string $customerId, float $lineSubtotal, int $loyaltyPointsRedeem): array
    {
        if ($customerId === null || $customerId === '' || $loyaltyPointsRedeem <= 0 || !$this->loyaltyService->isEnabledForTenant($tenantId)) {
            return ['discount_amount' => 0, 'points_redeemed' => 0];
        }

        return $this->loyaltyService->previewRedemption(
            $tenantId,
            $module,
            $customerId,
            $loyaltyPointsRedeem,
            $lineSubtotal
        );
    }

    public function previewCommerceRedemption(int $tenantId, ?string $customerId, float $lineSubtotal, int $loyaltyPointsRedeem): array
    {
        return $this->previewRedemption($tenantId, LoyaltyService::MODULE_COMMERCE, $customerId, $lineSubtotal, $loyaltyPointsRedeem);
    }

    public function recordCommerceSale(
        int $tenantId,
        string $saleId,
        string $customerId,
        float $lineSubtotal,
        float $loyaltyDiscountAmount,
        int $pointsRedeemed
    ): ?array {
        if (!$this->loyaltyService->isEnabledForTenant($tenantId)) {
            return null;
        }

        $discount = round(max(0, (float) $loyaltyDiscountAmount), 2);
        $eligible = round(max(0, $lineSubtotal - $discount), 2);

        return $this->loyaltyService->processCompletedSale(
            $tenantId,
            LoyaltyService::MODULE_COMMERCE,
            $saleId,
            $customerId,
            $lineSubtotal,
            $pointsRedeemed,
            $discount > 0 ? $discount : null,
            $eligible
        );
    }

    public function afterPharmacyOrHardwareSale(
        int $tenantId,
        string $module,
        string $saleId,
        ?string $customerId,
        float $saleSubtotal,
        int $loyaltyPointsRedeem = 0,
        float $loyaltyDiscountAmount = 0.0
    ): ?array {
        if ($customerId === null || $customerId === '' || !$this->loyaltyService->isEnabledForTenant($tenantId)) {
            return null;
        }

        try {
            $points = $loyaltyPointsRedeem;
            $discount = $loyaltyDiscountAmount;
            if ($points > 0 && $discount <= 0) {
                $preview = $this->loyaltyService->previewRedemption($tenantId, $module, $customerId, $points, $saleSubtotal);
                $discount = (float) $preview['discount_amount'];
                $points = (int) $preview['points_redeemed'];
            }
            $eligible = round(max(0, $saleSubtotal - $discount), 2);

            return $this->loyaltyService->processCompletedSale(
                $tenantId,
                $module,
                $saleId,
                $customerId,
                $saleSubtotal,
                $points,
                $discount,
                $eligible
            );
        } catch (\Throwable $e) {
            Log::warning('Loyalty POS sale failed', ['module' => $module, 'sale_id' => $saleId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function reverseSale(int $tenantId, string $module, string $saleId): void
    {
        try {
            $this->loyaltyService->reverseSale($tenantId, $module, $saleId);
        } catch (\Throwable $e) {
            Log::warning('Loyalty reversal failed', ['module' => $module, 'sale_id' => $saleId, 'error' => $e->getMessage()]);
        }
    }
}
