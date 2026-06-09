<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndustryUpdateRequest extends FormRequest
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
        if ($this->has('icon') && $this->input('icon') === '') {
            $this->merge(['icon' => null]);
        }

        if ($this->has('icon_color') && $this->input('icon_color') === '') {
            $this->merge(['icon_color' => null]);
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
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'icon' => 'nullable|string|max:2048',
            'icon_color' => 'nullable|string|max:7',
            'color' => 'nullable|string|max:7',
            'title_color' => 'nullable|string|max:7',
            'desc_color' => 'nullable|string|max:7',
            'btn_color' => 'nullable|string|max:7',
            'supplier_color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'featured' => 'nullable|boolean',
        ];
    }
}
