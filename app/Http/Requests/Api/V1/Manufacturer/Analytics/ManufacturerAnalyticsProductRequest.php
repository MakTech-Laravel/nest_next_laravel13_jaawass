<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Analytics;

use App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns\InteractsWithManufacturerAnalyticsPagination;
use App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns\InteractsWithManufacturerAnalyticsPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManufacturerAnalyticsProductRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'order_by' => ['sometimes', 'string', Rule::in(['name', 'views', 'inquiries'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ]);
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'views');
    }
}
