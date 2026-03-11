<?php

namespace Src\Application\Referral\Services;

use Illuminate\Support\Str;
use Src\Infrastructure\Referral\Models\ReferralAccountModel;
use Src\Infrastructure\Referral\Models\ReferralCommissionModel;
use Src\Infrastructure\Referral\Models\ReferralSettingModel;

/**
 * Service centralisé de gestion du parrainage (Referral).
 *
 * - Génère / récupère les comptes referral pour les utilisateurs
 * - Applique la configuration (commission, niveaux) par tenant
 * - Enregistre les commissions lors des transactions multi-modules
 */
class ReferralService
{
    public function getOrCreateAccount(int $tenantId, int $userId): ReferralAccountModel
    {
        $existing = ReferralAccountModel::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $code = $this->generateUniqueCode($tenantId);

        return ReferralAccountModel::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'code' => $code,
            'currency' => null,
        ]);
    }

    public function findByCode(string $code): ?ReferralAccountModel
    {
        return ReferralAccountModel::where('code', $code)->first();
    }

    /**
     * Enregistrer une transaction donnant lieu à des commissions de parrainage.
     *
     * @param int         $tenantId     Tenant / boutique
     * @param int|null    $buyerUserId  Utilisateur acheteur (peut être null si visite anonyme)
     * @param float       $grossAmount  Montant de la transaction (devise de référence du tenant)
     * @param string      $sourceType   Ex: pharmacy_sale, hardware_sale, commerce_sale, ecommerce_order
     * @param string      $sourceId     Identifiant de la vente / commande
     */
    public function recordTransaction(
        int $tenantId,
        ?int $buyerUserId,
        float $grossAmount,
        string $sourceType,
        string $sourceId
    ): void {
        if ($grossAmount <= 0 || !$buyerUserId) {
            return;
        }

        $settings = ReferralSettingModel::find($tenantId);
        if (!$settings || !$settings->enabled) {
            return;
        }

        // Filtrer par module si une liste est définie dans la configuration.
        // Mapping source_type -> clé de module (cf. Referral/Settings.jsx).
        $moduleKey = match ($sourceType) {
            'pharmacy_sale' => 'pharmacy',
            'hardware_sale' => 'hardware',
            'commerce_sale' => 'commerce',
            'ecommerce_order' => 'ecommerce',
            default => null,
        };

        $enabledModules = $settings->enabled_modules ?? [];
        if (is_array($enabledModules) && $enabledModules !== [] && $moduleKey !== null) {
            if (!in_array($moduleKey, $enabledModules, true)) {
                // Module désactivé pour le referral → ne rien enregistrer.
                return;
            }
        }

        // Trouver le compte referral de l'acheteur, pour remonter la chaîne
        $buyerAccount = ReferralAccountModel::where('tenant_id', $tenantId)
            ->where('user_id', $buyerUserId)
            ->first();

        if (!$buyerAccount || !$buyerAccount->parent_id) {
            // Aucun parrain direct, donc pas de commission à calculer
            return;
        }

        $currency = $this->resolveCurrencyForTenant($tenantId);

        $currentAccountId = $buyerAccount->parent_id;
        $level = 1;

        while ($currentAccountId && $level <= $settings->max_levels) {
            /** @var ReferralAccountModel|null $referrer */
            $referrer = ReferralAccountModel::find($currentAccountId);
            if (!$referrer) {
                break;
            }

            $commissionAmount = $this->calculateCommission(
                $settings->commission_type,
                (float) $settings->commission_value,
                $grossAmount
            );

            if ($commissionAmount > 0) {
                ReferralCommissionModel::create([
                    'tenant_id' => $tenantId,
                    'referrer_account_id' => $referrer->id,
                    'referred_user_id' => $buyerUserId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'level' => $level,
                    'amount' => $commissionAmount,
                    'currency' => $currency,
                    'status' => 'pending',
                ]);

                // Mettre à jour les totaux agrégés du parrain
                $referrer->increment('total_commissions_amount', $commissionAmount);
                $referrer->increment('total_referred_revenue', $grossAmount);
                $referrer->increment('total_referrals');
            }

            $currentAccountId = $referrer->parent_id;
            $level++;
        }
    }

    private function calculateCommission(string $type, float $value, float $baseAmount): float
    {
        if ($value <= 0 || $baseAmount <= 0) {
            return 0.0;
        }

        if ($type === 'fixed') {
            return $value;
        }

        // percentage
        return round($baseAmount * ($value / 100), 2);
    }

    private function generateUniqueCode(int $tenantId): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (
            ReferralAccountModel::where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }

    private function resolveCurrencyForTenant(int $tenantId): string
    {
        // Par défaut, on laisse "USD" si aucune devise n'est trouvée.
        try {
            $currency = \App\Models\Currency::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->first();

            if ($currency) {
                return strtoupper((string) $currency->code);
            }
        } catch (\Throwable) {
        }

        return 'USD';
    }
}

