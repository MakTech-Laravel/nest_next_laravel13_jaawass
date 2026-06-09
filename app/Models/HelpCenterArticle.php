<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Database\Factories\HelpCenterArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'help_center_category_id',
    'title',
    'description',
    'help_full',
    'not_help_full',
    'sort_order',
    'status',
    'views',
])]
class HelpCenterArticle extends Model
{
    /** @use HasFactory<HelpCenterArticleFactory> */
    use HasFactory, HasTranslations;

    protected $casts = [
        'status' => 'boolean',
        'help_full' => 'integer',
        'not_help_full' => 'integer',
        'sort_order' => 'integer',
    ];

    protected function translationModelClass(): string
    {
        return HelpCenterArticleTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['title', 'description'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HelpCenterArticleTranslation::class, 'help_center_article_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCenterCategory::class, 'help_center_category_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(HelpCenterArticleStep::class, 'help_center_article_id', 'id')
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END, sort_order ASC');
    }

    public function localizeData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'title' => 'title',
                'description' => 'description',
            ],
            ['title', 'description'],
            $locale,
            $fallbackLocale,
        );

        return [
            'title' => $fields['title'],
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

    /**
     * @param  array<int, array{content: string, sort_order?: int}>  $steps
     */
    public function syncSteps(array $steps, string $locale): void
    {
        $this->steps()->each(fn (HelpCenterArticleStep $step) => $step->delete());

        foreach ($steps as $index => $stepData) {
            $sortOrder = $stepData['sort_order'] ?? ($index + 1);

            $step = $this->steps()->create([
                'content' => $stepData['content'],
                'sort_order' => $sortOrder,
            ]);

            $step->upsertTranslations([
                $locale => ['content' => $stepData['content']],
            ]);

            $step->autoTranslate(
                sourceData: ['content' => $stepData['content']],
                sourceLocale: $locale,
            );
        }
    }
}
