<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('store_settings', 'receipt_auto_print')) {
                $table->boolean('receipt_auto_print')->default(false)->after('invoice_footer_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('store_settings', 'receipt_auto_print')) {
                $table->dropColumn('receipt_auto_print');
            }
        });
    }
};

