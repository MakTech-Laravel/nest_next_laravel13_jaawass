<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\UserManuFactureStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManufactureStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(UserManuFactureStatus::class)],
            'reason' => [
                Rule::when(
                    $this->isRejectionStatus(),
                    ['required', 'string', 'min:5', 'max:500'],
                    ['nullable', 'string', 'max:500'],
                ),
            ],
        ];
    }

    public function resolvedStatus(): UserManuFactureStatus
    {
        return UserManuFactureStatus::from($this->validated('status'));
    }

    private function isRejectionStatus(): bool
    {
        $value = $this->input('status');

        if (! is_string($value) || $value === '') {
            return false;
        }

        $status = UserManuFactureStatus::tryFrom($value);

        return $status !== null && $status->isRejected();
    }
}