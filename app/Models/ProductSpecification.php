<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'specification_title', 'specification_value'])]
class ProductSpecification extends Model
{
    //
    use HasTranslations; 
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // ================ Translations ================
    
    
    public function translationModelClass() :string
    {
        return ProductSpecificationTranslation::class;
    }
    
    public function translatableFields():array
    {
        return ['specification_title', 'specification_value'];
    }

    public function translations()
    {
        return $this->hasMany(ProductSpecificationTranslation::class, 'product_specification_id', 'id');
    }

 public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'specification_title' => 'specification_title',
                'specification_value' => 'specification_value',
            ],
            ['specification_title', 'specification_value'],
            $locale,
            $fallbackLocale
        );

          return [
            'specification_title' => $fields['specification_title'],
            'specification_value' => $fields['specification_value'],
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

    // ================ End Translations ================
}
