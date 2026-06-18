<?php

namespace App\Filters\Api\V1;

use App\Http\Requests\Api\V1\PublicSupplierIndexRequest;
use App\Support\Subscription\SupplierVisibilityScoreQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PublicSupplierFilter
{
    private Builder $query;

    private function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function apply(Builder $query, PublicSupplierIndexRequest $request): Builder
    {
        $filter = new self($query);

        return $filter
            ->withSearch($request->searchTerm())
            ->withIndustry($request->industryId(), $request->industrySlug())
            ->withCountry($request->country())
            ->withCertification($request->certification())
            ->withExportMarket($request->exportMarket())
            ->withMoqRange($request->minMoq(), $request->maxMoq())
            ->withReviewedOnly($request->reviewedOnly())
            ->withSort($request->sort())
            ->query;
    }

    private function withSearch(?string $term): self
    {
        if ($term === null) {
            return $this;
        }

        $like = '%'.$term.'%';

        $this->query->where(function (Builder $builder) use ($like): void {
            $builder
                ->whereHas('company', function (Builder $company) use ($like): void {
                    $company
                        ->where('company_name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('country', 'like', $like);
                })
                ->orWhereHas('products', function (Builder $product) use ($like): void {
                    $product
                        ->where('status', 'active')
                        ->where('is_approved', true)
                        ->where('name', 'like', $like);
                });
        });

        return $this;
    }

    private function withIndustry(?int $industryId, ?string $industrySlug): self
    {
        if ($industryId === null && $industrySlug === null) {
            return $this;
        }

        $this->query->where(function (Builder $builder) use ($industryId, $industrySlug): void {
            if ($industryId !== null) {
                $builder->whereHas('company.industries', fn (Builder $industry) => $industry
                    ->where('industries.id', $industryId))
                    ->orWhereHas('products', fn (Builder $product) => $product
                        ->where('status', 'active')
                        ->where('is_approved', true)
                        ->where('industry_id', $industryId));
            }

            if ($industrySlug !== null) {
                $builder->whereHas('company.industries', fn (Builder $industry) => $industry
                    ->where('slug', $industrySlug))
                    ->orWhereHas('products', function (Builder $product) use ($industrySlug): void {
                        $product
                            ->where('status', 'active')
                            ->where('is_approved', true)
                            ->whereHas('category', fn (Builder $category) => $category
                                ->where('slug', $industrySlug));
                    });
            }
        });

        return $this;
    }

    private function withCountry(?string $country): self
    {
        if ($country === null) {
            return $this;
        }

        $normalizedCountry = mb_strtolower(trim($country));
        $like = '%'.$normalizedCountry.'%';

        $this->query->whereHas('company', fn (Builder $company) => $company
            ->whereRaw('LOWER(TRIM(country)) LIKE ?', [$like]));

        return $this;
    }

    private function withCertification(?string $certification): self
    {
        if ($certification === null) {
            return $this;
        }

        $like = '%'.$certification.'%';

        $this->query->where(function (Builder $builder) use ($like): void {
            $builder
                ->whereHas('company', fn (Builder $company) => $company
                    ->where('certifications', 'like', $like))
                ->orWhereHas('certificates', fn (Builder $certificate) => $certificate
                    ->valid()
                    ->where(function (Builder $certQuery) use ($like): void {
                        $certQuery
                            ->where('issuing_body', 'like', $like)
                            ->orWhere('certificate_number', 'like', $like)
                            ->orWhereHas('certificateType', fn (Builder $type) => $type
                                ->where('name', 'like', $like));
                    }));
        });

        return $this;
    }

    private function withExportMarket(?string $exportMarket): self
    {
        if ($exportMarket === null) {
            return $this;
        }

        $this->query->whereHas('company', fn (Builder $company) => $company
            ->where('export_markets', 'like', '%'.$exportMarket.'%'));

        return $this;
    }

    private function withMoqRange(?int $minMoq, ?int $maxMoq): self
    {
        if ($minMoq === null && $maxMoq === null) {
            return $this;
        }

        $this->query->whereHas('products', function (Builder $product) use ($minMoq, $maxMoq): void {
            $product
                ->where('status', 'active')
                ->where('is_approved', true)
                ->whereHas('pricingQuantities', function (Builder $pricing) use ($minMoq, $maxMoq): void {
                    if ($minMoq !== null) {
                        $pricing->where('minimum_order_quantity', '>=', $minMoq);
                    }

                    if ($maxMoq !== null) {
                        $pricing->where('minimum_order_quantity', '<=', $maxMoq);
                    }
                });
        });

        return $this;
    }

    private function withReviewedOnly(bool $reviewedOnly): self
    {
        if ($reviewedOnly) {
            return $this;
        }

        return $this;
    }

    private function withSort(string $sort): self
    {
        match ($sort) {
            'rating' => $this->query
                ->orderByDesc('avg_rating')
                ->orderByDesc('review_count'),
            'products' => $this->query
                ->orderByDesc('public_product_count')
                ->orderBy('users.id'),
            'newest' => $this->query->orderByDesc('users.created_at'),
            default => $this->query
                ->orderByDesc(DB::raw(SupplierVisibilityScoreQuery::selectExpression()))
                ->orderByDesc('users.id'),
        };

        return $this;
    }
}
