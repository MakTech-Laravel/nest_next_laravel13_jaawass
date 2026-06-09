<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHelpCenterCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:help_center_categories,slug',
            'description' => 'nullable|string',
            'status' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:1',
            'locale' => 'nullable|string|max:10',
        ];
    }
}
