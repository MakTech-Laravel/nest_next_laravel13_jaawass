<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Rules\EnabledCurrencyCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCurrencyRateRequest extends FormRequest
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
            'quote_currency_code' => ['required', 'string', 'size:3', new EnabledCurrencyCode],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $quote = strtoupper((string) $this->input('quote_currency_code', ''));
            $base = strtoupper((string) config('currency.base_currency', 'USD'));
            if ($quote !== '' && $quote === $base) {
                $validator->errors()->add('quote_currency_code', __('The quote currency cannot be the base currency.'));
            }

            $rate = $this->input('rate');
            if (is_numeric($rate)) {
                $r = (float) $rate;
                $min = (float) config('currency.rate_min', 1e-10);
                $max = (float) config('currency.rate_max', 1e10);
                if ($r < $min || $r > $max) {
                    $validator->errors()->add('rate', __('The rate is outside the allowed range.'));
                }
            }
        });
    }
}
