<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'key_feature'])]
class ProductKeyFeature extends Model
{
    use HasTranslations;
    // 
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // =============== Translations ==============

    public function translationModelClass(): string
    {
        return ProductKeyFeatureTranslation::class;
    }

    public function translatableFields():array
    {
        return ['key_feature'];
    }

    public function translations()
    {
        return $this->hasMany(ProductKeyFeatureTranslation::class, 'product_key_feature_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'key_feature' => 'key_feature',
            ],
            ['key_feature'],
            $locale,
            $fallbackLocale
        );

          return [
            'key_feature' => $fields['key_feature'],
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
    // =============== End Translations ==============
}
