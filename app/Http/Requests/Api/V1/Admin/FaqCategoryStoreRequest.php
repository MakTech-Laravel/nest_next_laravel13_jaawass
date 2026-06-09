<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FaqCategoryStoreRequest extends FormRequest
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
            'name' => 'required|string|max:256',
            'slug' => 'required|string|max:256|unique:faq_categories,slug',
            'locale' => 'nullable|string|max:10',
       ];
    }

    public function messages(): array
    {
       return [
            'name.required' => 'Category name is required',
            'name.string' => 'Category name must be a string',
            'name.max' => 'Category name must not exceed 256 characters',
            'slug.required' => 'Category slug is required',
            'slug.string' => 'Category slug must be a string',
            'slug.max' => 'Category slug must not exceed 256 characters',
       ];
    }
}
