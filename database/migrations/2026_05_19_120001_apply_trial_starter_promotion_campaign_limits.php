<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Applique les limites campagnes promotions depuis les templates trial/starter si encore absentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('billing_plans')) {
            return;
        }

        $templates = config('billing_features.plan_templates', []);

        foreach (['trial', 'starter'] as $code) {
            $tpl = $templates[$code]['ecommerce.promotions'] ?? null;
            if (!is_array($tpl)) {
                continue;
            }

            $plan = DB::table('billing_plans')->where('code', $code)->first();
            if ($plan === null) {
                continue;
            }

            $features = json_decode((string) $plan->features, true);
            if (!is_array($features)) {
                $features = [];
            }

            $current = $features['ecommerce.promotions'] ?? [];
            if (($current['limit'] ?? null) !== null) {
                continue;
            }

            $features['ecommerce.promotions'] = array_merge($current, [
                'label' => $tpl['label'] ?? ($current['label'] ?? 'E-commerce promotions'),
                'enabled' => $tpl['enabled'] ?? ($current['enabled'] ?? false),
                'limit' => $tpl['limit'] ?? null,
            ]);

            DB::table('billing_plans')->where('id', $plan->id)->update([
                'features' => json_encode($features, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
