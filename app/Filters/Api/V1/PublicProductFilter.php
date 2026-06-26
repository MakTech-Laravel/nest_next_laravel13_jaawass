<?php

namespace App\Filters\Api\V1;

use App\Http\Requests\Api\V1\PublicProductIndexRequest;
use App\Support\ExportMarkets\ManufacturerExportMarketVisibility;
use App\Support\Product\BuyerFacingProductVisibility;
use Illuminate\Database\Eloquent\Builder;

class PublicProductFilter
{
    private Builder $query;

    private function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function apply(Builder $query, PublicProductIndexRequest $request): Builder
    {
        $filter = new self($query);

        return $filter
            ->publicCatalogOnly()
            ->withSearch($request->searchTerm())
            ->withCategory($request->categoryId(), $request->categorySlug())
            ->withSubCategory($request->subCategoryId(), $request->subCategorySlug())
            ->withSupplier($request->supplierId(), $request->supplierSearch())
            ->withLocation($request->country(), $request->city())
            ->withPriceRange($request->minPrice(), $request->maxPrice())
            ->withMoqRange($request->minMoq(), $request->maxMoq())
            ->withCertification($request->certification())
            ->withExportMarket($request->exportMarket())
            ->withViewerCountryVisibility($request->viewerCountryCode())
            ->withSort($request->sort())
            ->query;
    }

    private function publicCatalogOnly(): self
    {
        $this->query
            ->where('products.status', 'active')
            ->where('products.is_approved', true);

        app(BuyerFacingProductVisibility::class)
            ->applyManufacturerSubscriptionConstraint($this->query);

        return $this;
    }

    private function withSearch(?string $term): self
    {
        if ($term === null) {
            return $this;
        }

        $like = '%'.$term.'%';

        $this->query->where(function (Builder $builder) use ($like, $term): void {
            $builder
                ->where('products.name', 'like', $like)
                ->orWhere('products.description', 'like', $like)
                ->orWhere('products.slug', 'like', $like)
                ->orWhere('products.keywords', 'like', $like)
                ->orWhereHas('category', fn (Builder $category) => $category
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like))
                ->orWhereHas('subCategory', fn (Builder $subCategory) => $subCategory
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like))
                ->orWhereHas('user.company', fn (Builder $company) => $company
                    ->where('company_name', 'like', $like)
                    ->orWhere('country', 'like', $like)
                    ->orWhere('city', 'like', $like));
        });

        return $this;
    }

    private function withCategory(?int $categoryId, ?string $categorySlug): self
    {
        if ($categoryId !== null) {
            $this->query->where('products.industry_id', $categoryId);
        }

        if ($categorySlug !== null) {
            $this->query->whereHas('category', fn (Builder $category) => $category
                ->where('slug', $categorySlug));
        }

        return $this;
    }

    private function withSubCategory(?int $subCategoryId, ?string $subCategorySlug): self
    {
        if ($subCategoryId !== null) {
            $this->query->where('products.sub_category_id', $subCategoryId);
        }

        if ($subCategorySlug !== null) {
            $this->query->whereHas('subCategory', fn (Builder $subCategory) => $subCategory
                ->where('slug', $subCategorySlug));
        }

        return $this;
    }

    private function withSupplier(?int $supplierId, ?string $supplierSearch): self
    {
        if ($supplierId !== null) {
            $this->query->where('products.user_id', $supplierId);
        }

        if ($supplierSearch !== null) {
            $like = '%'.$supplierSearch.'%';

            $this->query->whereHas('user.company', fn (Builder $company) => $company
                ->where('company_name', 'like', $like));
        }

        return $this;
    }

    private function withLocation(?string $country, ?string $city): self
    {
        if ($country === null && $city === null) {
            return $this;
        }

        $this->query->whereHas('user.company', function (Builder $company) use ($country, $city): void {
            if ($country !== null) {
                $company->where('country', 'like', '%'.$country.'%');
            }

            if ($city !== null) {
                $company->where('city', 'like', '%'.$city.'%');
            }
        });

        return $this;
    }

    private function withPriceRange(?float $minPrice, ?float $maxPrice): self
    {
        if ($minPrice === null && $maxPrice === null) {
            return $this;
        }

        $this->query->whereHas('pricingQuantities', function (Builder $pricing) use ($minPrice, $maxPrice): void {
            if ($minPrice !== null) {
                $pricing->where('max_price', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $pricing->where('min_price', '<=', $maxPrice);
            }
        });

        return $this;
    }

    private function withMoqRange(?int $minMoq, ?int $maxMoq): self
    {
        if ($minMoq === null && $maxMoq === null) {
            return $this;
        }

        $this->query->whereHas('pricingQuantities', function (Builder $pricing) use ($minMoq, $maxMoq): void {
            if ($minMoq !== null) {
                $pricing->where('minimum_order_quantity', '>=', $minMoq);
            }

            if ($maxMoq !== null) {
                $pricing->where('minimum_order_quantity', '<=', $maxMoq);
            }
        });

        return $this;
    }

    private function withCertification(?string $certification): self
    {
        if ($certification === null) {
            return $this;
        }

        $like = '%'.$certification.'%';

        $this->query->whereHas('user.company', fn (Builder $company) => $company
            ->where('certifications', 'like', $like));

        return $this;
    }

    private function withExportMarket(?string $exportMarket): self
    {
        if ($exportMarket === null) {
            return $this;
        }

        $like = '%'.$exportMarket.'%';

        $this->query->whereHas('user.company', fn (Builder $company) => $company
            ->where('export_markets', 'like', $like));

        return $this;
    }

    private function withViewerCountryVisibility(?string $viewerCountryCode): self
    {
        if ($viewerCountryCode === null) {
            return $this;
        }

        app(ManufacturerExportMarketVisibility::class)
            ->applyToProductQuery($this->query, $viewerCountryCode);

        return $this;
    }

    private function withSort(string $sort): self
    {
        $this->query->select('products.*');

        return match ($sort) {
            'price-low' => $this->sortByPricingColumn('pricing_quanities.min_price', 'asc'),
            'price-high' => $this->sortByPricingColumn('pricing_quanities.max_price', 'desc'),
            'moq-low' => $this->sortByPricingColumn('pricing_quanities.minimum_order_quantity', 'asc'),
            'newest' => $this->sortByColumn('products.created_at', 'desc'),
            'popularity' => $this->sortByColumn('products.inquiry_count', 'desc'),
            default => $this->sortByRelevance(),
        };
    }

    private function sortByRelevance(): self
    {
        $this->query
            ->orderByDesc('products.inquiry_count')
            ->orderByDesc('products.view_count')
            ->orderByDesc('products.created_at');

        return $this;
    }

    private function sortByColumn(string $column, string $direction): self
    {
        $this->query->orderBy($column, $direction);

        return $this;
    }

    private function sortByPricingColumn(string $column, string $direction): self
    {
        $this->query
            ->leftJoin('pricing_quanities', 'pricing_quanities.product_id', '=', 'products.id')
            ->orderBy($column, $direction)
            ->orderByDesc('products.id');

        return $this;
    }
}
