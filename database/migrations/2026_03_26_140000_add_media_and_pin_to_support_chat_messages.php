<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('support_chat_messages', 'attachment_path')) {
                $table->string('attachment_path', 255)->nullable()->after('message');
            }
            if (!Schema::hasColumn('support_chat_messages', 'attachment_mime')) {
                $table->string('attachment_mime', 120)->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('support_chat_messages', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->index()->after('attachment_mime');
            }
            if (!Schema::hasColumn('support_chat_messages', 'pinned_by_user_id')) {
                $table->unsignedBigInteger('pinned_by_user_id')->nullable()->index()->after('pinned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_chat_messages', 'pinned_by_user_id')) {
                $table->dropColumn('pinned_by_user_id');
            }
            if (Schema::hasColumn('support_chat_messages', 'pinned_at')) {
                $table->dropColumn('pinned_at');
            }
            if (Schema::hasColumn('support_chat_messages', 'attachment_mime')) {
                $table->dropColumn('attachment_mime');
            }
            if (Schema::hasColumn('support_chat_messages', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
