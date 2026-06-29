<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_token' => ['required', 'string', 'uuid'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
