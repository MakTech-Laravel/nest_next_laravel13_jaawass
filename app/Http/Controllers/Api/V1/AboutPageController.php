<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AboutPageResource;
use App\Models\AboutPage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AboutPageController extends Controller
{
    public function show()
    {
        $page = AboutPage::query()
            ->where('enabled', true)
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
}
