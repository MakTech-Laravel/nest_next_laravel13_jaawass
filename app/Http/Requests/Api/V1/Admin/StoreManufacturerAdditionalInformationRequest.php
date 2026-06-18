<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\AdditionalInformationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManufacturerAdditionalInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
            'allowed_types' => ['required', 'array', 'min:1'],
            'allowed_types.*' => ['required', 'string', Rule::in(AdditionalInformationType::values())],
        ];
    }
}
