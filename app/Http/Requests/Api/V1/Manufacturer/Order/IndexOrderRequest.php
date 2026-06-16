<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'buyer_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')],
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

    public function buyerId(): ?int
    {
        return $this->filled('buyer_id') ? $this->integer('buyer_id') : null;
    }

    public function productId(): ?int
    {
        return $this->filled('product_id') ? $this->integer('product_id') : null;
    }
}
