<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shops')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'ecommerce_subdomain')) {
                $table->string('ecommerce_subdomain', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('shops', 'ecommerce_is_online')) {
                $table->boolean('ecommerce_is_online')->default(false)->after('ecommerce_subdomain');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'ecommerce_is_online')) {
                $table->dropColumn('ecommerce_is_online');
            }
            if (Schema::hasColumn('shops', 'ecommerce_subdomain')) {
                $table->dropColumn('ecommerce_subdomain');
            }
        });
    }
};

