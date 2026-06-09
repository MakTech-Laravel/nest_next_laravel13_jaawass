<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['promotion_id', 'locale', 'promotion_title', 'short_description', 'button_text', 'cta_button_text', 'highlight_text'])]
#[Hidden(['promotion_id'])]
class PromotionTranslation extends Model
{
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
