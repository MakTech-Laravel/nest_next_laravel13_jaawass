<?php

namespace App\Http\Requests\Api\V1\Subscription\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionVaultSetupTokenRequest extends FormRequest
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
            'return_url' => ['required', 'url', 'max:2048'],
            'cancel_url' => ['required', 'url', 'max:2048'],
        ];
    }
}
