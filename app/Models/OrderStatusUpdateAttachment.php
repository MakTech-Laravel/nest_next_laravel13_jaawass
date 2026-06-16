<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'order_status_update_id',
    'type',
    'disk',
    'file_path',
    'file_mime',
    'original_name',
    'size_bytes',
])]
class OrderStatusUpdateAttachment extends Model
{
    protected $appends = ['url'];

    public function orderStatusUpdate(): BelongsTo
    {
        return $this->belongsTo(OrderStatusUpdate::class, 'order_status_update_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! is_string($this->file_path) || $this->file_path === '') {
            return null;
        }

        return Storage::disk((string) $this->disk)->url($this->file_path);
    }
}
