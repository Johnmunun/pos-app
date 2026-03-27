<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChatConversationModel extends Model
{
    protected $table = 'support_chat_conversations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'guest_key',
        'guest_name',
        'guest_phone',
        'assigned_to_user_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportChatMessageModel::class, 'conversation_id');
    }
}

