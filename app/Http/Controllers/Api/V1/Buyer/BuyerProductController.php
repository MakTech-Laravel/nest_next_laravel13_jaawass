<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\BuyerProductActionRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\Buyer\BuyerProductListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BuyerProductController extends Controller
{
    public function __construct(
        private readonly BuyerProductListService $buyerProductListService,
    ) {}

    public function indexSaved(Request $request): JsonResponse
    {
        $products = $this->buyerProductListService->savedProducts($request->user());

        return sendResponse(
            status: true,
            message: __('api.saved_products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function saveProduct(BuyerProductActionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->buyerProductListService->save(
            $request->user(),
            (int) $validated['product_id']
        );

        return sendResponse(
            status: true,
            message: __('api.product_saved_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function unsaveProduct(Request $request, int $product): JsonResponse
    {
        $this->buyerProductListService->unsave($request->user(), $product);

        return sendResponse(
            status: true,
            message: __('api.product_unsaved_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function indexCompare(Request $request): JsonResponse
    {
        $products = $this->buyerProductListService->compareProducts($request->user());

        return sendResponse(
            status: true,
            message: __('api.compare_products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function addToCompare(BuyerProductActionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->buyerProductListService->addToCompare(
            $request->user(),
            (int) $validated['product_id']
        );

        return sendResponse(
            status: true,
            message: __('api.product_added_to_compare_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function removeFromCompare(Request $request, int $product): JsonResponse
    {
        $this->buyerProductListService->removeFromCompare($request->user(), $product);

        return sendResponse(
            status: true,
            message: __('api.product_removed_from_compare_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
