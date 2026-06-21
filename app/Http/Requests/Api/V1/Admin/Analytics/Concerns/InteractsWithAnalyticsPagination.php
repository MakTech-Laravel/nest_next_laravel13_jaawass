<?php

namespace App\Http\Requests\Api\V1\Admin\Analytics\Concerns;

trait InteractsWithAnalyticsPagination
{
    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    public function searchTerm(): ?string
    {
        $search = $this->input('search');

        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }

    public function orderDirection(): string
    {
        return $this->input('order_direction', 'desc') === 'asc' ? 'asc' : 'desc';
    }
}
