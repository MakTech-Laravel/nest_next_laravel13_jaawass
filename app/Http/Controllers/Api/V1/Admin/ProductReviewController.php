<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexAdminReviewRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAdminReviewRequest;
use App\Http\Resources\Api\V1\Admin\ProductReviewResource;
use App\Models\Review;
use App\Services\Admin\AdminReviewService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ProductReviewController extends Controller
{
    public function __construct(
        private readonly AdminReviewService $adminReviewService,
    ) {}

    public function stats(): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.admin_review_stats_fetched_successfully'),
            data: $this->adminReviewService->stats(),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function index(IndexAdminReviewRequest $request): JsonResponse
    {
        $reviews = $this->adminReviewService->paginate($request);

        return sendResponse(
            status: true,
            message: __('api.admin_reviews_fetched_successfully'),
            data: ProductReviewResource::collection($reviews),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(Review $review): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.admin_review_fetched_successfully'),
            data: new ProductReviewResource($this->adminReviewService->find($review)),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function update(UpdateAdminReviewRequest $request, Review $review): JsonResponse
    {
        $review = $this->adminReviewService->update($review, $request->validated());

        return sendResponse(
            status: true,
            message: __('api.admin_review_updated_successfully'),
            data: new ProductReviewResource($review),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroy(Review $review): JsonResponse
    {
        $review = $this->adminReviewService->delete($review);

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: new ProductReviewResource($review),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
