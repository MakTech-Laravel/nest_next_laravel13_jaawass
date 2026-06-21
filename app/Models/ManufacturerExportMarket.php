<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturerExportMarket extends Model
{
    protected $fillable = [
        'user_id',
        'region',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function countries(): HasMany
    {
        return $this->hasMany(ManufacturerExportMarketCountry::class, 'manufacturer_export_market_id');
    }
}
