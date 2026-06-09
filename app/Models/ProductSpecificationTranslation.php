<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'product_specification_id',
    'locale',
    'specification_title',
    'specification_value',
])]
class ProductSpecificationTranslation extends Model
{
    //

    public function productSpecification()
    {
        return $this->belongsTo(ProductSpecification::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
