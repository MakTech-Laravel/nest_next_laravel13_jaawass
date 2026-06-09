<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'status', 'description', 'sort_order'])]
class HelpCenterCategory extends Model
{
    use HasTranslations;

    protected $casts = [
        'status' => 'boolean',
    ];

    protected function translationModelClass(): string
    {
        return HelpCenterCategoryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    public function translations()
    {
        return $this->hasMany(HelpCenterCategoryTranslation::class, 'help_center_category_id', 'id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(HelpCenterArticle::class, 'help_center_category_id', 'id')
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END, sort_order ASC');
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
            ],
            ['name', 'description'],
            $locale,
            $fallbackLocale
        );

        return [
            'name' => $fields['name'],
            'description' => $fields['description'],
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

}
