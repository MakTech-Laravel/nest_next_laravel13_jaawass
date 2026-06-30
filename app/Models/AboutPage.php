<?php

namespace App\Models;

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

        $this->update(['content' => $content]);

        $locales = array_unique([$locale, $sourceLocale]);

        foreach ($locales as $targetLocale) {
            $this->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                ['content' => $content]
            );
        }
    }

    private function normalizeLocale(string $locale): string
    {
        $supported = config('localization.supported_locales', ['en']);
        $sourceLocale = (string) config('translation.source_locale', 'en');

        if (in_array($locale, $supported, true)) {
            return $locale;
        }

        if (str_starts_with($locale, 'zh')) {
            return in_array('zh_CN', $supported, true) ? 'zh_CN' : $sourceLocale;
        }

        return $sourceLocale;
    }
}
