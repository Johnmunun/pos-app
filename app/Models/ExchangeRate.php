<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent: ExchangeRate
 *
 * Représentation de la table 'exchange_rates'.
 *
 * @property int $tenant_id
 * @property int $from_currency_id
 * @property int $to_currency_id
 * @property float $rate
 * @property \Carbon\Carbon $effective_date
 * @property \App\Models\Currency|null $fromCurrency
 * @property \App\Models\Currency|null $toCurrency
 */
class ExchangeRate extends Model
{
    use HasFactory;

    protected $table = 'exchange_rates';

    protected $fillable = [
        'tenant_id',
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
}