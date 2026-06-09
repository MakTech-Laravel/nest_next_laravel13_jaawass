<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;


class FaqCategory extends Model
{

    use HasTranslations;

    //
    protected $fillable = [
        'name',
        'slug',
        'sort',
    ];

    protected function translationModelClass(): string
    {
        return FaqCategoryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }
    
    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function translations()
    {
        return $this->hasMany(FaqCategoryTranslation::class, 'faq_category_id', 'id');
    }

    

    public function localizedName(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'name' => 'name',
            ],
            ['name'],
            $locale,
            $fallbackLocale
        );

          return [
            'name' => $fields['name'],
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

}
