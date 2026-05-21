<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceAiSupportInteractionModel extends Model
{
    public const FEEDBACK_HELPFUL = 'helpful';

    public const FEEDBACK_NOT_HELPFUL = 'not_helpful';

    protected $table = 'ecommerce_ai_support_interactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'tenant_id',
        'topic',
        'user_message',
        'assistant_excerpt',
        'products_shown',
        'feedback',
        'feedback_at',
        'ip_address',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'tenant_id' => 'integer',
        'products_shown' => 'integer',
        'feedback_at' => 'datetime',
    ];
}
