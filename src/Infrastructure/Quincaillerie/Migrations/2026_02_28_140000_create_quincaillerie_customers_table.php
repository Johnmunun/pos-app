<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quincaillerie_customers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->enum('customer_type', ['individual', 'company'])->default('individual');
            $table->string('tax_number')->nullable();
            $table->decimal('credit_limit', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quincaillerie_customers');
    }
};
