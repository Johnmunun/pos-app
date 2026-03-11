<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('logged_at')->index();
            $table->string('level')->index(); // info, warning, error, critical
            $table->string('module')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->text('message');
            $table->text('context')->nullable();
            $table->timestamps();
        });

        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action');
            $table->string('module')->nullable()->index();
            $table->string('route')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        Schema::create('user_login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('logged_in_at')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('status', 32)->default('success'); // success, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_histories');
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('system_logs');
    }
};

