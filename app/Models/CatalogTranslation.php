<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['catalog_id', 'locale', 'name'])]
class CatalogTranslation extends Model
{
    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
