<?php

namespace App\Services\Manufacturer;

use App\Enums\OrderStatus;
use App\Filters\Api\V1\Manufacturer\Order\OrderFilter;
use App\Http\Requests\Api\V1\Manufacturer\Order\IndexOrderRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderBuyersRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderProductsRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderStatusUpdate;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManufacturerOrderService
{
    /**
     * @return array<int, string>
     */
    private function orderRelations(): array
    {
        return [
            'buyer.company',
            'manufacturer.company',
            'product.images',
            'product.category',
            'product.subCategory',
            'translations',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function orderDetailRelations(): array
    {
        return [
            ...$this->orderRelations(),
            'statusUpdates' => fn ($query) => $query
                ->with(['user.company', 'attachments', 'translations'])
                ->latest('id'),
        ];
    }

    public function paginate(IndexOrderRequest $request, int $manufacturerId): LengthAwarePaginator
    {
        return OrderFilter::apply(
            Order::query()->with($this->orderRelations()),
            $request,
            $manufacturerId,
        )
            ->latest('id')
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function findForManufacturer(Order $order, int $manufacturerId): Order
    {
        abort_unless((int) $order->manufacturer_id === $manufacturerId, 404);

        return $order->load($this->orderDetailRelations());
    }

    public function create(StoreOrderRequest $request, int $manufacturerId): Order
    {
        $order = Order::query()->create([
            ...$request->orderAttributes($manufacturerId),
            'status' => OrderStatus::OrderCreated->value,
        ]);

        OrderStatusUpdate::query()->create([
            'order_id' => $order->id,
            'user_id' => $manufacturerId,
            'status' => OrderStatus::OrderCreated->value,
            'notes' => null,
        ]);

        $sourceLocale = $request->sourceLocale();
        $sourceData = $request->translationSourceData();

        if ($sourceData !== []) {
            $order->upsertTranslations([
                $sourceLocale => $sourceData,
            ]);

            $order->autoTranslate(
                sourceData: $sourceData,
                sourceLocale: $sourceLocale,
            );
        }

        return $order->load($this->orderDetailRelations());
    }

    public function selectProducts(SelectOrderProductsRequest $request, int $manufacturerId): LengthAwarePaginator
    {
        $query = Product::query()
            ->select(['id', 'name', 'user_id', 'slug'])
            ->where('user_id', $manufacturerId)
            ->with(['user.company']);

        if ($request->searchTerm() !== null) {
            $searchTerm = $request->searchTerm();

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('slug', 'like', "%{$searchTerm}%");
            });
        }

        return $query
            ->latest('id')
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function selectBuyers(SelectOrderBuyersRequest $request, int $manufacturerId): LengthAwarePaginator
    {
        $buyerIds = RfqSubmission::query()
            ->where('product_id', $request->productId())
            ->where('manufacturer_id', $manufacturerId)
            ->distinct()
            ->pluck('buyer_id');

        $query = User::query()
            ->isBuyer()
            ->whereIn('id', $buyerIds)
            ->with('company');

        if ($request->searchTerm() !== null) {
            $searchTerm = $request->searchTerm();

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhereHas('company', function (Builder $companyQuery) use ($searchTerm): void {
                        $companyQuery->where('company_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query
            ->latest('id')
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }
}
