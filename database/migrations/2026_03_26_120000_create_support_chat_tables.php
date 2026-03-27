<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_chat_conversations')) {
            Schema::create('support_chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index(); // customer
                $table->unsignedBigInteger('assigned_to_user_id')->nullable()->index(); // support agent
                $table->string('status', 32)->default('open')->index(); // open, closed
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('support_chat_messages')) {
            Schema::create('support_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('sender_user_id')->nullable()->index();
                $table->text('message');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('support_chat_presences')) {
            Schema::create('support_chat_presences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('role', 24)->default('customer')->index(); // customer, support
                $table->timestamp('last_seen_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_presences');
        Schema::dropIfExists('support_chat_messages');
        Schema::dropIfExists('support_chat_conversations');
    }
};

