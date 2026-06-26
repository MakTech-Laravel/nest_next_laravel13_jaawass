<?php

namespace App\Services\Manufacturer;

use App\Enums\OrderStatus;
use App\Filters\Api\V1\Order\OrderFilter;
use App\Http\Requests\Api\V1\Manufacturer\Order\IndexOrderRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderBuyersRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\SelectOrderProductsRequest;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\OrderItem;
use App\Models\OrderStatusUpdate;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Services\Order\OrderNotificationService;
use App\Services\Order\OrderStatusUpdateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManufacturerOrderService
{
    public function __construct(
        private readonly OrderStatusUpdateService $orderStatusUpdateService,
        private readonly OrderNotificationService $orderNotificationService,
    ) {}

    /**
     * @return array<int|string, mixed>
     */
    private function orderDetailRelations(): array
    {
        return [
            ...$this->orderStatusUpdateService->listRelations(),
            'statusUpdates' => fn ($query) => $query
                ->with(['user.company', 'attachments', 'translations'])
                ->latest('id'),
        ];
    }

    public function paginate(IndexOrderRequest $request, int $manufacturerId): LengthAwarePaginator
    {
        return OrderFilter::apply(
            Order::query()->with($this->orderStatusUpdateService->listRelations()),
            manufacturerId: $manufacturerId,
            buyerId: $request->buyerId(),
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

    public function findForManufacturer(Order $order, int $manufacturerId): Order
    {
        abort_unless((int) $order->manufacturer_id === $manufacturerId, 404);

        return $order->load($this->orderDetailRelations());
    }

    public function create(StoreOrderRequest $request, int $manufacturerId): Order
    {
        return DB::transaction(function () use ($request, $manufacturerId): Order {
            $order = Order::query()->create([
                ...$request->orderAttributes($manufacturerId),
                'status' => OrderStatus::OrderCreated->value,
            ]);

            foreach ($request->normalizedItems() as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'quantity_unit' => $item['quantity_unit'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'notes' => $item['notes'],
                ]);
            }

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

            $this->storeAttachments($order, $request->file('attachments', []));

            $order = $order->load($this->orderDetailRelations());

            $this->orderNotificationService->sendOrderCreated($order);

            return $order;
        });
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
        $buyerIds = $this->buyerIdsWithRfqsForAllProducts(
            $request->productIds(),
            $manufacturerId,
        );

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

    /**
     * @param  array<int, int>  $productIds
     * @return Collection<int, int>
     */
    private function buyerIdsWithRfqsForAllProducts(array $productIds, int $manufacturerId): Collection
    {
        $buyerIds = null;

        foreach ($productIds as $productId) {
            $idsForProduct = RfqSubmission::query()
                ->where('product_id', $productId)
                ->where('manufacturer_id', $manufacturerId)
                ->distinct()
                ->pluck('buyer_id');

            $buyerIds = $buyerIds === null
                ? $idsForProduct
                : $buyerIds->intersect($idsForProduct);
        }

        return $buyerIds ?? collect();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(Order $order, array $files): void
    {
        if ($files === []) {
            return;
        }

        $disk = 'public';

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'orders/'.$order->id.'/documents',
                ['disk' => $disk],
            );

            OrderAttachment::query()->create([
                'order_id' => $order->id,
                'disk' => $disk,
                'file_path' => $path,
                'file_mime' => (string) $file->getClientMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize() ?? Storage::disk($disk)->size($path),
            ]);
        }
    }
}
