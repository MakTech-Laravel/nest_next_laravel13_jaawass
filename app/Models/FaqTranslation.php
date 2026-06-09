<?php

namespace App\Models;

use Illuminate\Console\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTranslations;

#[Fillable(['faq_id', 'locale', 'question', 'answer'])]
#[Hidden(['id'])]

class FaqTranslation extends Model
{
    
    
    public function faq()
    {
        return $this->belongsTo(Faq::class, 'faq_id', 'id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
