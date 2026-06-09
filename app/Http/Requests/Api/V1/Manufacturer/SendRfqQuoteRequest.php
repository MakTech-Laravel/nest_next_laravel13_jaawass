<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendRfqQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quoted_price' => ['required', 'numeric', 'min:0'],
            'quote_currency_code' => ['required', 'string', 'size:3'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:1'],
            'quote_valid_until' => ['required', 'date', 'after_or_equal:today'],
            'manufacturer_reply' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
