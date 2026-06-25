<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'general' => ['sometimes', 'array'],
            'general.platform_name' => ['sometimes', 'string', 'max:255'],
            'general.support_email' => ['sometimes', 'email', 'max:255'],
            'general.contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],

            'security' => ['sometimes', 'array'],
            'security.require_email_verification' => ['sometimes', 'boolean'],
            'security.manual_supplier_approval' => ['sometimes', 'boolean'],
            'security.rate_limiting' => ['sometimes', 'boolean'],

            'notifications' => ['sometimes', 'array'],
            'notifications.new_supplier_registrations' => ['sometimes', 'boolean'],
            'notifications.reported_content' => ['sometimes', 'boolean'],
            'notifications.daily_summary' => ['sometimes', 'boolean'],

            'email' => ['sometimes', 'array'],
            'email.from_name' => ['sometimes', 'string', 'max:255'],
            'email.from_email' => ['sometimes', 'email', 'max:255'],

            'localization' => ['sometimes', 'array'],
            'localization.default_language' => ['sometimes', 'string', 'max:10'],
            'localization.default_currency' => ['sometimes', 'string', 'max:10'],
            'localization.default_timezone' => ['sometimes', 'string', 'max:64'],
        ];
    }
}
