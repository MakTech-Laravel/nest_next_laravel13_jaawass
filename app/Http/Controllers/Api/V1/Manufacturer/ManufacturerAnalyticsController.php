<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\ManufacturerAnalyticsCountryRequest;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\ManufacturerAnalyticsFunnelRequest;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\ManufacturerAnalyticsMetricsRequest;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\ManufacturerAnalyticsPerformanceRequest;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\ManufacturerAnalyticsProductRequest;
use App\Services\Analytics\ManufacturerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAnalyticsController extends Controller
{
    public function __construct(
        private readonly ManufacturerAnalyticsService $analyticsService,
    ) {}

    public function metrics(ManufacturerAnalyticsMetricsRequest $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_analytics_metrics_fetched_successfully'),
            data: $this->analyticsService->metrics(
                $request->user(),
                $request->resolvedPeriodRange(),
            ),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function performance(ManufacturerAnalyticsPerformanceRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->performance(
            manufacturer: $request->user(),
            range: $request->resolvedPeriodRange(),
            search: $request->searchTerm(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_analytics_performance_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function products(ManufacturerAnalyticsProductRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->products(
            manufacturer: $request->user(),
            range: $request->resolvedPeriodRange(),
            search: $request->searchTerm(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_analytics_products_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function countries(ManufacturerAnalyticsCountryRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->countries(
            manufacturer: $request->user(),
            range: $request->resolvedPeriodRange(),
            search: $request->searchTerm(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_analytics_countries_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function funnel(ManufacturerAnalyticsFunnelRequest $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_analytics_funnel_fetched_successfully'),
            data: $this->analyticsService->funnel(
                $request->user(),
                $request->resolvedPeriodRange(),
            ),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
