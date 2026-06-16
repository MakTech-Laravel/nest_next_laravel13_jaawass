<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_status_update_id', 'locale', 'notes'])]
#[Hidden(['order_status_update_id'])]
class OrderStatusUpdateTranslation extends Model
{
    public function orderStatusUpdate(): BelongsTo
    {
        return $this->belongsTo(OrderStatusUpdate::class, 'order_status_update_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
