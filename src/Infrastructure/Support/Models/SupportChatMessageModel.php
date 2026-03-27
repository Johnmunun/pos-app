<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatMessageModel extends Model
{
    protected $table = 'support_chat_messages';

    protected $casts = [
        'pinned_at' => 'datetime',
    ];

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'sender_type',
        'message',
        'attachment_path',
        'attachment_mime',
        'pinned_at',
        'pinned_by_user_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportChatConversationModel::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_user_id');
    }
}

