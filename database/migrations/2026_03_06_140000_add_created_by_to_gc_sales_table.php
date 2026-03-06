<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gc_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('gc_sales', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
