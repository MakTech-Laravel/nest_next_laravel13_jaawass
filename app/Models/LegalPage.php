<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'title', 'last_updated_label', 'enabled', 'sort'])]
class LegalPage extends Model
{
    use HasTranslations;

    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];

    protected function translationModelClass(): string
    {
        return LegalPageTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['title'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LegalPageTranslation::class, 'legal_page_id', 'id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LegalPageSection::class, 'legal_page_id', 'id')
            ->orderBy('sort');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            ['title' => 'title'],
            ['title'],
            $locale,
            $fallbackLocale,
        );

        return [
            'title' => $fields['title'],
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

    /**
     * @param  array<int, array{id?: int|null, section_key: string, title: string, content: string, sort: int}>  $sections
     */
    public function syncSections(array $sections, string $locale): void
    {
        $keptIds = [];

        foreach ($sections as $index => $sectionData) {
            $sort = (int) ($sectionData['sort'] ?? ($index + 1));
            $sectionKey = (string) $sectionData['section_key'];
            $title = (string) $sectionData['title'];
            $content = (string) $sectionData['content'];

            $section = null;
            if (! empty($sectionData['id'])) {
                $section = $this->sections()->whereKey($sectionData['id'])->first();
            }

            if ($section === null) {
                $section = $this->sections()->firstOrNew(['section_key' => $sectionKey]);
            }

            $section->fill([
                'section_key' => $sectionKey,
                'title' => $title,
                'content' => $content,
                'sort' => $sort,
            ]);
            $section->save();

            $keptIds[] = $section->id;

            $translationFields = [
                'title' => $title,
                'content' => $content,
            ];

            $section->upsertContentTranslations($translationFields, $locale);

            $section->autoTranslate(
                sourceData: $translationFields,
                sourceLocale: $locale,
            );
        }

        if ($keptIds !== []) {
            $this->sections()->whereNotIn('id', $keptIds)->each(
                fn (LegalPageSection $section) => $section->delete()
            );
        } else {
            $this->sections()->delete();
        }
    }
}
