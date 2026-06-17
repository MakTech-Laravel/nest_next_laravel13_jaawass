<?php

namespace App\Services\Supplier;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Filters\Api\V1\PublicSupplierFilter;
use App\Http\Requests\Api\V1\PublicSupplierIndexRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PublicSupplierCatalogService
{
    /**
     * @return array<int, string|\Closure>
     */
    public function eagerRelationsForList(): array
    {
        return [
            'company.translations',
            'company.industries',
            'subscription.plan.planFeatures.feature',
            'products' => fn ($query) => $query
                ->where('status', 'active')
                ->where('is_approved', true)
                ->orderByDesc('inquiry_count')
                ->limit(5),
        ];
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function eagerRelationsForDetail(): array
    {
        return [
            'company.translations',
            'company.industries',
            'factoryImages',
            'subscription.plan.planFeatures.feature',
            'products' => fn ($query) => $query
                ->where('status', 'active')
                ->where('is_approved', true)
                ->orderByDesc('inquiry_count')
                ->limit(5),
        ];
    }

    public function publicSupplierBaseQuery(): Builder
    {
        return User::query()
            ->isManufacturer()
            ->where('status', UserStatus::ACTIVE->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->whereHas('company', fn (Builder $company) => $company
                ->whereNotNull('company_name')
                ->where('company_name', '!=', ''));
    }

    public function resolvePublicSupplier(string $identifier): User
    {
        $query = $this->publicSupplierBaseQuery()
            ->with($this->eagerRelationsForDetail());

        $supplier = ctype_digit($identifier)
            ? $query->where('users.id', (int) $identifier)->first()
            : $query->whereHas('company', fn (Builder $company) => $company
                ->where('slug', $identifier))->first();

        if ($supplier === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$identifier]);
        }

        return $supplier;
    }

    public function paginatePublicSuppliers(PublicSupplierIndexRequest $request): LengthAwarePaginator
    {
        $query = $this->publicSupplierBaseQuery()
            ->with($this->eagerRelationsForList())
            ->withCount('manufacturerReviews as review_count')
            ->withAvg('manufacturerReviews as avg_rating', 'rating')
            ->withCount([
                'products as public_product_count' => fn (Builder $product) => $product
                    ->where('status', 'active')
                    ->where('is_approved', true),
            ]);

        $query = PublicSupplierFilter::apply($query, $request);

        return $query->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function getPublicSuppliersByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return $this->publicSupplierBaseQuery()
            ->with($this->eagerRelationsForList())
            ->withCount('manufacturerReviews as review_count')
            ->withAvg('manufacturerReviews as avg_rating', 'rating')
            ->withCount([
                'products as public_product_count' => fn (Builder $product) => $product
                    ->where('status', 'active')
                    ->where('is_approved', true),
            ])
            ->whereIn('users.id', $ids)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewStatsForSupplier(User $supplier): array
    {
        $reviews = Review::query()
            ->where('user_id', $supplier->id)
            ->get(['rating']);

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0
            ? round((float) $reviews->avg('rating'), 1)
            : 0.0;

        $breakdown = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = $reviews->where('rating', $star)->count();
            $breakdown[] = [
                'rating' => $star,
                'count' => $count,
                'percentage' => $totalReviews > 0 ? (int) round(($count / $totalReviews) * 100) : 0,
            ];
        }

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'breakdown' => $breakdown,
        ];
    }
}
