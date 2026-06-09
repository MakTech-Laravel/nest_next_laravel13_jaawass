<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\BuyerSupplierActionRequest;
use App\Http\Resources\Api\V1\Buyer\SupplierResource;
use App\Services\Buyer\BuyerSupplierListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BuyerSupplierController extends Controller
{
    public function __construct(
        private readonly BuyerSupplierListService $buyerSupplierListService,
    ) {}

    public function indexSaved(Request $request): JsonResponse
    {
        $suppliers = $this->buyerSupplierListService->savedSuppliers($request->user());

        return sendResponse(
            status: true,
            message: __('api.saved_suppliers_fetched_successfully'),
            data: SupplierResource::collection($suppliers),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function saveSupplier(BuyerSupplierActionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->buyerSupplierListService->save(
            $request->user(),
            (int) $validated['supplier_id']
        );

        return sendResponse(
            status: true,
            message: __('api.supplier_saved_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function unsaveSupplier(Request $request, int $supplier): JsonResponse
    {
        $this->buyerSupplierListService->unsave($request->user(), $supplier);

        return sendResponse(
            status: true,
            message: __('api.supplier_unsaved_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function indexCompare(Request $request): JsonResponse
    {
        $suppliers = $this->buyerSupplierListService->compareSuppliers($request->user());

        return sendResponse(
            status: true,
            message: __('api.compare_suppliers_fetched_successfully'),
            data: SupplierResource::collection($suppliers),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function addToCompare(BuyerSupplierActionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->buyerSupplierListService->addToCompare(
            $request->user(),
            (int) $validated['supplier_id']
        );

        return sendResponse(
            status: true,
            message: __('api.supplier_added_to_compare_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function removeFromCompare(Request $request, int $supplier): JsonResponse
    {
        $this->buyerSupplierListService->removeFromCompare($request->user(), $supplier);

        return sendResponse(
            status: true,
            message: __('api.supplier_removed_from_compare_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
