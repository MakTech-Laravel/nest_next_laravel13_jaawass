<?php

namespace App\Services\Translation;

use App\Models\AboutPage;
use App\Models\Language;
use App\Support\Cms\AboutPageContentFlattener;
use Illuminate\Support\Facades\Log;

final class AboutPageContentTranslationService
{
    public function __construct(
        private readonly GoogleTranslationService $translator,
    ) {}

    /**
     * @param  array<string, mixed>  $sourceContent
     */
    public function handle(
        AboutPage $page,
        array $sourceContent,
        ?string $sourceLocale = null,
        ?string $modelUpdatedAtSnapshot = null,
    ): void {
        if ($modelUpdatedAtSnapshot !== null) {
            $page->refresh();
            $currentUpdatedAt = $page->updated_at?->toIso8601String();

            if ($currentUpdatedAt !== $modelUpdatedAtSnapshot) {
                Log::info('AboutPageContentTranslationService: stale translation job skipped.', [
                    'about_page_id' => $page->id,
                ]);

                return;
            }
        }

        $flat = AboutPageContentFlattener::flatten($sourceContent);

        if ($flat === []) {
            return;
        }

        $resolvedSource = $sourceLocale ?? (string) config('translation.source_locale', 'en');
        $targets = Language::translationTargets($resolvedSource);

        $page->translations()->updateOrCreate(
            ['locale' => $resolvedSource],
            ['content' => $sourceContent]
        );

        foreach ($targets as $language) {
            try {
                $translatedFlat = $this->translator->translateBatch(
                    $flat,
                    $language->locale,
                    $resolvedSource
                );

                $translatedContent = AboutPageContentFlattener::apply($sourceContent, $translatedFlat);

                $page->translations()->updateOrCreate(
                    ['locale' => $language->locale],
                    ['content' => $translatedContent]
                );
            } catch (\Throwable $e) {
                Log::error('AboutPageContentTranslationService: failed locale translation.', [
                    'about_page_id' => $page->id,
                    'locale' => $language->locale,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AboutPageContentTranslationService: translations persisted.', [
            'about_page_id' => $page->id,
            'source' => $resolvedSource,
            'targets' => $targets->pluck('locale')->all(),
        ]);
    }
}
