<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'min_price',
    'max_price',
    'currency_id',
    'product_id',
    'minimum_order_quantity',
    'unit',
    'lead_time',
    'production_capacity',
    'production_duration',
    'production_unit',
])]
class PricingQuanity extends Model
{
    //


     public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
