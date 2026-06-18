<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use App\Enums\AdditionalInformationRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexManufacturerAdditionalInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->isManufacturer() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(AdditionalInformationRequestStatus::values())],
        ];
    }
}
