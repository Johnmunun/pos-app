<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $message
 * @property string|null $attachment_path
 * @property \App\Models\User|null $user
 */
class SupportTicketReplyModel extends Model
{
    protected $table = 'support_ticket_replies';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment_path',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicketModel::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

