<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
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
           'certificate_type_id' => 'required|integer|exists:certificate_types,id',
           'issuing_body'=> 'required|string|max:255',
           'certificate_number' => 'required|string|max:255',
           'issue_date' => 'required|date',
           'expiry_date' => 'required|date|after:issue_date',
           'certificate_pdf' => 'required|file|mimes:pdf|max:10240',
           'notes' => 'nullable|string|max:500'
        ];
    }
}
