<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('support_chat_conversations')) {
            Schema::table('support_chat_conversations', function (Blueprint $table) {
                if (!Schema::hasColumn('support_chat_conversations', 'guest_key')) {
                    $table->string('guest_key', 80)->nullable()->index()->after('user_id');
                }
                if (!Schema::hasColumn('support_chat_conversations', 'guest_name')) {
                    $table->string('guest_name', 120)->nullable()->after('guest_key');
                }
                if (!Schema::hasColumn('support_chat_conversations', 'guest_phone')) {
                    $table->string('guest_phone', 40)->nullable()->after('guest_name');
                }
            });
        }

        if (Schema::hasTable('support_chat_messages')) {
            Schema::table('support_chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('support_chat_messages', 'sender_type')) {
                    $table->string('sender_type', 16)->default('user')->index()->after('sender_user_id'); // user|guest
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_chat_messages') && Schema::hasColumn('support_chat_messages', 'sender_type')) {
            Schema::table('support_chat_messages', function (Blueprint $table) {
                $table->dropColumn('sender_type');
            });
        }

        if (Schema::hasTable('support_chat_conversations')) {
            Schema::table('support_chat_conversations', function (Blueprint $table) {
                if (Schema::hasColumn('support_chat_conversations', 'guest_phone')) $table->dropColumn('guest_phone');
                if (Schema::hasColumn('support_chat_conversations', 'guest_name')) $table->dropColumn('guest_name');
                if (Schema::hasColumn('support_chat_conversations', 'guest_key')) $table->dropColumn('guest_key');
            });
        }
    }
};

