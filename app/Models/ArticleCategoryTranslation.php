<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'article_category_id', 'locale' ])]
class ArticleCategoryTranslation extends Model
{
    public function articleCategory()
    {
        return $this->belongsTo(ArticleCategory::class);
    }
    
    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
