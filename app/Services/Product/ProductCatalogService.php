<?php

namespace App\Services\Product;

use App\Filters\Api\V1\PublicProductFilter;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProductController;
use App\Http\Requests\Api\V1\PublicProductIndexRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductCatalogService
{
    /**
     * Same eager-load shape as {@see ManufacturerProductController::index()}
     * so public {@see ProductResource} can mirror
     * {@see \App\Http\Resources\Api\V1\Product\ProductResource} without N+1 queries.
     *
     * @return array<int, string|\Closure>
     */
    public function eagerRelationsForPublicProduct(bool $withReviews = false): array
    {
        $relations = [
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
            'user',
            'user.company',
        ];

        if ($withReviews) {
            $relations['reviews'] = fn ($query) => $query
                ->publiclyVisible()
                ->with(['reviewer.company', 'order', 'translations'])
                ->latest('id');
        }

        return $relations;
    }

    public function paginatePublicProducts(PublicProductIndexRequest $request): LengthAwarePaginator
    {
        $query = Product::query()
            ->with($this->eagerRelationsForPublicProduct(withReviews: true));

        $query = PublicProductFilter::apply($query, $request);

        return $query->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );
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
            ->with($this->eagerRelationsForPublicProduct(withReviews: true))
            ->where('status', 'active')
            ->where('is_approved', true)
            ->where('industry_id', $categoryId)
            ->orderBy('id')
            ->get();
    }

    public function getPublicProductsBySubCategory(int $subCategoryId): Collection
    {
        return Product::query()
            ->with($this->eagerRelationsForPublicProduct(withReviews: true))
            ->where('status', 'active')
            ->where('is_approved', true)
            ->where('sub_category_id', $subCategoryId)
            ->orderBy('id')
            ->get();
    }
}
