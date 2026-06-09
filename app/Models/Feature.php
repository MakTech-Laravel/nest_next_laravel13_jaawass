<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;



#[Fillable(['name','key'])]
#[Hidden(['id'])]
class Feature extends Model
{

    use HasTranslations;
    // Translations
    protected function translationModelClass(): string
    {
        return FeatureTransaltion::class;
    }
    public function translatableFields(): array
    {
        return ['name'];
    }
  
    public function translations()
    {
        return $this->hasMany(FeatureTransaltion::class, 'feature_id', 'id');
    }

 public function localizeData(?string $locale = null, ?string $fallbackLocale = null): array
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
    // Translations
  



    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class);
    }

}
