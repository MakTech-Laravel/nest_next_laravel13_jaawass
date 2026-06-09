<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'image_path'])]
class ProductImage extends Model
{
    //

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
