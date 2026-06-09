<?php

namespace App\Filters\Api\V1\Admin;

use Illuminate\Database\Eloquent\Builder;

class HelpCenterArticleFilter
{
    private function __construct(private Builder $query) {}

    public static function apply(Builder $query, $request): Builder
    {
        return (new self($query))
            ->withCategory($request->categoryId())
            ->withSearch($request->searchTerm())
            ->withOrder($request->orderBy(), $request->orderDirection())
            ->query;
    }

    private function withCategory(?int $categoryId): self
    {
        if ($categoryId !== null) {
            $this->query->where('help_center_category_id', $categoryId);
        }

        return $this;
    }

    private function withSearch(?string $term): self
    {
        if ($term === null) {
            return $this;
        }

        $this->query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%')
                ->orWhereHas('steps', function (Builder $stepQuery) use ($term) {
                    $stepQuery->where('content', 'like', '%'.$term.'%');
                });
        });

        return $this;
    }

    private function withOrder(?string $column, ?string $direction): self
    {
        if ($column === null) {
            return $this;
        }

        if ($column === 'sort_order') {
            $this->query->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END, sort_order ASC');
        } else {
            $this->query->orderBy($column, $direction ?? 'asc');
        }

        return $this;
    }
}
