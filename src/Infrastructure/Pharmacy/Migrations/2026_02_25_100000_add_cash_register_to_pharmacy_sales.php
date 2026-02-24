<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pharmacy_sales') || !Schema::hasTable('cash_registers')) {
            return;
        }

        Schema::table('pharmacy_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_sales', 'cash_register_id')) {
                $table->unsignedBigInteger('cash_register_id')->nullable()->after('shop_id')->index();
            }
            if (!Schema::hasColumn('pharmacy_sales', 'cash_register_session_id')) {
                $table->unsignedBigInteger('cash_register_session_id')->nullable()->after('cash_register_id')->index();
            }
        });

        if (!Schema::hasTable('cash_register_sessions')) {
            return;
        }

        try {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                if (Schema::hasColumn('pharmacy_sales', 'cash_register_id')) {
                    $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('set null');
                }
            });
        } catch (\Throwable $e) {
            // FK peut déjà exister ou driver ne supporte pas
        }

        try {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                if (Schema::hasColumn('pharmacy_sales', 'cash_register_session_id')) {
                    $table->foreign('cash_register_session_id')->references('id')->on('cash_register_sessions')->onDelete('set null');
                }
            });
        } catch (\Throwable $e) {
            // FK peut déjà exister
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pharmacy_sales')) {
            return;
        }
        try {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                $table->dropForeign(['cash_register_id']);
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                $table->dropForeign(['cash_register_session_id']);
            });
        } catch (\Throwable $e) {
        }
        Schema::table('pharmacy_sales', function (Blueprint $table) {
            if (Schema::hasColumn('pharmacy_sales', 'cash_register_id')) {
                $table->dropColumn('cash_register_id');
            }
            if (Schema::hasColumn('pharmacy_sales', 'cash_register_session_id')) {
                $table->dropColumn('cash_register_session_id');
            }
        });
    }
};
