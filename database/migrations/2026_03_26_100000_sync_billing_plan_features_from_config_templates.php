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
            if (!is_array($features) || $features === []) {
                continue;
            }
            DB::table('billing_plans')->where('code', $code)->update([
                'features' => json_encode($features, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }

        $descriptions = [
            'starter' => 'Caisse, stock et catalogue vitrine. Idéal pour démarrer — la vente en ligne et les outils avancés sont sur le plan Pro.',
            'pro' => 'Commandes web, promotions, analytics, API, assistant IA et multi-dépôts pour faire grandir votre activité.',
            'enterprise' => 'Tout illimité, support prioritaire et intégrations pour les réseaux et grandes équipes.',
        ];
        foreach ($descriptions as $code => $description) {
            DB::table('billing_plans')->where('code', $code)->update([
                'description' => $description,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Données métier : pas de retour automatique aux anciens JSON.
    }
};
