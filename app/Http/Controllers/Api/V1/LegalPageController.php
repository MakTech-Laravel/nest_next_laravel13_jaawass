<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LegalPageResource;
use App\Models\LegalPage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class LegalPageController extends Controller
{
    public function index()
    {
        $pages = LegalPage::query()
            ->where('enabled', true)
            ->with(['translations'])
            ->orderBy('sort')
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: LegalPageResource::collection($pages),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(string $slug)
    {
        $page = LegalPage::query()
            ->where('slug', $slug)
            ->where('enabled', true)
            ->with(['translations', 'sections.translations'])
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
            data: new LegalPageResource($page),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
