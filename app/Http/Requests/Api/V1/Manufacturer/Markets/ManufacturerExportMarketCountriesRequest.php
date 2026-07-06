<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Markets;

use App\Http\Requests\Api\V1\Manufacturer\Markets\Concerns\InteractsWithManufacturerExportMarketPagination;
use App\Support\Countries\CountryMapCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManufacturerExportMarketCountriesRequest extends FormRequest
{
    use InteractsWithManufacturerExportMarketPagination;

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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:250'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'geographic_region' => ['sometimes', 'nullable', 'string', Rule::in(CountryMapCatalog::groups())],
        ];
    }
}
