<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_assistant_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->unique();
            $table->boolean('voice_enabled')->default(true);
            $table->string('voice_type', 20)->default('female'); // male | female
            $table->decimal('voice_speed', 3, 2)->default(1.0);   // 0.5 - 2.0
            $table->boolean('auto_play')->default(true);
            $table->string('language', 10)->default('auto');     // fr | en | auto
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_assistant_settings');
    }
};
