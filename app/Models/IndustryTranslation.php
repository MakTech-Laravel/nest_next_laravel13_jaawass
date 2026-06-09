<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['industry_id', 'name', 'locale'])]
class IndustryTranslation extends Model
{
    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id', 'id');
    }
    
    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
