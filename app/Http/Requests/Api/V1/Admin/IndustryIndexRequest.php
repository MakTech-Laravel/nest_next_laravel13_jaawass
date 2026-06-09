<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndustryIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'order_by' => ['sometimes', 'nullable', 'string', 'in:name,sort_order,created_at,updated_at'],
            'order_direction' => ['sometimes', 'nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 8);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    public function searchTerm(): ?string
    {
        return $this->input('search');
    }

    public function orderBy(): ?string
    {
        return $this->input('order_by');
    }

    public function orderDirection(): ?string
    {
        return $this->input('order_direction');
    }
}
