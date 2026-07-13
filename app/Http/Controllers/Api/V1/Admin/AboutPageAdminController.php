<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAboutPageRequest;
use App\Http\Resources\Api\V1\AboutPageResource;
use App\Models\AboutPage;
use App\Support\Localization\LocaleCode;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AboutPageAdminController extends Controller
{
    public function show()
    {
        $page = AboutPage::query()
            ->with(['translations'])
            ->first();

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
            data: new AboutPageResource($page),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function update(UpdateAboutPageRequest $request)
    {
        $page = AboutPage::query()->first();

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
        $content = $validated['content'];

        DB::transaction(function () use ($page, $validated, $locale, $content): void {
            if (array_key_exists('enabled', $validated)) {
                $page->update(['enabled' => (bool) $validated['enabled']]);
            }

            $page->upsertContent($content, $locale);
        });

        $page->load(['translations']);

        return sendResponse(
            status: true,
            message: __('about_pages.updated'),
            data: new AboutPageResource($page->fresh(['translations'])),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    private function normalizeContentLocale(string $locale): string
    {
        $supported = config('localization.supported_locales', ['en']);
        $sourceLocale = (string) config('translation.source_locale', 'en');

        return LocaleCode::resolveSupported($locale, $supported)
            ?? $sourceLocale;
    }
}
