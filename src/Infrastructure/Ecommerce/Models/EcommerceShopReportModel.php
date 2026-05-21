<?php

namespace Src\Infrastructure\Ecommerce\Models;

use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $shop_id
 * @property int|null $tenant_id
 * @property string $reason
 * @property string|null $details
 * @property string|null $reporter_name
 * @property string|null $reporter_email
 * @property string $status
 */
class EcommerceShopReportModel extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_DISMISSED = 'dismissed';

    public const REASON_SCAM = 'scam';
    public const REASON_COUNTERFEIT = 'counterfeit';
    public const REASON_INAPPROPRIATE = 'inappropriate';
    public const REASON_SPAM = 'spam';
    public const REASON_OTHER = 'other';

    protected $table = 'ecommerce_shop_reports';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'tenant_id',
        'reason',
        'details',
        'reporter_name',
        'reporter_email',
        'ip_address',
        'user_agent',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public static function reasonLabels(): array
    {
        return [
            self::REASON_SCAM => 'Arnaque / fraude',
            self::REASON_COUNTERFEIT => 'Produits contrefaits ou trompeurs',
            self::REASON_INAPPROPRIATE => 'Contenu inapproprié',
            self::REASON_SPAM => 'Spam ou publicité abusive',
            self::REASON_OTHER => 'Autre',
        ];
    }
}
