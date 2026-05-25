<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute les clés fidélité au JSON features des plans existants (sans écraser la config admin).
 */
return new class extends Migration
{
    private const MERGE_CODES = [
        'loyalty.enabled',
        'loyalty.accounts.max',
    ];

    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('billing_plans')) {
            return;
        }

        $templates = config('billing_features.plan_templates', []);

        $plans = DB::table('billing_plans')->orderBy('id')->get();
        foreach ($plans as $plan) {
            $code = (string) ($plan->code ?? '');
            $template = is_array($templates[$code] ?? null) ? $templates[$code] : [];

            $features = [];
            if (is_string($plan->features) && $plan->features !== '') {
                $decoded = json_decode($plan->features, true);
                $features = is_array($decoded) ? $decoded : [];
            }

            $changed = false;

            foreach (self::MERGE_CODES as $featureCode) {
                if (isset($features[$featureCode]) || !isset($template[$featureCode])) {
                    continue;
                }
                $features[$featureCode] = $template[$featureCode];
                $changed = true;
            }

            if ($changed) {
                DB::table('billing_plans')->where('id', $plan->id)->update([
                    'features' => json_encode($features, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Données métier : pas de retour automatique.
    }
};
