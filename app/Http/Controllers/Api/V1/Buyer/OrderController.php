<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\Order\IndexOrderRequest;
use App\Http\Resources\Api\V1\Manufacturer\OrderResource;
use App\Models\Order;
use App\Services\Buyer\BuyerOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class OrderController extends Controller
{
    public function __construct(
        private readonly BuyerOrderService $buyerOrderService,
    ) {}

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = $this->buyerOrderService->paginate(
            $request,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.buyer_orders_fetched_successfully'),
            data: OrderResource::collection($orders),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $order = $this->buyerOrderService->findForBuyer(
            $order,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.buyer_order_fetched_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
