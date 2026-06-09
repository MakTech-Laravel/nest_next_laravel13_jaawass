<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['help_center_article_id', 'content', 'sort_order'])]
class HelpCenterArticleStep extends Model
{
    use HasTranslations;

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected function translationModelClass(): string
    {
        return HelpCenterArticleStepTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['content'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HelpCenterArticleStepTranslation::class, 'help_center_article_step_id', 'id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticle::class, 'help_center_article_id');
    }

    public function localizeData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            ['content' => 'content'],
            ['content'],
            $locale,
            $fallbackLocale,
        );

        return [
            'content' => $fields['content'],
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
