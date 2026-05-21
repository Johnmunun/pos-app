<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Détermine si le tenant est sur un plan d'entrée (trial / starter) pour proposer l'upgrade à la connexion.
 */
final class TenantBillingUpgradePrompt
{
    private const ENTRY_PLAN_CODES = ['trial', 'starter'];

    /** Mots-clés dans le nom du plan si la colonne code est absente ou inconnue. */
    private const NAME_HINTS = ['trial', 'starter', 'essai', 'découverte', 'decouverte', 'gratuit', 'free'];

    public static function tenantHasEntryPlan(?object $user): bool
    {
        if ($user === null || empty($user->tenant_id)) {
            return false;
        }

        if (! Schema::hasTable('tenant_plan_subscriptions') || ! Schema::hasTable('billing_plans')) {
            return false;
        }

        $tenantId = (string) $user->tenant_id;

        $row = DB::table('tenant_plan_subscriptions as tps')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.tenant_id', $tenantId)
            ->where('tps.status', 'active')
            ->orderByDesc('tps.id')
            ->select(['bp.code', 'bp.name'])
            ->first();

        if ($row === null) {
            return false;
        }

        $code = strtolower(trim((string) ($row->code ?? '')));
        if ($code !== '' && in_array($code, self::ENTRY_PLAN_CODES, true)) {
            return true;
        }

        $name = strtolower((string) ($row->name ?? ''));
        foreach (self::NAME_HINTS as $hint) {
            if ($hint !== '' && str_contains($name, $hint)) {
                return true;
            }
        }

        return false;
    }
}
