<?php

namespace App\Services\Buyer;

use App\Enums\DashboardEventType;
use App\Models\CompareProduct;
use App\Models\Product;
use App\Models\SaveProduct;
use App\Models\User;
use App\Services\Dashboard\EventTrackerService;
use App\Support\Product\BuyerFacingProductVisibility;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BuyerProductListService
{
    public function __construct(
        private readonly EventTrackerService $eventTracker,
        private readonly BuyerFacingProductVisibility $buyerFacingProductVisibility,
    ) {}

    public function save(User $buyer, int $productId): SaveProduct
    {
        $product = $this->resolveProductForBuyer($buyer, $productId);

        $saved = SaveProduct::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $this->eventTracker->track(
            eventType: DashboardEventType::ProductSaved,
            actor: $buyer,
            entityType: 'product',
            entityId: (int) $product->id,
            counterparty: $product->user,
            metadata: ['saved_id' => (int) $saved->id],
            occurredAt: $saved->created_at,
        );

        return $saved;
    }

    public function unsave(User $buyer, int $productId): void
    {
        $deleted = SaveProduct::query()
            ->where('user_id', $buyer->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.saved_product_not_found')],
            ]);
        }

        $this->eventTracker->track(
            eventType: DashboardEventType::ProductUnsaved,
            actor: $buyer,
            entityType: 'product',
            entityId: $productId,
        );
    }

    public function addToCompare(User $buyer, int $productId): CompareProduct
    {
        $product = $this->resolveProductForBuyer($buyer, $productId);

        $compared = CompareProduct::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $this->eventTracker->track(
            eventType: DashboardEventType::ProductCompared,
            actor: $buyer,
            entityType: 'product',
            entityId: (int) $product->id,
            counterparty: $product->user,
            metadata: ['compare_id' => (int) $compared->id],
            occurredAt: $compared->created_at,
        );

        return $compared;
    }

    public function removeFromCompare(User $buyer, int $productId): void
    {
        $deleted = CompareProduct::query()
            ->where('user_id', $buyer->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.compare_product_not_found')],
            ]);
        }

        $this->eventTracker->track(
            eventType: DashboardEventType::ProductCompareRemoved,
            actor: $buyer,
            entityType: 'product',
            entityId: $productId,
        );
    }

    /**
     * @return Collection<int, Product>
     */
    public function savedProducts(User $buyer): Collection
    {
        $query = $buyer
            ->savedProducts()
            ->with($this->productRelations());

        $this->buyerFacingProductVisibility
            ->applyManufacturerSubscriptionConstraint($query);

        return $query
            ->latest('save_products.created_at')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function compareProducts(User $buyer): Collection
    {
        $query = $buyer
            ->compareProducts()
            ->with($this->productRelations());

        $this->buyerFacingProductVisibility
            ->applyManufacturerSubscriptionConstraint($query);

        return $query
            ->latest('compare_products.created_at')
            ->get();
    }

    private function resolveProductForBuyer(User $buyer, int $productId): Product
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.product_not_found')],
            ]);
        }

        if ((int) $product->user_id === (int) $buyer->id) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.buyer_own_product_not_allowed')],
            ]);
        }

        if (! $this->buyerFacingProductVisibility->productHasManufacturerWithActiveSubscription($product)) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.product_not_found')],
            ]);
        }

        return $product;
    }

    /**
     * @return array<int, string>
     */
    private function productRelations(): array
    {
        return [
            'translations',
            'currency',
            'category',
            'subCategory',
            'images',
        ];
    }
}
