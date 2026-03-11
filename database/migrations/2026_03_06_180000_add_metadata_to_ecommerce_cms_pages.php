<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_cms_pages')) {
            Schema::table('ecommerce_cms_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('ecommerce_cms_pages', 'template')) {
                    $table->string('template', 50)->nullable()->after('slug');
                }
                if (!Schema::hasColumn('ecommerce_cms_pages', 'metadata')) {
                    $table->json('metadata')->nullable()->after('content');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecommerce_cms_pages')) {
            Schema::table('ecommerce_cms_pages', function (Blueprint $table) {
                $table->dropColumn(['template', 'metadata']);
            });
        }
    }
};
