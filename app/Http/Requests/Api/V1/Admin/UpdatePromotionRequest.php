<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\Api\V1\BillingInterval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'promotional_price' => 'sometimes|numeric|min:0',
            'requires_payment' => 'sometimes|boolean',
            'billing_period_unit' => ['sometimes', 'string', Rule::in([
                BillingInterval::MONTH->value,
                BillingInterval::YEAR->value,
                'monthly',
                'yearly',
            ])],
            'promotion_title' => 'sometimes|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:255',
            'cta_button_text' => 'nullable|string|max:255',
            'highlight_text' => 'nullable|string',
            'disclaimer_text' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
            'status' => 'sometimes|boolean',
            'locale' => 'sometimes|string|max:10',
        ];
    }
}
