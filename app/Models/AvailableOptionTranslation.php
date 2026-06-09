<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['available_option_id', 'locale', 'customization_detail'])]
class AvailableOptionTranslation extends Model
{
    //

    public function availableOption()
    {
        return $this->belongsTo(AvailableOption::class);
    }
    
    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
