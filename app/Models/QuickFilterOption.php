<?php

namespace App\Models;

use App\Enums\QuickFilterType;
use Database\Factories\QuickFilterOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'display_label', 'value', 'is_enabled', 'sort_order'])]
class QuickFilterOption extends Model
{
    /** @use HasFactory<QuickFilterOptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => QuickFilterType::class,
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForType(Builder $query, QuickFilterType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
