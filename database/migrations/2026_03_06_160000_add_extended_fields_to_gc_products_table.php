<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            $after = 'description';
            if (!Schema::hasColumn('gc_products', 'product_type')) {
                $table->string('product_type', 32)->nullable()->after($after);
            }
            if (!Schema::hasColumn('gc_products', 'unit')) {
                $table->string('unit', 50)->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('gc_products', 'weight')) {
                $table->decimal('weight', 12, 4)->nullable()->after('unit');
            }
            if (!Schema::hasColumn('gc_products', 'length')) {
                $table->decimal('length', 12, 4)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('gc_products', 'width')) {
                $table->decimal('width', 12, 4)->nullable()->after('length');
            }
            if (!Schema::hasColumn('gc_products', 'height')) {
                $table->decimal('height', 12, 4)->nullable()->after('width');
            }
            if (!Schema::hasColumn('gc_products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('height');
            }
            if (!Schema::hasColumn('gc_products', 'tax_type')) {
                $table->string('tax_type', 20)->nullable()->after('tax_rate');
            }
            if (!Schema::hasColumn('gc_products', 'status')) {
                $table->string('status', 20)->nullable()->default('active')->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            foreach (['status', 'tax_type', 'tax_rate', 'height', 'width', 'length', 'weight', 'unit', 'product_type'] as $col) {
                if (Schema::hasColumn('gc_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
