<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usernameField = config('fortify.username', 'email');

        return [
            $usernameField => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(array_map(fn (UserRole $role) => $role->value, UserRole::cases()))],
        ];
    }
}
