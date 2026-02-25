<?php

namespace Src\Infrastructure\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $shop_id
 * @property string $type
 * @property string $party_id
 * @property float $total_amount
 * @property float $paid_amount
 * @property string $currency
 * @property string $reference_type
 * @property string|null $reference_id
 * @property string $status
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $settled_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DebtModel extends Model
{
    protected $table = 'finance_debts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'shop_id', 'type', 'party_id',
        'total_amount', 'paid_amount', 'currency',
        'reference_type', 'reference_id', 'status', 'due_date', 'settled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'settled_at' => 'datetime',
    ];
}
