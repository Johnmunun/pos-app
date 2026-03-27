<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('annual_price', 12, 2)->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('tenant_plan_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('billing_plan_id')->constrained('billing_plans')->cascadeOnDelete();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tenant_feature_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('feature_code', 120);
            $table->boolean('is_enabled')->nullable();
            $table->integer('limit_value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'feature_code']);
        });

        $templates = config('billing_features.plan_templates', []);
        $starterDesc = 'Caisse, stock et catalogue vitrine. Idéal pour démarrer — la vente en ligne et les outils avancés sont sur le plan Pro.';
        $proDesc = 'Commandes web, promotions, analytics, API, assistant IA et multi-dépôts pour faire grandir votre activité.';
        $enterpriseDesc = 'Tout illimité, support prioritaire et intégrations pour les réseaux et grandes équipes.';

        DB::table('billing_plans')->insert([
            [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => $starterDesc,
                'monthly_price' => 0,
                'annual_price' => 0,
                'features' => json_encode($templates['starter'] ?? [], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'description' => $proDesc,
                'monthly_price' => 0,
                'annual_price' => 0,
                'features' => json_encode($templates['pro'] ?? [], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => $enterpriseDesc,
                'monthly_price' => 0,
                'annual_price' => 0,
                'features' => json_encode($templates['enterprise'] ?? [], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $existingPermission = DB::table('permissions')->where('code', 'admin.billing.manage')->exists();
        if (!$existingPermission) {
            DB::table('permissions')->insert([
                'code' => 'admin.billing.manage',
                'description' => 'Gerer les plans et limitations SaaS',
                'group' => 'admin',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_overrides');
        Schema::dropIfExists('tenant_plan_subscriptions');
        Schema::dropIfExists('billing_plans');
    }
};
