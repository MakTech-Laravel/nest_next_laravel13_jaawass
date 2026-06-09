<?php

namespace App\Http\Requests\Api\V1\Admin\QuickFilter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuickFilterOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_label' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'nullable', 'string', 'max:191'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
