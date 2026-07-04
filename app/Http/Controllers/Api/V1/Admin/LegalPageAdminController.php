<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateLegalPageContentRequest;
use App\Http\Resources\Api\V1\LegalPageResource;
use App\Models\LegalPage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class LegalPageAdminController extends Controller
{
    public function index()
    {
        $pages = LegalPage::query()
            ->with(['translations', 'sections.translations'])
            ->orderBy('sort')
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: LegalPageResource::collection($pages),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(int $legalPage)
    {
        $page = LegalPage::query()
            ->with(['translations', 'sections.translations'])
            ->find($legalPage);

        if ($page === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new LegalPageResource($page),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function updateContent(UpdateLegalPageContentRequest $request, int $legalPage)
    {
        $page = LegalPage::query()->find($legalPage);

        if ($page === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $validated = $request->validated();
        $locale = $this->normalizeContentLocale($validated['locale'] ?? app()->getLocale());
        $sections = $validated['sections'];

        DB::transaction(function () use ($page, $validated, $locale, $sections): void {
            $lastUpdated = $validated['last_updated'] ?? $page->last_updated_label;

            $page->update([
                'title' => $validated['title'],
                'last_updated_label' => $lastUpdated,
                'enabled' => $validated['enabled'] ?? $page->enabled,
                'sort' => $validated['sort'] ?? $page->sort,
            ]);

            $translationFields = [
                'title' => $validated['title'],
                'last_updated_label' => (string) $lastUpdated,
            ];

            $page->upsertContentTranslations($translationFields, $locale);

            $page->autoTranslate(
                sourceData: $translationFields,
                sourceLocale: $locale,
            );

            $page->syncSections($sections, $locale);
        });

        $page->load(['translations', 'sections.translations']);

        return sendResponse(
            status: true,
            message: __('legal_pages.updated'),
            data: new LegalPageResource($page->fresh(['translations', 'sections.translations'])),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    private function normalizeContentLocale(string $locale): string
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
