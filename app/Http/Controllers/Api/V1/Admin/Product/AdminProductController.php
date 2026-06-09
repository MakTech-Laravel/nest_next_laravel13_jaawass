<?php

namespace App\Http\Controllers\Api\V1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Product\ProductIndexRequest;
use App\Http\Requests\Api\V1\Admin\Product\UpdateProductApprovalStatusRequest;
use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Models\Product;
use App\Services\Admin\AdminProductService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminProductController extends Controller
{
    public function __construct(private AdminProductService $adminProductService) {}

    public function index(ProductIndexRequest $request)
    {
        $product = $this->adminProductService->paginate($request);

        return sendResponse(
            status: true,
            message: __('api.products_fetched_successfully'),
            data: ProductResource::collection($product),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateApprovalStatus(UpdateProductApprovalStatusRequest $request, Product $product)
    {
        $product = $this->adminProductService->updateApprovalStatus(
            $product,
            $request->boolean('is_approved')
        );

        return sendResponse(
            status: true,
            message: __('api.product_approval_status_updated_successfully'),
            data: new ProductResource($product),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
