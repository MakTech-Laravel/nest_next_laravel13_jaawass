<?php

namespace App\Filters\Api\V1\Admin;

use Illuminate\Database\Eloquent\Builder;

class ArticleFilter
{
    private function __construct(private Builder $query) {}

    public static function apply(Builder $query, $request): Builder
    {

       
        return (new self($query))
            ->withSearch($request->searchTerm())
            ->withFilterStatus($request->status())
            ->withFilterCategory($request->categoryId())
            ->withFilterFeatured($request->isFeatured())
            ->query;
    }

    public function withSearch(?string $term): self
    {
        if ($term == null) {
            return $this;
        }

        $this->query->where('title', 'like', "%{$term}%")
            ->orWhere('content', 'like', "%{$term}%")
            ->orWhere('excerpt', 'like', "%{$term}%");

        return $this;
    }

    public function withFilterStatus(?string $status): self
    {
        if ($status == null) {
            return $this;
        }

        $this->query->where('status', $status);

        return $this;
    }

    public function withFilterCategory(?int $categoryId): self
    {
        if ($categoryId == null) {
            return $this;
        }

        $this->query->where('article_category_id', $categoryId);

        return $this;
    }

    public function withFilterFeatured(?bool $isFeatured): self
    {
        if ($isFeatured == null) {
            return $this;
        }

        $this->query->where('is_featured', $isFeatured);

        return $this;
    }
}
