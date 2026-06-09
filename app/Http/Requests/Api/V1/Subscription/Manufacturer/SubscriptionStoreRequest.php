<?php

namespace App\Http\Requests\Api\V1\Subscription\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionStoreRequest extends FormRequest
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
            'plan_id' => 'required|integer|exists:plans,id',
            'payment_method' => 'required|string', //Stripe, PayPal
            'billing_interval' => 'required|string', //monthly, yearly
            'payment_id' => 'required|string',
            'manufacturer_id' => 'required|integer|exists:users,id',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'trial_ends_at' => 'nullable|date',
            'auto_renew' => 'required|boolean',
            'paid_amount' => 'required|numeric',
        ];
    }
}
