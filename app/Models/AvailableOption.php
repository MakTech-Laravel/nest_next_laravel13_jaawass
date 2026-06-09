<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'product_id',
    'sample_available',
    'sample_price',
    'customization_available',
    'customization_detail',
    'product_broschure'
])]
class AvailableOption extends Model
{
    //
    use HasTranslations;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    //  Translations Options 

    public function translationModelClass(): string
    {
        return AvailableOptionTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['customization_detail'];
    }

    public function translations()
    {
        return $this->hasMany(AvailableOptionTranslation::class, 'available_option_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'customization_detail' => 'customization_detail',
            ],
            ['customization_detail'],
            $locale,
            $fallbackLocale
        );

        return [
            'customization_detail' => $fields['customization_detail'],
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

    
    // End Translations Options
}
