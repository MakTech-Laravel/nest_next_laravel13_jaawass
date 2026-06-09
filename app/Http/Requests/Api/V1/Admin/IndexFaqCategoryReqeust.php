<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFaqCategoryReqeust extends FormRequest
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
            'order_by' => ['sometimes', 'string', Rule::in(['created_at', 'sort', 'name'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }


    public function messages(): array
    {
        return [
            'per_page.integer' => 'The per page must be an integer.',
            'per_page.min' => 'The per page must be at least 1.',
            'per_page.max' => 'The per page may not be greater than 100.',
            'page.integer' => 'The page must be an integer.',
            'page.min' => 'The page must be at least 1.',
            'search.string' => 'The search must be a string.',
            'search.max' => 'The search may not be greater than 100 characters.',
            'order_by.string' => 'The order by must be a string.',
            'order_by.in' => 'The order by must be one of: created_at, sort, name.',
            'order_direction.string' => 'The order direction must be a string.',
            'order_direction.in' => 'The order direction must be one of: asc, desc.',
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
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
        return $this->input('order_by') ?? 'sort';
    }
    
    public function orderDirection(): ?string
    {
        return $this->input('order_direction') ?? 'asc';
    }
}
