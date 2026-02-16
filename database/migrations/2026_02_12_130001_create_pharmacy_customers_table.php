<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_customers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('customer_type', 20)->default('individual'); // individual, company
            $table->string('tax_number', 100)->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            // Index pour les recherches
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'customer_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_customers');
    }
};
