<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['help_center_article_step_id', 'locale', 'content'])]
#[Hidden(['help_center_article_step_id'])]
class HelpCenterArticleStepTranslation extends Model
{
    public function step(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticleStep::class, 'help_center_article_step_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
