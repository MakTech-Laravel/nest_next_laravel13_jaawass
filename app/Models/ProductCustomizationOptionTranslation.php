<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_customization_option_id', 'locale', 'option'])]
class ProductCustomizationOptionTranslation extends Model
{

    public function productCustomizationOption()
    {
        return $this->belongsTo(ProductCustomizationOption::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
