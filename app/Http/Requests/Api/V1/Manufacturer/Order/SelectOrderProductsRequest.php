<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

use Illuminate\Foundation\Http\FormRequest;

class SelectOrderProductsRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function searchTerm(): ?string
    {
        $value = $this->input('search');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }
}
