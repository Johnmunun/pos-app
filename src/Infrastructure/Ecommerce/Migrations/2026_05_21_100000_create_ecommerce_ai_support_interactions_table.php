<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_ai_support_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('topic', 40)->nullable();
            $table->string('user_message', 500)->nullable();
            $table->string('assistant_excerpt', 500)->nullable();
            $table->unsignedSmallInteger('products_shown')->default(0);
            $table->enum('feedback', ['helpful', 'not_helpful'])->nullable();
            $table->timestamp('feedback_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_ai_support_interactions');
    }
};
