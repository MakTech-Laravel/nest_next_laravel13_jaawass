<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'status'])]
class ArticleCategory extends Model
{
    //
    use HasTranslations;

    protected $casts = [
        'status' => 'boolean',
    ];

    // Translations
    public function translationModelClass(): string
    {
        return ArticleCategoryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    public function translations()
    {
        return $this->hasMany(ArticleCategoryTranslation::class, 'article_category_id', 'id');
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

    // Relations
    public function articles()
    {
        return $this->hasMany(Article::class, 'article_category_id', 'id');
    }
}
