<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache partagé Inertia (plans, quotas, feature flags) — évite ~15 requêtes SQL par navigation.
 */
final class InertiaBillingPayloadCache
{
    public const KEY_PREFIX = 'inertia:billing_payload:tenant:';

    public const TTL_SECONDS = 600;

    public static function key(string $tenantId): string
    {
        return self::KEY_PREFIX.$tenantId;
    }

    public static function forget(string $tenantId): void
    {
        Cache::forget(self::key($tenantId));
    }
}
