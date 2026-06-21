<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Markets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncManufacturerExportMarketCountriesRequest extends FormRequest
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
            'country_codes' => ['required', 'array'],
            'country_codes.*' => ['required', 'string', 'max:8'],
        ];
    }
}
