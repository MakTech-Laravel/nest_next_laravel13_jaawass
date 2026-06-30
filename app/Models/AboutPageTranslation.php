<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['about_page_id', 'locale', 'content'])]
class AboutPageTranslation extends Model
{
    protected $casts = [
        'content' => 'array',
    ];

    public function aboutPage(): BelongsTo
    {
        return $this->belongsTo(AboutPage::class, 'about_page_id', 'id');
    }
}
