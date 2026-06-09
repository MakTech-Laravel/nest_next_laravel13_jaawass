<?php

namespace App\Http\Requests\Api\V1\Buyer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfqSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'quantity_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'target_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'target_currency_code' => ['sometimes', 'nullable', 'string', 'size:3'],
            'required_delivery_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'shipping_terms' => ['sometimes', 'nullable', 'string', 'max:64'],
            'destination_country' => ['sometimes', 'nullable', 'string', 'max:128'],
            'destination_port_city' => ['sometimes', 'nullable', 'string', 'max:128'],
            'packaging_details' => ['sometimes', 'nullable', 'string', 'max:128'],
            'additional_requirements' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
