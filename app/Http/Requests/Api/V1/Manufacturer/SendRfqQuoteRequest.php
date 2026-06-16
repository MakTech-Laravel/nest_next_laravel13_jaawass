<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SendRfqQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $certifications = $this->input('quote_certifications', $this->input('certifications'));

        if (is_string($certifications) && $certifications !== '') {
            $decoded = json_decode($certifications, true);
            if (is_array($decoded)) {
                $this->merge(['quote_certifications' => $decoded]);
            }
        }
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
            'lead_time' => ['sometimes', 'nullable', 'string', 'max:128'],
            'quote_valid_until' => ['required', 'date', 'after_or_equal:today'],
            'quote_shipping_terms' => ['sometimes', 'nullable', 'string', 'max:64', Rule::in(['EXW', 'FOB', 'CIF', 'DDP'])],
            'quote_payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sample_cost' => ['sometimes', 'nullable', 'string', 'max:128'],
            'sample_lead_time' => ['sometimes', 'nullable', 'string', 'max:128'],
            'quote_packaging_details' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'quote_certifications' => ['sometimes', 'nullable', 'array', 'max:20'],
            'quote_certifications.*' => ['string', 'max:64'],
            'certifications' => ['sometimes', 'nullable', 'array', 'max:20'],
            'certifications.*' => ['string', 'max:64'],
            'quote_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'manufacturer_reply' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'photos' => ['sometimes', 'array', 'max:10'],
            'photos.*' => [
                'file',
                File::image()->max(10240),
            ],
            'attachments' => ['sometimes', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'webp'])
                    ->max(51200),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|mixed
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        if (! is_array($validated)) {
            return $validated;
        }

        if (! isset($validated['quote_certifications']) && isset($validated['certifications'])) {
            $validated['quote_certifications'] = $validated['certifications'];
        }

        unset($validated['certifications']);

        return $validated;
    }
}
