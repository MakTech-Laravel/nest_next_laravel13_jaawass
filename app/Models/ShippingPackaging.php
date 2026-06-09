<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'packaging_type',
    'port_of_loading',
    'packaging_dimensions',
    'packaging_weight',
    'packaging_cost_per_unit',
    'packaging_description',
])]

class ShippingPackaging extends Model
{

    use HasTranslations;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Translations ==================


    public function translationModelClass(): string
    {
        return ShippingPackagingTranslation::class;
    }

    public function translatableFields():array
    {
        return [
            'packaging_type',
            'port_of_loading',
            'packaging_dimensions',
            'packaging_weight',
            'packaging_description',
        ];
    }

    public function translations()
    {
        return $this->hasMany(ShippingPackagingTranslation::class, 'shipping_packaging_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'packaging_type' => 'packaging_type',
                'port_of_loading' => 'port_of_loading',
                'packaging_dimensions' => 'packaging_dimensions',
                'packaging_weight' => 'packaging_weight',
                'packaging_description' => 'packaging_description',
            ],
            ['packaging_type', 'port_of_loading', 'packaging_dimensions', 'packaging_weight', 'packaging_description'],
            $locale,
            $fallbackLocale
        );

        return [
            'packaging_type' => $fields['packaging_type'],
            'port_of_loading' => $fields['port_of_loading'],
            'packaging_dimensions' => $fields['packaging_dimensions'],
            'packaging_weight' => $fields['packaging_weight'],
            'packaging_description' => $fields['packaging_description'],
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


    // End Translations ============
}
