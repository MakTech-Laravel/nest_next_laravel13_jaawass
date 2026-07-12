<?php

namespace App\Http\Requests\Api\V1\Subscription\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionAutoRenewToggleRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'vault_setup_token' => ['nullable', 'string', 'max:255'],
            'paypal_vault_id' => ['nullable', 'string', 'max:255'],
            'return_url' => ['nullable', 'url', 'max:2048'],
            'cancel_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
