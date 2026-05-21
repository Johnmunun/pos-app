<?php

namespace App\Support;

/**
 * Plafonds catalogue pour la boutique pré-configurée (référence plan Starter).
 */
final class PreconfiguredStoreSeedLimits
{
    private const PLAN_CODE = 'starter';

    public static function maxProducts(): ?int
    {
        return self::featureLimit('products.max');
    }

    public static function maxCategories(): ?int
    {
        return self::featureLimit('categories.max');
    }

    private static function featureLimit(string $featureCode): ?int
    {
        $templates = (array) config('billing_features.plan_templates', []);
        $plan = $templates[self::PLAN_CODE] ?? [];
        if (! is_array($plan)) {
            return null;
        }

        $feature = $plan[$featureCode] ?? null;
        if (! is_array($feature) || ! ($feature['enabled'] ?? true)) {
            return null;
        }

        if (! array_key_exists('limit', $feature) || $feature['limit'] === null) {
            return null;
        }

        return max(0, (int) $feature['limit']);
    }
}
