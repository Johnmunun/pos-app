<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatPresenceModel extends Model
{
    protected $table = 'support_chat_presences';

    protected $fillable = [
        'user_id',
        'role',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

