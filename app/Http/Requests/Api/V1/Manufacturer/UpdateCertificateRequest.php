<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
          return [
            'certificate_type_id' => 'sometimes|integer|exists:certificate_types,id',
            'issuing_body'=> 'sometimes|string|max:255',
            'certificate_number' => 'sometimes|string|max:255',
            'issue_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|date|after:issue_date',
            'certificate_pdf' => 'sometimes|file|mimes:pdf|max:10240',
            'notes' => 'sometimes|nullable|string|max:500',
            'status' => 'sometimes|nullable|string|max:50'
        ];
    }
}
