<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['legal_page_id', 'section_key', 'title', 'content', 'sort'])]
class LegalPageSection extends Model
{
    use HasTranslations;

    protected $casts = [
        'sort' => 'integer',
    ];

    protected function translationModelClass(): string
    {
        return LegalPageSectionTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['title', 'content'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LegalPageSectionTranslation::class, 'legal_page_section_id', 'id');
    }

    public function legalPage(): BelongsTo
    {
        return $this->belongsTo(LegalPage::class, 'legal_page_id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'title' => 'title',
                'content' => 'content',
            ],
            ['title', 'content'],
            $locale,
            $fallbackLocale,
        );

        return [
            'title' => $fields['title'],
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

    /**
     * @param  array<string, string>  $fields
     */
    public function upsertContentTranslations(array $fields, string $locale): void
    {
        $sourceLocale = (string) config('translation.source_locale', 'en');
        $payload = [$locale => $fields];

        if ($locale !== $sourceLocale) {
            $payload[$sourceLocale] = $fields;
        }

        $this->upsertTranslations($payload);
    }
}
