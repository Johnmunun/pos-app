<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_feature_usage_events')) {
            return;
        }

        Schema::create('tenant_feature_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 64)->index();
            $table->string('feature_code', 120)->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'feature_code', 'created_at'], 'tfue_tenant_feature_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_usage_events');
    }
};

