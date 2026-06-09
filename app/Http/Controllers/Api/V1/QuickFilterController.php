<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QuickFilterPublicIndexRequest;
use App\Http\Resources\Api\V1\Admin\QuickFilterOptionResource;
use App\Services\QuickFilterService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class QuickFilterController extends Controller
{
    public function __construct(
        private readonly QuickFilterService $quickFilters,
    ) {}

    /** Enabled options only (supplier / industry pages). */
    public function index(QuickFilterPublicIndexRequest $request): JsonResponse
    {
        $options = $this->quickFilters->listEnabledByType($request->filterType());

        return sendResponse(
            true,
            __('common.success'),
            QuickFilterOptionResource::collection($options),
            HttpStatus::HTTP_OK,
        );
    }
}
