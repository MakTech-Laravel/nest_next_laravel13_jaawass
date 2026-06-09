<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'product_key_feature_id',
    'key_feature',
    'locale',
])]
class ProductKeyFeatureTranslation extends Model
{
    //

    public function productKeyFeature()
    {
        return $this->belongsTo(ProductKeyFeature::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
