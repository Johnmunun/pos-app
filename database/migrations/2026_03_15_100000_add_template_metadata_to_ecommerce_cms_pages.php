<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes template et metadata à ecommerce_cms_pages si elles n'existent pas.
 * (La migration add_metadata s'exécute avant create_ecommerce_cms_tables, donc la table
 * peut avoir été créée sans ces colonnes.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return;
        }

        Schema::table('ecommerce_cms_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_cms_pages', 'template')) {
                $table->string('template', 50)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('ecommerce_cms_pages', 'metadata')) {
                $table->json('metadata')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return;
        }

        Schema::table('ecommerce_cms_pages', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('ecommerce_cms_pages', 'template')) {
                $columns[] = 'template';
            }
            if (Schema::hasColumn('ecommerce_cms_pages', 'metadata')) {
                $columns[] = 'metadata';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
