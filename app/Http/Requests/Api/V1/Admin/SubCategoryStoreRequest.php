<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubCategoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tags')) {
            $tags = $this->input('tags');
            if ($tags === '' || $tags === null) {
                $this->merge(['tags' => null]);
            } elseif (is_string($tags)) {
                $this->merge(['tags' => array_values(array_filter(array_map('trim', explode(',', $tags))))]);
            }
        }

        if ($this->has('icon') && $this->input('icon') === '') {
            $this->merge(['icon' => null]);
        }

        if ($this->has('description') && $this->input('description') === '') {
            $this->merge(['description' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'industry_id' => 'required|exists:industries,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:sub_categories,slug',
            'description' => 'nullable|string|max:10000',
            'icon' => 'nullable|string|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
