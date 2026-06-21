<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Analytics\AdminAnalyticsCountryRequest;
use App\Http\Requests\Api\V1\Admin\Analytics\AdminAnalyticsGrowthRequest;
use App\Http\Requests\Api\V1\Admin\Analytics\AdminAnalyticsIndustryRequest;
use App\Http\Requests\Api\V1\Admin\Analytics\AdminAnalyticsMetricsRequest;
use App\Services\Analytics\AdminAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminAnalyticsController extends Controller
{
    public function __construct(
        private readonly AdminAnalyticsService $analyticsService,
    ) {}

    public function metrics(AdminAnalyticsMetricsRequest $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.admin_analytics_metrics_fetched_successfully'),
            data: $this->analyticsService->metrics($request->resolvedPeriodRange()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function growth(AdminAnalyticsGrowthRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->growth(
            search: $request->searchTerm(),
            year: $request->year(),
            months: $request->months(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.admin_analytics_growth_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function countries(AdminAnalyticsCountryRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->countries(
            search: $request->searchTerm(),
            roleFilter: $request->roleFilter(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.admin_analytics_countries_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function industries(AdminAnalyticsIndustryRequest $request): JsonResponse
    {
        $paginator = $this->analyticsService->industries(
            search: $request->searchTerm(),
            orderBy: $request->orderByColumn(),
            orderDirection: $request->orderDirection(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.admin_analytics_industries_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
