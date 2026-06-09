<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
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
            'plan_id' => 'sometimes|integer|exists:plans,id',
            'slots' => 'sometimes|integer|min:1',
            'duration_months' => 'sometimes|integer|min:1|max:120',
            'promotion_title' => 'sometimes|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:255',
            'cta_button_text' => 'nullable|string|max:255',
            'highlight_text' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'status' => 'sometimes|boolean',
            'locale' => 'sometimes|string|max:10',
        ];
    }
}
