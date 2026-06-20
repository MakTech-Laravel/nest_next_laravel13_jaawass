<?php

namespace App\Http\Requests\Api\V1\Admin\Analytics;

use App\Http\Requests\Api\V1\Admin\Analytics\Concerns\InteractsWithAnalyticsPagination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAnalyticsGrowthRequest extends FormRequest
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
            'year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'months' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'order_by' => ['sometimes', 'string', Rule::in(['period', 'users', 'suppliers', 'rfqs'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function year(): ?int
    {
        return $this->filled('year') ? $this->integer('year') : null;
    }

    public function months(): int
    {
        return $this->integer('months', 12);
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'period');
    }
}
