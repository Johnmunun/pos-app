<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('debt_id')->index();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('payment_method', 64)->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('recorded_by')->index();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->foreign('debt_id')->references('id')->on('finance_debts')->onDelete('cascade');
            $table->index(['debt_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_settlements');
    }
};
