<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'user_id', 'total_downloads', 'file_size', 'file_path', 'status'])]
class Catalog extends Model
{
    //
    use HasTranslations;


    // Translations 

    public function translationModelClass(): string
    {
        return CatalogTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    public function translations()
    {
        return $this->hasMany(CatalogTranslation::class, 'catalog_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
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



    // Translations End

    public function user(){

        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
