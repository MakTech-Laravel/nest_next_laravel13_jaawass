<?php

namespace App\Models;

use App\Jobs\TranslateAboutPageContentJob;
use App\Support\Localization\LocaleCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enabled', 'content'])]
class AboutPage extends Model
{
    protected $casts = [
        'enabled' => 'boolean',
        'content' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AboutPageTranslation::class, 'about_page_id', 'id');
    }

    public static function singleton(): self
    {
        return static::query()->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function localizedContent(?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale());
        $sourceLocale = (string) config('translation.source_locale', 'en');

        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('locale', $locale);
            if ($translation !== null && filled($translation->content)) {
                return $translation->content;
            }

            if ($locale !== $sourceLocale) {
                $sourceTranslation = $this->translations->firstWhere('locale', $sourceLocale);
                if ($sourceTranslation !== null && filled($sourceTranslation->content)) {
                    return $sourceTranslation->content;
                }
            }
        }

        return $this->content ?? [];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function upsertContent(array $content, string $locale): void
    {
        $locale = $this->normalizeLocale($locale);
        $sourceLocale = (string) config('translation.source_locale', 'en');

        if ($locale === $sourceLocale) {
            $this->update(['content' => $content]);
        }

        $this->translations()->updateOrCreate(
            ['locale' => $locale],
            ['content' => $content]
        );

        $this->autoTranslateContent($content, $locale);
    }

    /**
     * @param  array<string, mixed>  $sourceContent
     */
    public function autoTranslateContent(array $sourceContent, ?string $sourceLocale = null): void
    {
        $snapshot = $this->updated_at?->toIso8601String();

        if (config('translation.queue.enabled', true)) {
            TranslateAboutPageContentJob::dispatch(
                (int) $this->id,
                $sourceContent,
                $sourceLocale,
                $snapshot,
            );
        } else {
            app(\App\Services\Translation\AboutPageContentTranslationService::class)
                ->handle($this, $sourceContent, $sourceLocale, $snapshot);
        }
    }

    private function normalizeLocale(string $locale): string
    {
        $supported = config('localization.supported_locales', ['en']);
        $sourceLocale = (string) config('translation.source_locale', 'en');

        return LocaleCode::resolveSupported($locale, $supported)
            ?? $sourceLocale;
    }
}
