<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexCertificateRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'certificate_type_id' => ['sometimes', 'nullable', 'integer', 'exists:certificate_types,id'],
        ];
    }

     public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    public function searchTerm(): ?string
    {
        $value = $this->input('search');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function status(): ?string
    {
        $value = $this->input('status');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function certificateTypeId(): ?int
    {
        return $this->integer('certificate_type_id');
    }

}
