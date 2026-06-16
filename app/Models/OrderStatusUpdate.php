<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'user_id',
    'status',
    'notes',
])]
class OrderStatusUpdate extends Model
{
    use HasTranslations;

    protected function translationModelClass(): string
    {
        return OrderStatusUpdateTranslation::class;
    }

    /**
     * @return string[]
     */
    public function translatableFields(): array
    {
        return ['notes'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(OrderStatusUpdateTranslation::class, 'order_status_update_id', 'id');
    }

    /**
     * @return array{notes: mixed}
     */
    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'notes' => 'notes',
            ],
            ['notes'],
            $locale,
            $fallbackLocale
        );

        return [
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderStatusUpdateAttachment::class, 'order_status_update_id', 'id');
    }
}
