<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable()->index();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::table('gc_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('gc_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_categories');
    }
};
