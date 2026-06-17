<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BuyerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BuyerDashboardController extends Controller
{
    public function __construct(
        private readonly BuyerDashboardService $dashboardService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.buyer_dashboard_fetched_successfully'),
            data: $this->dashboardService->overview($request->user()),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
