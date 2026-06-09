<?php

namespace App\Http\Requests\Api\V1\Register;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManufacturerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([UserRole::MANUFACTURER->value])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'company_name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'terms_condition' => ['required', 'accepted'],
            'bussiness_licence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'company_website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'factory_images' => ['sometimes', 'nullable', 'array', 'max:5'],
            'factory_images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
