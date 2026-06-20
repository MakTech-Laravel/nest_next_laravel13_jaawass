<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Manufacturer\ManufacturerReviewCenterResource;
use App\Services\Manufacturer\ManufacturerReviewCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerReviewCenterController extends Controller
{
    public function __construct(
        private readonly ManufacturerReviewCenterService $reviewCenterService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $payload = $this->reviewCenterService->forManufacturer($request->user());

        return sendResponse(
            status: true,
            message: __('api.manufacturer_review_center_fetched_successfully'),
            data: new ManufacturerReviewCenterResource($payload),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
