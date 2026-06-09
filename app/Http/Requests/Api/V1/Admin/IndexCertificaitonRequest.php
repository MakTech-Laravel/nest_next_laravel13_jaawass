<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexCertificaitonRequest extends FormRequest
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
           'per_page' => 'nullable|integer|min:1|max:100',
           'page' => 'nullable|integer|min:1',
           'expired' => 'nullable|boolean',
           'search' => 'nullable|string|max:100',
        ];
    }

    public function perPage(): int
    {
        return $this->input('per_page', 10);
    }

    public function page(): int
    {
        return $this->input('page', 1);
    }

    public function expired(): ?bool
    {
        $value = $this->input('expired');
        
        if ($value === null) {
            return null;
        }
        
        if ($value === 'false' || $value === '0' || $value === 0) {
            return false;
        }
        
        if ($value === 'true' || $value === '1' || $value === 1) {
            return true;
        }
        
        return (bool) $value;
    }

    public function searchTerm(): ?string
    {
        $value = $this->input('search');

        return is_string($value) && $value !== '' ? $value : null;
    }


}
