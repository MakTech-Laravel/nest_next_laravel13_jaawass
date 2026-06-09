<?php

namespace App\Http\Requests\Api\V1\Admin\QuickFilter;

use Illuminate\Foundation\Http\FormRequest;

class ToggleQuickFilterOptionRequest extends FormRequest
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
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
