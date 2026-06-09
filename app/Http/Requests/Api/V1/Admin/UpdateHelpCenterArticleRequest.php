<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHelpCenterArticleRequest extends FormRequest
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
            'help_center_category_id' => 'sometimes|integer|exists:help_center_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'help_full' => 'sometimes|integer|min:0',
            'not_help_full' => 'sometimes|integer|min:0',
            'status' => 'sometimes|boolean',
            'locale' => 'nullable|string|max:10',
            'steps' => 'sometimes|array',
            'steps.*.content' => 'required_with:steps|string',
            'steps.*.sort_order' => 'sometimes|integer|min:1',
        ];
    }
}
