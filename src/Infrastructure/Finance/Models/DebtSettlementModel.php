<?php

namespace Src\Infrastructure\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $debt_id
 * @property float $amount
 * @property string $currency
 * @property string|null $payment_method
 * @property string|null $reference
 * @property int $recorded_by
 * @property \Carbon\Carbon $paid_at
 */
class DebtSettlementModel extends Model
{
    protected $table = 'finance_debt_settlements';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'debt_id', 'amount', 'currency', 'payment_method', 'reference', 'recorded_by', 'paid_at'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
}
