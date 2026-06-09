<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

class AccountPasswordReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:api'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
