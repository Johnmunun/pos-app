<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('category', 64)->index();
            $table->string('description');
            $table->string('supplier_id', 36)->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['shop_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};
