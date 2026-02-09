<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->string('image_path', 500)->nullable()->after('description');
            $table->string('image_type', 20)->default('upload')->after('image_path')->comment('upload or url');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_type']);
        });
    }
};
