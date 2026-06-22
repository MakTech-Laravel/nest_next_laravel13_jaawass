<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'reviewer_id',
        'rating',
        'title',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
        ];
    }

    protected function translationModelClass(): string
    {
        return ReviewTranslation::class;
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'comment'];
    }

    /**
     * @return HasMany<ReviewTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ReviewTranslation::class, 'review_id', 'id');
    }

    /**
     * @return array{title: mixed, comment: mixed}
     */
    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'title' => 'title',
                'comment' => 'comment',
            ],
            ['title', 'comment'],
            $locale,
            $fallbackLocale,
        );

        return [
            'title' => $fields['title'],
            'comment' => $fields['comment'],
        ];
    }

    /**
     * @param  array<string, string|null>  $sourceData
     */
    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        $sourceData = array_intersect_key(
            $sourceData,
            array_flip($this->translatableFields()),
        );

        if ($sourceData === []) {
            return;
        }

        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }

    /**
     * @param  array<string, string|null>  $sourceData
     */
    public function syncTranslations(array $sourceData, ?string $sourceLocale = null): void
    {
        $sourceData = array_intersect_key(
            $sourceData,
            array_flip($this->translatableFields()),
        );

        if ($sourceData === []) {
            return;
        }

        $locale = $sourceLocale ?? app()->getLocale();

        $this->upsertTranslations([
            $locale => $sourceData,
        ]);

        $this->autoTranslate($sourceData, $locale);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::PUBLISHED->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ReviewStatus::publicValues());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
