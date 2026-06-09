<?php

namespace App\Http\Requests\Api\V1\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
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
            'is_approved' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function filterStatus(): ?bool
    {
        if (! $this->has('is_approved')) {
            return null;
        }

        return $this->boolean('is_approved');
    }

    public function filterSearch(): ?string
    {
        return $this->input('search');
    }

    public function perPage(): ?int
    {
        return $this->integer('per_page', 10);
    }

    public function pageNumber(): ?int
    {
        return $this->integer('page', 1);
    }
}
