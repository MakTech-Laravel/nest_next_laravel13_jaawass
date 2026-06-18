<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AdditionalInformationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitManufacturerAdditionalInformationRequest extends FormRequest
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
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.type' => ['required', 'string', Rule::in(AdditionalInformationType::values())],
            'responses.*.message' => ['nullable', 'string', 'max:5000'],
            'responses.*.file' => ['nullable', 'file'],
        ];
    }
}
