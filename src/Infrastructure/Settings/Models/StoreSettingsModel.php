<?php

namespace Src\Infrastructure\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreSettingsModel extends Model
{
    use HasFactory;

    protected $table = 'store_settings';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'company_name',
        'id_nat',
        'rccm',
        'tax_number',
        'street',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'logo_path',
        'currency',
        'exchange_rate',
        'invoice_footer_text',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
    ];

    // Relations
    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }
}
