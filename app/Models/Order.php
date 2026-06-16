<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'buyer_id',
    'manufacturer_id',
    'product_id',
    'title',
    'quantity',
    'quantity_unit',
    'total_amount',
    'currency_code',
    'estimated_delivery_at',
    'production_lead',
    'payment_terms',
    'shipping_terms',
    'destination',
    'notes',
    'status',
])]
class Order extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_amount' => 'decimal:2',
            'estimated_delivery_at' => 'date',
            'status' => OrderStatus::class,
        ];
    }

    protected function translationModelClass(): string
    {
        return OrderTranslation::class;
    }

    /**
     * @return string[]
     */
    public function translatableFields(): array
    {
        return ['title', 'notes'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(OrderTranslation::class, 'order_id', 'id');
    }

    /**
     * @return array{title: string, notes: mixed}
     */
    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'title' => 'title',
                'notes' => 'notes',
            ],
            ['title', 'notes'],
            $locale,
            $fallbackLocale
        );

        return [
            'title' => $fields['title'],
            'notes' => $fields['notes'],
        ];
    }

    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(OrderStatusUpdate::class, 'order_id', 'id');
    }
}
