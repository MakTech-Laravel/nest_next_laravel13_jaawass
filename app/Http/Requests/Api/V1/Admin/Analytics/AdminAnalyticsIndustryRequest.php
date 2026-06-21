<?php

namespace App\Http\Requests\Api\V1\Admin\Analytics;

use App\Http\Requests\Api\V1\Admin\Analytics\Concerns\InteractsWithAnalyticsPagination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAnalyticsIndustryRequest extends FormRequest
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
            'order_by' => ['sometimes', 'string', Rule::in(['industry', 'suppliers', 'products'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'suppliers');
    }
}
