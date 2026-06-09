<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sub_category_id', 'locale', 'name'])]
class SubCategoryTranslation extends Model
{
    //

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id', 'id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
