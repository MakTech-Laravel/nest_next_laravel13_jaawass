<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait InteractsWithOrderIndexFilters
{
    /**
     * @return array<string, mixed>
     */
    protected function orderIndexFilterRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')],
            'status' => ['sometimes', 'nullable', 'string', new Enum(OrderStatus::class)],
        ];
    }

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
        $value = $this->input('search');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function productId(): ?int
    {
        return $this->filled('product_id') ? $this->integer('product_id') : null;
    }

    public function orderStatus(): ?string
    {
        $value = $this->input('status');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
