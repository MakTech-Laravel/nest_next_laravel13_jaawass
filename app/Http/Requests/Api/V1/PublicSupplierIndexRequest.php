<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicSupplierIndexRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in([
                'relevance',
                'rating',
                'products',
                'newest',
            ])],
            'industry_id' => ['sometimes', 'nullable', 'integer', 'exists:industries,id'],
            'industry_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:industries,id'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'certification' => ['sometimes', 'nullable', 'string', 'max:100'],
            'export_market' => ['sometimes', 'nullable', 'string', 'max:100'],
            'moq_range' => ['sometimes', 'nullable', 'string', Rule::in([
                '1-100',
                '100-500',
                '500-1000',
                '1000-5000',
                '5000+',
            ])],
            'reviewed_only' => ['sometimes', 'boolean'],
            'ids' => ['sometimes', 'nullable', 'string', 'max:50'],
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

    public function industryId(): ?int
    {
        if ($this->filled('industry_id')) {
            return $this->integer('industry_id');
        }

        if ($this->filled('category_id')) {
            return $this->integer('category_id');
        }

        return null;
    }

    public function industrySlug(): ?string
    {
        foreach (['industry_slug', 'category_slug'] as $key) {
            $value = $this->input($key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function country(): ?string
    {
        $country = $this->input('country');

        return is_string($country) && $country !== '' ? $country : null;
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

    public function minMoq(): ?int
    {
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
        return match ($this->input('moq_range')) {
            '1-100' => 100,
            '100-500' => 500,
            '500-1000' => 1000,
            '1000-5000' => 5000,
            default => null,
        };
    }

    public function reviewedOnly(): bool
    {
        if (! $this->has('reviewed_only')) {
            return true;
        }

        return $this->boolean('reviewed_only');
    }

    /**
     * @return array<int, int>
     */
    public function supplierIds(): array
    {
        $ids = $this->input('ids');

        if (! is_string($ids) || $ids === '') {
            return [];
        }

        return array_slice(
            array_values(array_filter(array_map('intval', explode(',', $ids)))),
            0,
            4
        );
    }
}
