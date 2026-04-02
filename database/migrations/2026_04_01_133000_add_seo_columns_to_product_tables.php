<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gc_products')) {
            Schema::table('gc_products', function (Blueprint $table): void {
                if (!Schema::hasColumn('gc_products', 'meta_title')) {
                    $table->string('meta_title', 60)->nullable()->after('description');
                }
                if (!Schema::hasColumn('gc_products', 'meta_description')) {
                    $table->string('meta_description', 160)->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('gc_products', 'slug')) {
                    $table->string('slug', 180)->nullable()->after('meta_description');
                }
            });
        }

        if (Schema::hasTable('quincaillerie_products')) {
            Schema::table('quincaillerie_products', function (Blueprint $table): void {
                if (!Schema::hasColumn('quincaillerie_products', 'meta_title')) {
                    $table->string('meta_title', 60)->nullable()->after('description');
                }
                if (!Schema::hasColumn('quincaillerie_products', 'meta_description')) {
                    $table->string('meta_description', 160)->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('quincaillerie_products', 'slug')) {
                    $table->string('slug', 180)->nullable()->after('meta_description');
                }
            });
        }

        if (Schema::hasTable('pharmacy_products')) {
            Schema::table('pharmacy_products', function (Blueprint $table): void {
                if (!Schema::hasColumn('pharmacy_products', 'meta_title')) {
                    $table->string('meta_title', 60)->nullable()->after('description');
                }
                if (!Schema::hasColumn('pharmacy_products', 'meta_description')) {
                    $table->string('meta_description', 160)->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('pharmacy_products', 'slug')) {
                    $table->string('slug', 180)->nullable()->after('meta_description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gc_products')) {
            Schema::table('gc_products', function (Blueprint $table): void {
                if (Schema::hasColumn('gc_products', 'slug')) {
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('gc_products', 'meta_description')) {
                    $table->dropColumn('meta_description');
                }
                if (Schema::hasColumn('gc_products', 'meta_title')) {
                    $table->dropColumn('meta_title');
                }
            });
        }

        if (Schema::hasTable('quincaillerie_products')) {
            Schema::table('quincaillerie_products', function (Blueprint $table): void {
                if (Schema::hasColumn('quincaillerie_products', 'slug')) {
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('quincaillerie_products', 'meta_description')) {
                    $table->dropColumn('meta_description');
                }
                if (Schema::hasColumn('quincaillerie_products', 'meta_title')) {
                    $table->dropColumn('meta_title');
                }
            });
        }

        if (Schema::hasTable('pharmacy_products')) {
            Schema::table('pharmacy_products', function (Blueprint $table): void {
                if (Schema::hasColumn('pharmacy_products', 'slug')) {
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('pharmacy_products', 'meta_description')) {
                    $table->dropColumn('meta_description');
                }
                if (Schema::hasColumn('pharmacy_products', 'meta_title')) {
                    $table->dropColumn('meta_title');
                }
            });
        }
    }
};

