<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\Order\IndexOrderRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderBuyersRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderProductsRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderStatusUpdateRequest;
use App\Http\Resources\Api\V1\Admin\OrderBuyerSelectResource;
use App\Http\Resources\Api\V1\Admin\OrderProductSelectResource;
use App\Http\Resources\Api\V1\Manufacturer\OrderResource;
use App\Models\Order;
use App\Services\Manufacturer\ManufacturerOrderService;
use App\Services\Order\OrderStatusUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class OrderController extends Controller
{
    public function __construct(
        private readonly ManufacturerOrderService $manufacturerOrderService,
        private readonly OrderStatusUpdateService $orderStatusUpdateService,
    ) {}

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = $this->manufacturerOrderService->paginate(
            $request,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_orders_fetched_successfully'),
            data: OrderResource::collection($orders),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->manufacturerOrderService->create(
            $request,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_order_created_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $order = $this->manufacturerOrderService->findForManufacturer(
            $order,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_order_fetched_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function selectProducts(SelectOrderProductsRequest $request): JsonResponse
    {
        $products = $this->manufacturerOrderService->selectProducts(
            $request,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_order_products_select_fetched_successfully'),
            data: OrderProductSelectResource::collection($products),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function selectBuyers(SelectOrderBuyersRequest $request): JsonResponse
    {
        $buyers = $this->manufacturerOrderService->selectBuyers(
            $request,
            (int) $request->user()->id,
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_order_buyers_select_fetched_successfully'),
            data: OrderBuyerSelectResource::collection($buyers),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function statusOptions(): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.order_status_options_fetched_successfully'),
            data: OrderStatus::options(),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function storeStatusUpdate(StoreOrderStatusUpdateRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderStatusUpdateService->create(
            $order,
            $request->user(),
            $request,
        );

        return sendResponse(
            status: true,
            message: __('api.order_status_update_created_successfully'),
            data: new OrderResource($order),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }
}
