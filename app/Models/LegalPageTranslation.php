<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['legal_page_id', 'locale', 'title', 'last_updated_label'])]
#[Hidden(['legal_page_id'])]
class LegalPageTranslation extends Model
{
    public function legalPage(): BelongsTo
    {
        return $this->belongsTo(LegalPage::class, 'legal_page_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
