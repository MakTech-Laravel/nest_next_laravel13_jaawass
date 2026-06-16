<?php

namespace App\Services\Buyer;

use App\Filters\Api\V1\Order\OrderFilter;
use App\Http\Requests\Api\V1\Buyer\Order\IndexOrderRequest;
use App\Models\Order;
use App\Services\Order\OrderStatusUpdateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuyerOrderService
{
    public function __construct(
        private readonly OrderStatusUpdateService $orderStatusUpdateService,
    ) {}

    public function paginate(IndexOrderRequest $request, int $buyerId): LengthAwarePaginator
    {
        return OrderFilter::apply(
            Order::query()->with($this->orderStatusUpdateService->listRelations()),
            buyerId: $buyerId,
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

    public function findForBuyer(Order $order, int $buyerId): Order
    {
        abort_unless((int) $order->buyer_id === $buyerId, 404);

        return $this->orderStatusUpdateService->loadOrderWithRelations($order);
    }
}
