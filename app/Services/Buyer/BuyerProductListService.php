<?php

namespace App\Services\Buyer;

use App\Models\CompareProduct;
use App\Models\Product;
use App\Models\SaveProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BuyerProductListService
{
    public function save(User $buyer, int $productId): SaveProduct
    {
        $product = $this->resolveProductForBuyer($buyer, $productId);

        return SaveProduct::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
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
    }

    public function addToCompare(User $buyer, int $productId): CompareProduct
    {
        $product = $this->resolveProductForBuyer($buyer, $productId);

        return CompareProduct::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
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
    }

    /**
     * @return Collection<int, Product>
     */
    public function savedProducts(User $buyer): Collection
    {
        return $buyer
            ->savedProducts()
            ->with($this->productRelations())
            ->latest('save_products.created_at')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function compareProducts(User $buyer): Collection
    {
        return $buyer
            ->compareProducts()
            ->with($this->productRelations())
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
