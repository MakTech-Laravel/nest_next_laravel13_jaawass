<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Analytics;

use App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns\InteractsWithManufacturerAnalyticsPagination;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns\InteractsWithManufacturerAnalyticsPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManufacturerAnalyticsPerformanceRequest extends FormRequest
{
    use InteractsWithManufacturerAnalyticsPagination;
    use InteractsWithManufacturerAnalyticsPeriod;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->periodRules(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'order_by' => ['sometimes', 'string', Rule::in(['period', 'profile_views', 'inquiries', 'messages', 'quote_requests'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ]);
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'period');
    }
}
