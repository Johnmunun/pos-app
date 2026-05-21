<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_shop_reports')) {
            Schema::create('ecommerce_shop_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('reason', 40);
                $table->text('details')->nullable();
                $table->string('reporter_name', 120)->nullable();
                $table->string('reporter_email', 190)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();

                $table->index(['shop_id', 'status', 'created_at']);
            });
        }

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                if (!Schema::hasColumn('shops', 'ecommerce_report_suspended_at')) {
                    $table->timestamp('ecommerce_report_suspended_at')->nullable()->after('ecommerce_is_online');
                }
                if (!Schema::hasColumn('shops', 'ecommerce_report_suspend_reason')) {
                    $table->text('ecommerce_report_suspend_reason')->nullable()->after('ecommerce_report_suspended_at');
                }
                if (!Schema::hasColumn('shops', 'ecommerce_report_suspended_by_user_id')) {
                    $table->unsignedBigInteger('ecommerce_report_suspended_by_user_id')->nullable()->after('ecommerce_report_suspend_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                foreach ([
                    'ecommerce_report_suspended_by_user_id',
                    'ecommerce_report_suspend_reason',
                    'ecommerce_report_suspended_at',
                ] as $col) {
                    if (Schema::hasColumn('shops', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('ecommerce_shop_reports');
    }
};
