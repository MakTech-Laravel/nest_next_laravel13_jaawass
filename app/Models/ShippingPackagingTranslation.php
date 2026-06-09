<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'shipping_packaging_id',
    'packaging_type',
    'port_of_loading',
    'packaging_dimensions',
    'packaging_weight',
    'packaging_description',
    'locale',
])]
class ShippingPackagingTranslation extends Model
{
    //

    public function shippingPackaging()
    {
        return $this->belongsTo(ShippingPackaging::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
