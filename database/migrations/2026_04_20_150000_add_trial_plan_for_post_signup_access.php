<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        $features = (array) config('billing_features.plan_templates.trial', []);
        $payload = [
            'code' => 'trial',
            'name' => 'Trial',
            'description' => 'Essai gratuit avec limites d usage.',
            'monthly_price' => 0,
            'annual_price' => 0,
            'features' => json_encode($features),
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        $existing = DB::table('billing_plans')->where('code', 'trial')->first(['id']);
        if ($existing !== null) {
            DB::table('billing_plans')->where('id', $existing->id)->update([
                'name' => $payload['name'],
                'description' => $payload['description'],
                'monthly_price' => $payload['monthly_price'],
                'annual_price' => $payload['annual_price'],
                'features' => $payload['features'],
                'is_active' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('billing_plans')->insert($payload);
    }

    public function down(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        DB::table('billing_plans')->where('code', 'trial')->delete();
    }
};
