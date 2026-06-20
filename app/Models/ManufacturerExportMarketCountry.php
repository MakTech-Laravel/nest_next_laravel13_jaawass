<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerExportMarketCountry extends Model
{
    protected $fillable = [
        'manufacturer_export_market_id',
        'country_code',
        'country_name',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(ManufacturerExportMarket::class, 'manufacturer_export_market_id');
    }
}
