<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_movements', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->string('product_id')->index();
            $table->string('type', 16); // IN, OUT, ADJUSTMENT
            $table->integer('quantity');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movements');
    }
};

