<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['legal_page_section_id', 'locale', 'title', 'content'])]
#[Hidden(['legal_page_section_id'])]
class LegalPageSectionTranslation extends Model
{
    public function section(): BelongsTo
    {
        return $this->belongsTo(LegalPageSection::class, 'legal_page_section_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
