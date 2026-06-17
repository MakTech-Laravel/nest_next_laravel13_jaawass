<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ManufacturerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerDashboardController extends Controller
{
    public function __construct(
        private readonly ManufacturerDashboardService $dashboardService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_dashboard_fetched_successfully'),
            data: $this->dashboardService->overview($request->user()),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
