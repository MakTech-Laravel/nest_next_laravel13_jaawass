<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {}

    public function overview(): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.admin_dashboard_fetched_successfully'),
            data: $this->dashboardService->overview(),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
