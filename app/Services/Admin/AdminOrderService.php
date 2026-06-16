<?php

namespace App\Services\Admin;

use App\Filters\Api\V1\Order\OrderFilter;
use App\Http\Requests\Api\V1\Admin\Order\IndexOrderRequest;
use App\Models\Order;
use App\Services\Order\OrderStatusUpdateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminOrderService
{
    public function __construct(
        private readonly OrderStatusUpdateService $orderStatusUpdateService,
    ) {}

    public function paginate(IndexOrderRequest $request): LengthAwarePaginator
    {
        return OrderFilter::apply(
            Order::query()->with($this->orderStatusUpdateService->listRelations()),
            buyerId: $request->buyerId(),
            manufacturerId: $request->manufacturerId(),
            productId: $request->productId(),
            status: $request->orderStatus(),
            searchTerm: $request->searchTerm(),
        )
            ->latest('id')
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function find(Order $order): Order
    {
        return $this->orderStatusUpdateService->loadOrderWithRelations($order);
    }
}
