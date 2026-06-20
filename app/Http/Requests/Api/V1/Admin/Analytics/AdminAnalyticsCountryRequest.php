<?php

namespace App\Http\Requests\Api\V1\Admin\Analytics;

use App\Http\Requests\Api\V1\Admin\Analytics\Concerns\InteractsWithAnalyticsPagination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAnalyticsCountryRequest extends FormRequest
{
    use InteractsWithAnalyticsPagination;

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
            'role' => ['sometimes', 'nullable', 'string', Rule::in(['buyer', 'manufacturer', 'all'])],
            'order_by' => ['sometimes', 'string', Rule::in(['country', 'users', 'percentage'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function roleFilter(): string
    {
        return $this->input('role', 'all');
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'users');
    }
}
