<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $status
 * @property string $priority
 * @property string $module
 * @property \App\Models\User|null $user
 * @property \App\Models\User|null $assignedTo
 */
class SupportTicketModel extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'priority',
        'category',
        'module',
        'status',
        'attachment_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReplyModel::class, 'ticket_id');
    }
}

