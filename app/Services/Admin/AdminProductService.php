<?php

namespace App\Services\Admin;

use App\Filters\Api\V1\Admin\Product\ProductFilter;
use App\Http\Requests\Api\V1\Admin\Product\ProductIndexRequest;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminProductService
{
    /**
     * @return array<int, string>
     */
    private function productRelations(): array
    {
        return [
            'user',
            'currency',
            'category',
            'subCategory',
            'images',
            'pricingQuantities.currency',
            'specifications',
            'productKeyFeatures',
            'customizationOptions',
            'shippingPackaging',
            'availableOptions',
            'shippingMethods',
        ];
    }

    public function paginate(ProductIndexRequest $request): LengthAwarePaginator
    {
        return ProductFilter::apply(
            Product::query()->with($this->productRelations()),
            $request
        )
            ->latest('id')
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function updateApprovalStatus(Product $product, bool $isApproved): Product
    {
        $product->update([
            'is_approved' => $isApproved,
        ]);

        return $product->load($this->productRelations());
    }
}
