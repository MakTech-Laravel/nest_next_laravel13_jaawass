<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexHelpCenterArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'help_center_category_id' => ['sometimes', 'integer', 'exists:help_center_categories,id'],
            'order_by' => ['sometimes', 'string', Rule::in(['created_at', 'sort_order', 'title', 'views'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
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

    public function categoryId(): ?int
    {
        return $this->filled('help_center_category_id')
            ? $this->integer('help_center_category_id')
            : null;
    }

    public function orderBy(): ?string
    {
        return $this->input('order_by') ?? 'sort_order';
    }

    public function orderDirection(): ?string
    {
        return $this->input('order_direction') ?? 'asc';
    }
}
