<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateManufacturerRequest extends FormRequest
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
            // User fields
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'first_name' => 'required|string|max:30',
            'last_name' => 'nullable|string|max:30',
            'send_email' => 'nullable|boolean',
            
            // Company fields
            'company_name' => 'nullable|string|max:255',
            'company_type' => 'nullable|string|max:100',
            'company_established' => 'nullable|string|max:4',
            'company_size' => 'nullable|string|max:50',
            'revenue' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'industries_id' => 'nullable|array',
            'industries_id.*' => 'integer|exists:industries,id',
            'zip_code' => 'nullable|string|max:20',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string|max:255',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:255',
            'export_markets' => 'nullable|array',
            'export_markets.*' => 'string|max:255',
            'bussiness_license' => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
