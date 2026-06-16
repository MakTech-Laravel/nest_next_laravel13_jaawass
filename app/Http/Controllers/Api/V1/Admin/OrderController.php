<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Manufacturer\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderStatusUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStatusUpdateService $orderStatusUpdateService,
    ) {}

    public function show(Request $request, Order $order): JsonResponse
    {
        $order = $this->orderStatusUpdateService->loadOrderWithRelations($order);

        return sendResponse(
            status: true,
            message: __('api.admin_order_fetched_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
