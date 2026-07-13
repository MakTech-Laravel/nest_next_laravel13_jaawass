<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Currency;
use App\Rules\EnabledCurrencyCode;
use App\Support\Localization\LocaleCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('preferred_language')) {
            return;
        }

        $supported = config('localization.supported_locales', ['en']);
        $resolved = LocaleCode::resolveSupported(
            (string) $this->input('preferred_language'),
            $supported
        );

        if ($resolved !== null) {
            $this->merge(['preferred_language' => $resolved]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supported = config('localization.supported_locales', ['en']);

        return [
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', new EnabledCurrencyCode],
            'preferred_language' => ['sometimes', 'nullable', 'string', 'max:24', Rule::in($supported)],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:64', 'timezone'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (
                ! $this->filled('currency_code')
                && ! $this->filled('preferred_language')
                && ! $this->filled('timezone')
            ) {
                $v->errors()->add('currency_code', 'Provide currency_code and/or preferred_language and/or timezone.');
            }
        });
    }

    public function currency(): ?Currency
    {
        if (! $this->filled('currency_code')) {
            return null;
        }

        return Currency::query()->where('code', strtoupper($this->string('currency_code')->toString()))->firstOrFail();
    }

    public function normalizedPreferredLanguage(): ?string
    {
        if (! $this->filled('preferred_language')) {
            return null;
        }

        $raw = trim($this->string('preferred_language')->toString());

        return $raw === '' ? null : LocaleCode::canonical($raw);
    }

    public function normalizedTimezone(): ?string
    {
        if (! $this->filled('timezone')) {
            return null;
        }

        $raw = trim($this->string('timezone')->toString());

        return $raw === '' ? null : $raw;
    }
}
