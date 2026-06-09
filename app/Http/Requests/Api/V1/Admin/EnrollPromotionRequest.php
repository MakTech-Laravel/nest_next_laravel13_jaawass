<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\PromotionUserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollPromotionRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'status' => [
                'sometimes',
                'string',
                Rule::in(array_column(PromotionUserStatus::cases(), 'value')),
            ],
        ];
    }
}
