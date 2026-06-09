<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexArticlesRequest extends FormRequest
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
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'is_featured' => ['sometimes', 'nullable', 'boolean'],
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

    public function status(): ?string
    {
        return $this->input('status');
    }

    public function categoryId(): ?int
    {
        return $this->integer('category_id');
    }

    public function isFeatured(): ?bool
    {
        return $this->boolean('is_featured');
    }
}
