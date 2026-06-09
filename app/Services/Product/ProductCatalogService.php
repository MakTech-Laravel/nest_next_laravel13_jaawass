<?php

namespace App\Services\Product;

use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProductController;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductCatalogService
{
    /**
     * Same eager-load shape as {@see ManufacturerProductController::index()}
     * so public {@see ProductResource} can mirror
     * {@see \App\Http\Resources\Api\V1\Product\ProductResource} without N+1 queries.
     *
     * @return array<int, string>
     */
    public function eagerRelationsForPublicProduct(): array
    {
        return [
            'translations',
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

    public function getPublicProducts(): Collection
    {
        return Product::query()
            ->with($this->eagerRelationsForPublicProduct())
            ->orderBy('id')
            ->get();
    }

    public function getPublicProductsByCategory(int $categoryId): Collection
    {
        return Product::query()
            ->with($this->eagerRelationsForPublicProduct())
            ->where('industry_id', $categoryId)
            ->orderBy('id')
            ->get();
    }

    public function getPublicProductsBySubCategory(int $subCategoryId): Collection
    {
        return Product::query()
            ->with($this->eagerRelationsForPublicProduct())
            ->where('sub_category_id', $subCategoryId)
            ->orderBy('id')
            ->get();
    }
}
