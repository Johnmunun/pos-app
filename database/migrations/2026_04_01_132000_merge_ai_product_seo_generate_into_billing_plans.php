<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('billing_plans')) {
            return;
        }

        $templates = config('billing_features.plan_templates', []);
        foreach ($templates as $code => $features) {
            if (!is_array($features) || !isset($features['ai.product.seo.generate'])) {
                continue;
            }
            $row = DB::table('billing_plans')->where('code', $code)->first();
            if ($row === null || !is_string($row->features)) {
                continue;
            }
            $decoded = json_decode($row->features, true) ?: [];
            $decoded['ai.product.seo.generate'] = $features['ai.product.seo.generate'];
            DB::table('billing_plans')->where('code', $code)->update([
                'features' => json_encode($decoded, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Données métier : conserver les clés en base.
    }
};

