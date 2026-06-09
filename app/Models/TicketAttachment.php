<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'ticket_message_id',
    'disk',
    'file_path',
    'file_mime',
    'original_name',
    'size_bytes',
])]
class TicketAttachment extends Model
{
    protected $appends = ['url'];

    /**
     * @return BelongsTo<TicketMessage, $this>
     */
    public function ticketMessage(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (! is_string($this->file_path) || $this->file_path === '') {
            return null;
        }

        if (filter_var($this->file_path, FILTER_VALIDATE_URL) && str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return Storage::disk((string) $this->disk)->url($this->file_path);
    }
}
