<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['currency_id', 'name', 'description', 'button_text', 'monthly_price', 'yearly_price', 'is_popular', 'status', 'currency_id'])]

class Plan extends Model
{
    use HasTranslations;

    protected $casts = [
        'is_popular' => 'boolean',
    ];

    // Translation methods
    protected function translationModelClass(): string
    {
        return PlanTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description', 'button_text'];
    }

    public function translations()
    {
        return $this->hasMany(PlanTranslation::class, 'plan_id', 'id');
    }

    public function localizeData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'name' => 'name',
                'description' => 'description',
                'button_text' => 'button_text',
            ],
            ['name', 'description', 'button_text'],
            $locale,
            $fallbackLocale
        );

        return [
            'name' => $fields['name'],
            'description' => $fields['description'],
            'button_text' => $fields['button_text'],
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
    // Translation

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'source');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'plan_id', 'id');
    }
}
