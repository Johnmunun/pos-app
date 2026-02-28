<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quincaillerie_stock_movements', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('product_id')->index();
            $table->string('type', 16); // IN, OUT, ADJUSTMENT
            $table->decimal('quantity', 10, 3);
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'product_id']);
            $table->index(['shop_id', 'type']);
            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quincaillerie_stock_movements');
    }
};
