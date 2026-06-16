<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\IndexOrderRequest;
use App\Http\Resources\Api\V1\Manufacturer\OrderResource;
use App\Models\Order;
use App\Services\Admin\AdminOrderService;
use App\Services\Order\OrderStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
        private readonly OrderStatsService $orderStatsService,
    ) {}

    public function stats(): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.admin_order_stats_fetched_successfully'),
            data: $this->orderStatsService->forAdmin(),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = $this->adminOrderService->paginate($request);

        return sendResponse(
            status: true,
            message: __('api.admin_orders_fetched_successfully'),
            data: OrderResource::collection($orders),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $order = $this->adminOrderService->find($order);

        return sendResponse(
            status: true,
            message: __('api.admin_order_fetched_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
