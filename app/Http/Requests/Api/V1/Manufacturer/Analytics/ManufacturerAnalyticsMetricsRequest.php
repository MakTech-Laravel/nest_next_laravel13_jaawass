<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Analytics;

use App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns\InteractsWithManufacturerAnalyticsPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManufacturerAnalyticsMetricsRequest extends FormRequest
{
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
        return $this->periodRules();
    }
}
