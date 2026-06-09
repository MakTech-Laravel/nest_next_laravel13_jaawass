<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Rules\EnabledCurrencyCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'monthly_price' => 'required|numeric',
            'yearly_price' => 'required|numeric',
            'currency_code' => ['nullable', 'string', 'size:3', new EnabledCurrencyCode],
            'is_popular' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'features' => 'nullable|array',
            'features.*.id' => 'required|exists:features,id',
            'features.*.input_type' => 'required|in:text,boolean',
            'features.*.value' => 'required|string',
        ];
    }
}
