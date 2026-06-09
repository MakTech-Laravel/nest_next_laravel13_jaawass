<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['industry_id', 'name', 'slug', 'description', 'icon', 'tags', 'sort_order'])]
class SubCategory extends Model
{
    use HasTranslations;
    // Translations

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function translationModelClass(): string
    {
        return SubCategoryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    public function translations()
    {
        return $this->hasMany(SubCategoryTranslation::class, 'sub_category_id', 'id');
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
            'description' => $this->description,
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

    // Relations
    public function category()
    {
        return $this->belongsTo(Industry::class, 'industry_id', 'id');
    }
}
