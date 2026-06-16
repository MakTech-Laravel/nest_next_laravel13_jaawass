<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'rfq_submission_id',
    'type',
    'disk',
    'file_path',
    'file_mime',
    'original_name',
    'size_bytes',
])]
class RfqQuoteAttachment extends Model
{
    protected $appends = ['url'];

    public function rfqSubmission(): BelongsTo
    {
        return $this->belongsTo(RfqSubmission::class, 'rfq_submission_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! is_string($this->file_path) || $this->file_path === '') {
            return null;
        }

        return Storage::disk((string) $this->disk)->url($this->file_path);
    }
}
