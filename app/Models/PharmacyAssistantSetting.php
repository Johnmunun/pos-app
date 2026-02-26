<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paramètres vocaux de l'assistant par pharmacie (shop).
 *
 * @property int $id
 * @property int $shop_id
 * @property bool $voice_enabled
 * @property string $voice_type male|female
 * @property float $voice_speed
 * @property bool $auto_play
 * @property string $language fr|en|auto
 */
class PharmacyAssistantSetting extends Model
{
    protected $table = 'pharmacy_assistant_settings';

    protected $fillable = [
        'shop_id',
        'voice_enabled',
        'voice_type',
        'voice_speed',
        'auto_play',
        'language',
    ];

    protected $casts = [
        'voice_enabled' => 'boolean',
        'auto_play' => 'boolean',
        'voice_speed' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public static function defaults(): array
    {
        return [
            'voice_enabled' => true,
            'voice_type' => 'female',
            'voice_speed' => 1.0,
            'auto_play' => true,
            'language' => 'auto',
        ];
    }
}
