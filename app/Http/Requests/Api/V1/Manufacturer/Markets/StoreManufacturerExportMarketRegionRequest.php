<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Markets;

use App\Support\ExportMarkets\ExportMarketCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManufacturerExportMarketRegionRequest extends FormRequest
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
            'region' => ['required', 'string', Rule::in(ExportMarketCatalog::regions())],
            'country_codes' => ['required', 'array', 'min:1'],
            'country_codes.*' => ['required', 'string', 'max:8'],
        ];
    }
}
