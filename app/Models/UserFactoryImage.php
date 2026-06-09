<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'path', 'mime_type', 'extension', 'original_name'])]
#[Hidden(['user_id'])]
class UserFactoryImage extends Model
{
    protected $table = 'user_factory_images';

    /* --------------------------------------------------------------
    |                       Relationships
    | -------------------------------------------------------------- */

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getUrlAttribute(): ?string
    {
        $url = $this->path;

        if (! is_string($url) || $url === '' || $url === null) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
            return $url;
        }

        return storage_url($url);
    }
}
