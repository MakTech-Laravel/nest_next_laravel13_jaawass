<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicProductIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in([
                'relevance',
                'price-low',
                'price-high',
                'moq-low',
                'newest',
                'popularity',
            ])],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:industries,id'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'industry_id' => ['sometimes', 'nullable', 'integer', 'exists:industries,id'],
            'industry_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sub_category_id' => ['sometimes', 'nullable', 'integer', 'exists:sub_categories,id'],
            'sub_category_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'supplier_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'supplier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_moq' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_moq' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'moq_range' => ['sometimes', 'nullable', 'string', Rule::in([
                '1-100',
                '100-500',
                '500-1000',
                '1000-5000',
                '5000+',
            ])],
            'certification' => ['sometimes', 'nullable', 'string', 'max:100'],
            'export_market' => ['sometimes', 'nullable', 'string', 'max:100'],
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
        $search = $this->input('search');

        return is_string($search) && $search !== '' ? $search : null;
    }

    public function sort(): string
    {
        return $this->input('sort', 'relevance');
    }

    public function categoryId(): ?int
    {
        return $this->filled('category_id')
            ? $this->integer('category_id')
            : ($this->filled('industry_id') ? $this->integer('industry_id') : null);
    }

    public function categorySlug(): ?string
    {
        foreach (['category_slug', 'category', 'industry_slug', 'industry'] as $key) {
            $value = $this->input($key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function subCategoryId(): ?int
    {
        return $this->filled('sub_category_id') ? $this->integer('sub_category_id') : null;
    }

    public function subCategorySlug(): ?string
    {
        $slug = $this->input('sub_category_slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function supplierId(): ?int
    {
        return $this->filled('supplier_id') ? $this->integer('supplier_id') : null;
    }

    public function supplierSearch(): ?string
    {
        $supplier = $this->input('supplier');

        return is_string($supplier) && $supplier !== '' ? $supplier : null;
    }

    public function country(): ?string
    {
        $country = $this->input('country');

        return is_string($country) && $country !== '' ? $country : null;
    }

    public function city(): ?string
    {
        $city = $this->input('city');

        return is_string($city) && $city !== '' ? $city : null;
    }

    public function minPrice(): ?float
    {
        return $this->filled('min_price') ? (float) $this->input('min_price') : null;
    }

    public function maxPrice(): ?float
    {
        return $this->filled('max_price') ? (float) $this->input('max_price') : null;
    }

    public function minMoq(): ?int
    {
        if ($this->filled('min_moq')) {
            return $this->integer('min_moq');
        }

        return match ($this->input('moq_range')) {
            '1-100' => 1,
            '100-500' => 100,
            '500-1000' => 500,
            '1000-5000' => 1000,
            '5000+' => 5000,
            default => null,
        };
    }

    public function maxMoq(): ?int
    {
        if ($this->filled('max_moq')) {
            return $this->integer('max_moq');
        }

        return match ($this->input('moq_range')) {
            '1-100' => 100,
            '100-500' => 500,
            '500-1000' => 1000,
            '1000-5000' => 5000,
            default => null,
        };
    }

    public function certification(): ?string
    {
        $certification = $this->input('certification');

        return is_string($certification) && $certification !== '' ? $certification : null;
    }

    public function exportMarket(): ?string
    {
        $exportMarket = $this->input('export_market');

        return is_string($exportMarket) && $exportMarket !== '' ? $exportMarket : null;
    }
}
