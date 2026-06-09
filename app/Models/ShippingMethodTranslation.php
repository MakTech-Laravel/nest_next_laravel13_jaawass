<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['shipping_method_id', 'name', 'locale'])]
class ShippingMethodTranslation extends Model
{
    
    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
