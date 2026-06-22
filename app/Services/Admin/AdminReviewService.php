<?php

namespace App\Services\Admin;

use App\Enums\ReviewStatus;
use App\Http\Requests\Api\V1\Admin\IndexAdminReviewRequest;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminReviewService
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $counts = Review::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total_reviews' => (int) Review::query()->count(),
            'published' => (int) ($counts[ReviewStatus::PUBLISHED->value] ?? 0),
            'pending_review' => (int) ($counts[ReviewStatus::PENDING->value] ?? 0),
            'flagged' => (int) ($counts[ReviewStatus::FLAGGED->value] ?? 0),
            'hidden' => (int) ($counts[ReviewStatus::HIDDEN->value] ?? 0),
        ];
    }

    public function paginate(IndexAdminReviewRequest $request): LengthAwarePaginator
    {
        return $this->listQuery($request)
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function find(Review $review): Review
    {
        return $review->load($this->detailRelations());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Review $review, array $attributes): Review
    {
        $review->update($attributes);

        return $review->fresh($this->detailRelations());
    }

    public function delete(Review $review): Review
    {
        $review->load($this->detailRelations());
        $review->delete();

        return $review;
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function listRelations(): array
    {
        return [
            'reviewer.company',
            'user.company',
            'product.category',
            'order',
        ];
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function detailRelations(): array
    {
        return $this->listRelations();
    }

    private function listQuery(IndexAdminReviewRequest $request): Builder
    {
        $query = Review::query()
            ->with($this->listRelations())
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        if ($request->filled('search')) {
            $searchTerm = trim($request->string('search')->toString());

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('comment', 'like', "%{$searchTerm}%")
                    ->orWhereHas('reviewer', function (Builder $reviewer) use ($searchTerm): void {
                        $reviewer
                            ->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhereHas('company', fn (Builder $company) => $company
                                ->where('company_name', 'like', "%{$searchTerm}%"));
                    })
                    ->orWhereHas('user.company', fn (Builder $company) => $company
                        ->where('company_name', 'like', "%{$searchTerm}%"));
            });
        }

        return $query;
    }
}
