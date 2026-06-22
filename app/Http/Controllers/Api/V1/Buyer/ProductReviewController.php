<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\StoreProductReviewRequest;
use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ProductReviewController extends Controller
{
    public function store(StoreProductReviewRequest $request, Product $product): JsonResponse
    {
        $attributes = $request->reviewAttributes($product);

        $review = Review::query()
            ->create($attributes)
            ->load(['reviewer.company', 'order', 'translations']);

        $sourceLocale = $request->input('locale') ?? app()->getLocale();
        $sourceData = [
            'title' => $attributes['title'],
            'comment' => $attributes['comment'],
        ];

        $review->syncTranslations($sourceData, $sourceLocale);
        $review->load('translations');

        return sendResponse(
            status: true,
            message: __('api.review_created_successfully'),
            data: new ProductReviewResource($review),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }
}
