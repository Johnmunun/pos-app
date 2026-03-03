<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropUnique('finance_invoices_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->unique('number', 'finance_invoices_number_unique');
        });
    }
};
