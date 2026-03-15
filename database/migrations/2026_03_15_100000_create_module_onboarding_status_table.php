<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('module_onboarding_status')) {
            return;
        }

        Schema::create('module_onboarding_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('module_name', 64)->index();
            $table->json('steps_completed')->nullable(); // ["step1", "step2", ...]
            $table->unsignedTinyInteger('status')->default(0); // 0 = en cours, 1 = terminé
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'module_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_onboarding_status');
    }
};
