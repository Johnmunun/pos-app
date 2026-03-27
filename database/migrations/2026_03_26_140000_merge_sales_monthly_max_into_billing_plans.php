<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute les cles manquantes du catalogue billing (ex. sales.monthly.max) aux plans existants.
     */
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('billing_plans')) {
            return;
        }

        $catalog = (array) config('billing_features.catalog', []);
        $templates = (array) config('billing_features.plan_templates', []);

        $plans = DB::table('billing_plans')->get();
        foreach ($plans as $plan) {
            $features = [];
            if (is_string($plan->features)) {
                $features = json_decode($plan->features, true) ?: [];
            }
            $template = is_string($plan->code) ? ($templates[$plan->code] ?? []) : [];

            foreach ($catalog as $code => $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                if (!isset($features[$code])) {
                    if (isset($template[$code]) && is_array($template[$code])) {
                        $features[$code] = $template[$code];
                    } else {
                        $features[$code] = [
                            'label' => $meta['label'] ?? $code,
                            'enabled' => (bool) ($meta['default_enabled'] ?? false),
                            'limit' => $meta['default_limit'] ?? null,
                        ];
                    }
                }
            }

            DB::table('billing_plans')
                ->where('id', $plan->id)
                ->update([
                    'features' => json_encode($features, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Pas de rollback fiable des fusions JSON.
    }
};
