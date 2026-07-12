<?php

namespace App\Http\Requests\Api\V1\Subscription\Manufacturer;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcriptionUpdgradeRquest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'payment_method' => ['required', 'string', Rule::in(RegisterPaymentManager::values())],
            'billing_interval' => ['required', 'string', Rule::in([
                BillingInterval::MONTH->value,
                BillingInterval::YEAR->value,
                'monthly',
                'yearly',
            ])],
            'payment_id' => ['required', 'string', 'max:255'],
            'paypal_vault_id' => ['nullable', 'string', 'max:255'],
            'auto_renew' => ['required', 'boolean'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
