<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute les clés promotions au JSON features des plans existants (sans écraser la config admin).
 */
return new class extends Migration
{
    private const MERGE_CODES = [
        'promotions.products.max',
        'promotions.advanced',
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

            if (isset($template['ecommerce.promotions']) && isset($features['ecommerce.promotions'])) {
                $existing = $features['ecommerce.promotions'];
                $tpl = $template['ecommerce.promotions'];
                $existingLimit = $existing['limit'] ?? null;
                $templateLimit = $tpl['limit'] ?? null;
                if ($templateLimit !== null && $existingLimit === null) {
                    $features['ecommerce.promotions']['limit'] = $templateLimit;
                    $features['ecommerce.promotions']['label'] = $tpl['label'] ?? ($existing['label'] ?? 'E-commerce promotions');
                    $changed = true;
                }
            } elseif (isset($template['ecommerce.promotions']) && !isset($features['ecommerce.promotions'])) {
                $features['ecommerce.promotions'] = $template['ecommerce.promotions'];
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
