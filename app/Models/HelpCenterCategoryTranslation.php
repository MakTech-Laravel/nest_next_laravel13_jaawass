<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable(['help_center_category_id', 'name', 'description', 'locale'])]
#[Hidden(['help_center_category_id'])]
class HelpCenterCategoryTranslation extends Model
{
    public function helpCenterCategory()
    {
        return $this->belongsTo(HelpCenterCategory::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
