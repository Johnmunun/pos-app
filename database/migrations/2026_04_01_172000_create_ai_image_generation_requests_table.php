<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_image_generation_requests')) {
            return;
        }

        Schema::create('ai_image_generation_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 64)->index();
            $table->string('feature_code', 120)->index();
            $table->string('context', 40)->default('product')->index(); // product|media
            $table->string('status', 30)->default('pending')->index(); // pending|processing|completed|failed
            $table->unsignedTinyInteger('count')->default(1);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('result_images')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_image_generation_requests');
    }
};
