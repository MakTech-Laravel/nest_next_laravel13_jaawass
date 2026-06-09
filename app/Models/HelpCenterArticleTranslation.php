<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['help_center_article_id', 'locale', 'title', 'description'])]
#[Hidden(['help_center_article_id'])]
class HelpCenterArticleTranslation extends Model
{
    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticle::class, 'help_center_article_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
